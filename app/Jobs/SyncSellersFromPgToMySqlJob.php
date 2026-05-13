<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSellersFromPgToMySqlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $batchSize;
    protected $onlyChanges;

    public $tries = 3;
    public $timeout = 180;
    public $maxExceptions = 3;

    public function __construct($companyId, $batchSize = 500, $onlyChanges = true)
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
            Log::info('Starting sellers sync', [
                'company_id' => $this->companyId,
                'batch_size' => $this->batchSize
            ]);

            $mysql->select('SELECT 1');

            // Obtener sellers con sus usuarios
            $query = $pg->table('sellers as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->where('s.company_id', $this->companyId)
                ->select([
                    's.id as pg_seller_id',
                    's.code',
                    's.percent_sales',
                    's.seller_status',
                    'u.name',
                    'u.email',
                    'u.phone'
                ]);

            if ($this->onlyChanges) {
                $query->where('s.pending_sync', true);
            }

            $sellers = $query->limit(2000)->get();

            if ($sellers->isEmpty()) {
                Log::info('No sellers to sync');
                return;
            }

            $updatedCount = 0;
            $insertedCount = 0;
            $errorCount = 0;

            $sellers->chunk($this->batchSize)->each(function ($batch) use ($mysql, &$updatedCount, &$insertedCount, &$errorCount) {
                $mysql->beginTransaction();

                try {
                    foreach ($batch as $seller) {
                        try {
                            $exists = $mysql->table('sellers')
                                ->where('code', $seller->code)
                                ->where('company_id', $this->companyId)
                                ->first();

                            $data = [
                                'name' => $seller->name,
                                'email' => $seller->email,
                                'phone' => $seller->phone,
                                'percent_sales' => $seller->percent_sales,
                                'seller_status' => $seller->seller_status ?? 'active',
                                'company_id' => $this->companyId,
                                'pg_seller_id' => $seller->pg_seller_id,
                                'updated_at' => now(),
                            ];

                            if ($exists) {
                                $mysql->table('sellers')
                                    ->where('code', $seller->code)
                                    ->where('company_id', $this->companyId)
                                    ->update($data);
                                $updatedCount++;
                            } else {
                                $data['code'] = $seller->code;
                                $data['created_at'] = now();
                                $mysql->table('sellers')->insert($data);
                                $insertedCount++;
                            }
                        } catch (\Exception $e) {
                            $errorCount++;
                            Log::warning('Error syncing individual seller', [
                                'code' => $seller->code,
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
                $pgSellerIds = $sellers->pluck('pg_seller_id');
                $pg->table('sellers')
                    ->whereIn('id', $pgSellerIds)
                    ->update(['pending_sync' => false]);
            }

            Log::info('Sellers sync completed', [
                'company_id' => $this->companyId,
                'total' => $sellers->count(),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Sellers sync failed', [
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
        Log::error('Sellers sync job failed permanently', [
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
