<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCategoriesFromPgToMySqlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $batchSize;
    protected $onlyChanges;

    public $tries = 3;
    public $timeout = 120;
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
            Log::info('Starting categories sync', [
                'company_id' => $this->companyId,
                'batch_size' => $this->batchSize
            ]);

            $mysql->select('SELECT 1');

            // Obtener categories
            $query = $pg->table('categories')
                ->where('company_id', $this->companyId)
                ->select([
                    'id',
                    'name',
                    'description',
                    'status'
                ]);

            if ($this->onlyChanges) {
                $query->where('pending_sync', true);
            }

            $categories = $query->limit(1000)->get();

            if ($categories->isEmpty()) {
                Log::info('No categories to sync');
                return;
            }

            $updatedCount = 0;
            $insertedCount = 0;
            $errorCount = 0;

            $categories->chunk($this->batchSize)->each(function ($batch) use ($mysql, &$updatedCount, &$insertedCount, &$errorCount) {
                $mysql->beginTransaction();

                try {
                    foreach ($batch as $category) {
                        try {
                            $exists = $mysql->table('categories')
                                ->where('pg_category_id', $category->id)
                                ->where('company_id', $this->companyId)
                                ->first();

                            $data = [
                                'name' => $category->name,
                                'description' => $category->description,
                                'status' => $category->status ?? 'active',
                                'company_id' => $this->companyId,
                                'pg_category_id' => $category->id,
                                'updated_at' => now(),
                            ];

                            if ($exists) {
                                $mysql->table('categories')
                                    ->where('pg_category_id', $category->id)
                                    ->where('company_id', $this->companyId)
                                    ->update($data);
                                $updatedCount++;
                            } else {
                                $data['created_at'] = now();
                                $mysql->table('categories')->insert($data);
                                $insertedCount++;
                            }
                        } catch (\Exception $e) {
                            $errorCount++;
                            Log::warning('Error syncing individual category', [
                                'id' => $category->id,
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

            // Actualizar pending_sync
            if ($this->onlyChanges) {
                $pgCategoryIds = $categories->pluck('id');
                $pg->table('categories')
                    ->whereIn('id', $pgCategoryIds)
                    ->update(['pending_sync' => false]);
            }

            Log::info('Categories sync completed', [
                'company_id' => $this->companyId,
                'total' => $categories->count(),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Categories sync failed', [
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
        Log::error('Categories sync job failed permanently', [
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
