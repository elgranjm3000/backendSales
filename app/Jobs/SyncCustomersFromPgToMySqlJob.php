<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCustomersFromPgToMySqlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $batchSize;
    protected $onlyChanges;

    public $tries = 3;
    public $timeout = 300;
    public $maxExceptions = 3;

    public function __construct($companyId, $batchSize = 1000, $onlyChanges = false)
    {
        $this->companyId = $companyId;
        $this->batchSize = $batchSize;
        $this->onlyChanges = $onlyChanges;

        $this->onQueue('sync-from-pg');
    }

    public function handle()
    {
        $pg = DB::connection('pgsql');
        $mysql = DB::connection('mysql_remote');

        try {
            Log::info('Starting customers sync', [
                'company_id' => $this->companyId,
                'batch_size' => $this->batchSize,
                'only_changes' => $this->onlyChanges
            ]);

            $mysql->select('SELECT 1');

            $query = $pg->table('customers')
                ->select([
                    'code',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'status',
                    'document_type',
                    'document_number'
                ]);

            if ($this->onlyChanges) {
                $query->where('pending_sync', true);
            }

            $customers = $query->limit(10000)->get();

            if ($customers->isEmpty()) {
                Log::info('No customers to sync');
                return;
            }

            $updatedCount = 0;
            $insertedCount = 0;
            $errorCount = 0;

            $customers->chunk($this->batchSize)->each(function ($batch) use ($mysql, &$updatedCount, &$insertedCount, &$errorCount) {
                $mysql->beginTransaction();

                try {
                    foreach ($batch as $customer) {
                        try {
                            $exists = $mysql->table('customers')
                                ->where('code', $customer->code)
                                ->first();

                            $data = [
                                'name' => $customer->name,
                                'email' => $customer->email,
                                'phone' => $customer->phone,
                                'address' => $customer->address,
                                'status' => $customer->status ?? 'active',
                                'document_type' => $customer->document_type,
                                'document_number' => $customer->document_number,
                                'updated_at' => now(),
                            ];

                            if ($exists) {
                                $mysql->table('customers')
                                    ->where('code', $customer->code)
                                    ->update($data);
                                $updatedCount++;
                            } else {
                                $data['code'] = $customer->code;
                                $data['created_at'] = now();
                                $mysql->table('customers')->insert($data);
                                $insertedCount++;
                            }
                        } catch (\Exception $e) {
                            $errorCount++;
                            Log::warning('Error syncing individual customer', [
                                'code' => $customer->code,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    $mysql->commit();
                } catch (\Exception $e) {
                    $mysql->rollBack();
                    throw $e;
                }
            });

            // Actualizar sync_hashes
            if ($this->onlyChanges) {
                $codes = $customers->pluck('code');
                $pg->table('sync_hashes')
                    ->whereIn('record_key', $codes)
                    ->where('table_name', 'customers_mysql')
                    ->where('company_id', $this->companyId)
                    ->update([
                        'synced_at' => now(),
                        'updated_at' => now(),
                        'pending_sync' => false
                    ]);

                $pg->table('customers')
                    ->whereIn('code', $codes)
                    ->update(['pending_sync' => false]);
            }

            Log::info('Customers sync completed', [
                'company_id' => $this->companyId,
                'total' => $customers->count(),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Customers sync failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);

            if ($this->isConnectionError($e)) {
                $this->release(30);
            } else {
                throw $e;
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Customers sync job failed permanently', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);
    }

    protected function isConnectionError($exception)
    {
        $connectionErrors = [
            'MySQL server has gone away',
            'Lost connection',
            'Connection reset',
            'Timeout',
            'SQLSTATE[HY000]'
        ];

        foreach ($connectionErrors as $error) {
            if (str_contains($exception->getMessage(), $error)) {
                return true;
            }
        }

        return false;
    }
}
