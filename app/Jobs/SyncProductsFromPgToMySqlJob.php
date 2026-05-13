<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\SyncFailedNotification;

class SyncProductsFromPgToMySqlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $batchSize;
    protected $onlyChanges;

    public $tries = 3;
    public $timeout = 300; // 5 minutos
    public $maxExceptions = 3;

    /**
     * Crear un nuevo job de sincronización.
     *
     * @param int $companyId ID de la compañía
     * @param int $batchSize Tamaño del lote (default: 1000)
     * @param bool $onlyChanges Si true, solo sincroniza cambios pendientes
     */
    public function __construct($companyId, $batchSize = 1000, $onlyChanges = false)
    {
        $this->companyId = $companyId;
        $this->batchSize = $batchSize;
        $this->onlyChanges = $onlyChanges;

        $this->onQueue('sync-from-pg');
    }

    /**
     * Ejecutar el job.
     */
    public function handle()
    {
        $pg = DB::connection('pgsql');
        $mysql = DB::connection('mysql_remote');

        try {
            Log::info('Starting products sync', [
                'company_id' => $this->companyId,
                'batch_size' => $this->batchSize,
                'only_changes' => $this->onlyChanges
            ]);

            // Verificar conexión MySQL
            $mysql->select('SELECT 1');

            // Obtener productos de PostgreSQL
            $query = $pg->table('products')
                ->select([
                    'code',
                    'description',
                    'minimal_sale',
                    'maximal_sale',
                    'status',
                    'product_type',
                    'sale_price'
                ]);

            // Si solo queremos cambios, filtrar por pending_sync
            if ($this->onlyChanges) {
                $query->where('pending_sync', true);
            }

            $products = $query->limit(10000)->get(); // Max 10k por job

            if ($products->isEmpty()) {
                Log::info('No products to sync');
                return;
            }

            $updatedCount = 0;
            $insertedCount = 0;
            $errorCount = 0;

            // Procesar en batches para no saturar memoria
            $products->chunk($this->batchSize)->each(function ($batch) use ($mysql, &$updatedCount, &$insertedCount, &$errorCount) {
                $mysql->beginTransaction();

                try {
                    foreach ($batch as $product) {
                        try {
                            // Verificar si existe en MySQL
                            $exists = $mysql->table('products')
                                ->where('code', $product->code)
                                ->first();

                            $data = [
                                'description' => $product->description,
                                'minimal_sale' => $product->minimal_sale,
                                'maximal_sale' => $product->maximal_sale,
                                'status' => $product->status ?? 'active',
                                'product_type' => $product->product_type,
                                'sale_price' => $product->sale_price,
                                'updated_at' => now(),
                            ];

                            if ($exists) {
                                // UPDATE si existe
                                $mysql->table('products')
                                    ->where('code', $product->code)
                                    ->update($data);
                                $updatedCount++;
                            } else {
                                // INSERT si no existe
                                $data['code'] = $product->code;
                                $data['created_at'] = now();
                                $mysql->table('products')->insert($data);
                                $insertedCount++;
                            }
                        } catch (\Exception $e) {
                            $errorCount++;
                            Log::warning('Error syncing individual product', [
                                'code' => $product->code,
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

            // Actualizar sync_hashes en PostgreSQL
            if ($this->onlyChanges) {
                $codes = $products->pluck('code');
                $pg->table('sync_hashes')
                    ->whereIn('record_key', $codes)
                    ->where('table_name', 'products_mysql')
                    ->where('company_id', $this->companyId)
                    ->update([
                        'synced_at' => now(),
                        'updated_at' => now(),
                        'pending_sync' => false
                    ]);

                // Limpiar pending_sync en products
                $pg->table('products')
                    ->whereIn('code', $codes)
                    ->update(['pending_sync' => false]);
            }

            Log::info('Products sync completed', [
                'company_id' => $this->companyId,
                'total' => $products->count(),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Products sync failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Reintentar si es error de conexión
            if ($this->isConnectionError($e)) {
                $this->release(30); // Reintentar en 30 segundos
            } else {
                throw $e;
            }
        }
    }

    /**
     * El job falló permanentemente.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Products sync job failed permanently', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage()
        ]);

        // Enviar notificación (opcional)
        // $admin = User::where('role', 'admin')->first();
        // if ($admin) {
        //     $admin->notify(new SyncFailedNotification('products', $exception));
        // }
    }

    /**
     * Determinar si el error es de conexión.
     */
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
