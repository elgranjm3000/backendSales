<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProductsFromPgToMySqlJob;
use App\Jobs\SyncCustomersFromPgToMySqlJob;
use App\Jobs\SyncQuotesFromMySqlToPgJob;
use App\Jobs\SyncSellersFromPgToMySqlJob;
use App\Jobs\SyncCategoriesFromPgToMySqlJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BiDirectionalSyncController extends Controller
{
    /**
     * Sincronizar productos de PostgreSQL a MySQL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'batch_size' => 'nullable|integer|min:100|max:5000',
            'only_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $companyId = $request->input('company_id');
            $batchSize = $request->input('batch_size', 1000);
            $onlyChanges = $request->input('only_changes', true);

            // Despachar job
            SyncProductsFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);

            Log::info('Products sync job dispatched', [
                'company_id' => $companyId,
                'batch_size' => $batchSize,
                'only_changes' => $onlyChanges
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Products sync job dispatched successfully',
                'data' => [
                    'company_id' => $companyId,
                    'batch_size' => $batchSize,
                    'only_changes' => $onlyChanges,
                    'queue' => 'sync-from-pg'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching products sync job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error dispatching sync job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar customers de PostgreSQL a MySQL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncCustomers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'batch_size' => 'nullable|integer|min:100|max:5000',
            'only_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $companyId = $request->input('company_id');
            $batchSize = $request->input('batch_size', 1000);
            $onlyChanges = $request->input('only_changes', true);

            SyncCustomersFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);

            Log::info('Customers sync job dispatched', [
                'company_id' => $companyId,
                'batch_size' => $batchSize
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customers sync job dispatched successfully',
                'data' => [
                    'company_id' => $companyId,
                    'batch_size' => $batchSize,
                    'only_changes' => $onlyChanges,
                    'queue' => 'sync-from-pg'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching customers sync job', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error dispatching sync job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar quotes de MySQL a PostgreSQL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncQuotes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'batch_size' => 'nullable|integer|min:100|max:2000',
            'only_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $companyId = $request->input('company_id');
            $batchSize = $request->input('batch_size', 500);
            $onlyChanges = $request->input('only_changes', true);

            SyncQuotesFromMySqlToPgJob::dispatch($companyId, $batchSize, $onlyChanges);

            Log::info('Quotes sync job dispatched', [
                'company_id' => $companyId,
                'batch_size' => $batchSize
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quotes sync job dispatched successfully',
                'data' => [
                    'company_id' => $companyId,
                    'batch_size' => $batchSize,
                    'only_changes' => $onlyChanges,
                    'queue' => 'sync-from-mysql'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching quotes sync job', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error dispatching sync job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar sellers de PostgreSQL a MySQL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncSellers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'batch_size' => 'nullable|integer|min:100|max:2000',
            'only_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $companyId = $request->input('company_id');
            $batchSize = $request->input('batch_size', 500);
            $onlyChanges = $request->input('only_changes', true);

            SyncSellersFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);

            Log::info('Sellers sync job dispatched', [
                'company_id' => $companyId,
                'batch_size' => $batchSize
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sellers sync job dispatched successfully',
                'data' => [
                    'company_id' => $companyId,
                    'batch_size' => $batchSize,
                    'only_changes' => $onlyChanges,
                    'queue' => 'sync-from-pg'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching sellers sync job', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error dispatching sync job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar categories de PostgreSQL a MySQL.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'batch_size' => 'nullable|integer|min:100|max:1000',
            'only_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $companyId = $request->input('company_id');
            $batchSize = $request->input('batch_size', 500);
            $onlyChanges = $request->input('only_changes', true);

            SyncCategoriesFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);

            Log::info('Categories sync job dispatched', [
                'company_id' => $companyId,
                'batch_size' => $batchSize
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categories sync job dispatched successfully',
                'data' => [
                    'company_id' => $companyId,
                    'batch_size' => $batchSize,
                    'only_changes' => $onlyChanges,
                    'queue' => 'sync-from-pg'
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching categories sync job', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error dispatching sync job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizar todas las entidades (sync completo).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'batch_size' => 'nullable|integer|min:100|max:5000',
            'only_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $companyId = $request->input('company_id');
            $batchSize = $request->input('batch_size', 1000);
            $onlyChanges = $request->input('only_changes', false);

            // Despachar todos los jobs
            $jobs = [
                SyncProductsFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges),
                SyncCustomersFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges),
                SyncQuotesFromMySqlToPgJob::dispatch($companyId, $batchSize, $onlyChanges),
                SyncSellersFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges),
                SyncCategoriesFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges),
            ];

            Log::info('Full sync dispatched', [
                'company_id' => $companyId,
                'jobs_count' => count($jobs)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Full sync dispatched successfully',
                'data' => [
                    'company_id' => $companyId,
                    'batch_size' => $batchSize,
                    'only_changes' => $onlyChanges,
                    'entities' => ['products', 'customers', 'quotes', 'sellers', 'categories'],
                    'jobs_dispatched' => count($jobs)
                ]
            ], 202);

        } catch (\Exception $e) {
            Log::error('Error dispatching full sync', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error dispatching full sync',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de sincronización.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        try {
            $companyId = $request->input('company_id');

            // Obtener conteos de PostgreSQL
            $pg = DB::connection('pgsql');

            $stats = [
                'postgresql' => [
                    'products' => $pg->table('products')->count(),
                    'customers' => $pg->table('customers')->count(),
                    'quotes' => $pg->table('quotes')
                        ->when($companyId, function ($query) use ($companyId) {
                            return $query->where('company_id', $companyId);
                        })->count(),
                    'sellers' => $pg->table('sellers')
                        ->when($companyId, function ($query) use ($companyId) {
                            return $query->where('company_id', $companyId);
                        })->count(),
                    'categories' => $pg->table('categories')
                        ->when($companyId, function ($query) use ($companyId) {
                            return $query->where('company_id', $companyId);
                        })->count(),
                ],
                'mysql' => [
                    'products' => 0,
                    'customers' => 0,
                    'quotes' => 0,
                    'sellers' => 0,
                    'categories' => 0,
                ]
            ];

            // Obtener conteos de MySQL
            try {
                $mysql = DB::connection('mysql_remote');

                $stats['mysql']['products'] = $mysql->table('products')->count();
                $stats['mysql']['customers'] = $mysql->table('customers')->count();
                $stats['mysql']['quotes'] = $mysql->table('quotes')
                    ->when($companyId, function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    })->count();
                $stats['mysql']['sellers'] = $mysql->table('sellers')
                    ->when($companyId, function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    })->count();
                $stats['mysql']['categories'] = $mysql->table('categories')
                    ->when($companyId, function ($query) use ($companyId) {
                        return $query->where('company_id', $companyId);
                    })->count();

            } catch (\Exception $e) {
                $stats['mysql_error'] = $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting sync stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting sync stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estado de las colas de sincronización.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueueStatus()
    {
        try {
            $pg = DB::connection('pgsql');

            // Contar jobs pendientes
            $pendingJobs = $pg->table('jobs')
                ->where('queue', 'like', 'sync-%')
                ->count();

            // Contar jobs fallidos
            $failedJobs = $pg->table('failed_jobs')
                ->where('queue', 'like', 'sync-%')
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'queues' => [
                        'sync-from-pg' => 'PostgreSQL → MySQL',
                        'sync-from-mysql' => 'MySQL → PostgreSQL'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting queue status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
