<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncQuotesFromMySqlToPgJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $batchSize;
    protected $onlyChanges;

    public $tries = 3;
    public $timeout = 300;
    public $maxExceptions = 3;

    public function __construct($companyId, $batchSize = 500, $onlyChanges = true)
    {
        $this->companyId = $companyId;
        $this->batchSize = $batchSize;
        $this->onlyChanges = $onlyChanges;

        $this->onQueue('sync-from-mysql');
    }

    public function handle()
    {
        $mysql = DB::connection('mysql_remote');
        $pg = DB::connection('pgsql');

        try {
            Log::info('Starting quotes sync from MySQL to PostgreSQL', [
                'company_id' => $this->companyId,
                'batch_size' => $this->batchSize
            ]);

            $pg->select('SELECT 1');

            // Obtener quotes de MySQL
            $query = $mysql->table('quotes')
                ->where('company_id', $this->companyId)
                ->select([
                    'id',
                    'quote_number',
                    'customer_id',
                    'seller_id',
                    'subtotal',
                    'tax',
                    'total',
                    'status',
                    'created_at',
                    'updated_at'
                ]);

            if ($this->onlyChanges) {
                $query->where('pending_sync', true);
            }

            $quotes = $query->orderBy('created_at', 'desc')
                ->limit(5000)
                ->get();

            if ($quotes->isEmpty()) {
                Log::info('No quotes to sync from MySQL');
                return;
            }

            $insertedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;

            $quotes->chunk($this->batchSize)->each(function ($batch) use ($pg, $mysql, &$insertedCount, &$updatedCount, &$errorCount) {
                $pg->beginTransaction();

                try {
                    foreach ($batch as $quote) {
                        try {
                            // Verificar si existe en PostgreSQL
                            $exists = $pg->table('quotes')
                                ->where('mysql_quote_id', $quote->id)
                                ->first();

                            $quoteData = [
                                'quote_number' => $quote->quote_number,
                                'customer_id' => $quote->customer_id,
                                'seller_id' => $quote->seller_id,
                                'company_id' => $this->companyId,
                                'subtotal' => $quote->subtotal,
                                'tax' => $quote->tax,
                                'total' => $quote->total,
                                'status' => $quote->status ?? 'draft',
                                'mysql_quote_id' => $quote->id,
                                'synced_from_mysql' => true,
                                'created_at' => $quote->created_at ?? now(),
                                'updated_at' => $quote->updated_at ?? now(),
                            ];

                            if ($exists) {
                                // UPDATE
                                $pg->table('quotes')
                                    ->where('mysql_quote_id', $quote->id)
                                    ->update($quoteData);
                                $updatedCount++;
                            } else {
                                // INSERT
                                $pg->table('quotes')->insert($quoteData);
                                $insertedCount++;
                            }

                            // Sincronizar items de la quote
                            $this->syncQuoteItems($quote->id, $pg, $mysql);

                        } catch (\Exception $e) {
                            $errorCount++;
                            Log::warning('Error syncing individual quote', [
                                'quote_id' => $quote->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    $pg->commit();
                } catch (\Exception $e) {
                    $pg->rollBack();
                    throw $e;
                }
            });

            // Actualizar pending_sync en MySQL
            if ($this->onlyChanges) {
                $quoteIds = $quotes->pluck('id');
                $mysql->table('quotes')
                    ->whereIn('id', $quoteIds)
                    ->update(['pending_sync' => false]);
            }

            Log::info('Quotes sync completed', [
                'company_id' => $this->companyId,
                'total' => $quotes->count(),
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Quotes sync from MySQL failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($this->isConnectionError($e)) {
                $this->release(30);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Sincronizar items de una quote.
     */
    protected function syncQuoteItems($mysqlQuoteId, $pg, $mysql)
    {
        try {
            // Obtener items de MySQL
            $items = $mysql->table('quote_items')
                ->where('quote_id', $mysqlQuoteId)
                ->get();

            if ($items->isEmpty()) {
                return;
            }

            // Obtener el ID de la quote en PostgreSQL
            $pgQuote = $pg->table('quotes')
                ->where('mysql_quote_id', $mysqlQuoteId)
                ->first();

            if (!$pgQuote) {
                Log::warning('Quote not found in PostgreSQL for items sync', [
                    'mysql_quote_id' => $mysqlQuoteId
                ]);
                return;
            }

            foreach ($items as $item) {
                // Verificar si existe el item
                $exists = $pg->table('quote_items')
                    ->where('quote_id', $pgQuote->id)
                    ->where('mysql_item_id', $item->id)
                    ->first();

                $itemData = [
                    'quote_id' => $pgQuote->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount ?? 0,
                    'total' => $item->total,
                    'mysql_item_id' => $item->id,
                    'created_at' => $item->created_at ?? now(),
                    'updated_at' => $item->updated_at ?? now(),
                ];

                if ($exists) {
                    $pg->table('quote_items')
                        ->where('mysql_item_id', $item->id)
                        ->update($itemData);
                } else {
                    $pg->table('quote_items')->insert($itemData);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error syncing quote items', [
                'mysql_quote_id' => $mysqlQuoteId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Quotes sync job failed permanently', [
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
