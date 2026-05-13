<?php

namespace App\Console\Commands;

use App\Jobs\SyncProductsFromPgToMySqlJob;
use App\Jobs\SyncCustomersFromPgToMySqlJob;
use App\Jobs\SyncQuotesFromMySqlToPgJob;
use App\Jobs\SyncSellersFromPgToMySqlJob;
use App\Jobs\SyncCategoriesFromPgToMySqlJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncRunAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:run-all
                            {--entity= : Entity to sync (products, customers, quotes, sellers, categories, all)}
                            {--company_id= : Company ID (default: all companies)}
                            {--batch-size=1000 : Batch size for operations}
                            {--only-changes : Sync only pending changes}
                            {--force : Force full sync (ignore only-changes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute synchronization between PostgreSQL and MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $entity = $this->option('entity') ?? 'all';
        $companyId = $this->option('company_id');
        $batchSize = (int) $this->option('batch-size') ?: 1000;
        $onlyChanges = $this->option('only-changes') && !$this->option('force');

        $this->info("🔄 Starting synchronization...");
        $this->info("Entity: {$entity}");
        $this->info("Batch Size: {$batchSize}");
        $this->info("Only Changes: " . ($onlyChanges ? 'Yes' : 'No'));

        // Obtener company IDs
        if ($companyId) {
            $companyIds = [(int) $companyId];
        } else {
            $companyIds = DB::table('companies')->where('status', 'active')->pluck('id')->toArray();
            $this->info("Companies: " . implode(', ', $companyIds));
        }

        if (empty($companyIds)) {
            $this->error('No active companies found');
            return 1;
        }

        $this->newLine();

        // Procesar cada compañía
        foreach ($companyIds as $id) {
            $this->info("🏢 Processing Company ID: {$id}");

            $jobsDispatched = $this->dispatchJobs($id, $entity, $batchSize, $onlyChanges);

            if ($jobsDispatched > 0) {
                $this->info("✅ Dispatched {$jobsDispatched} job(s) for company {$id}");
            } else {
                $this->warn("⚠️  No jobs dispatched for company {$id}");
            }

            $this->newLine();
        }

        $this->info("✨ Synchronization completed!");
        $this->info("💡 Monitor progress with: php artisan queue:work --queue=sync-from-pg,sync-from-mysql");

        Log::info('Sync command executed', [
            'entity' => $entity,
            'company_ids' => $companyIds,
            'batch_size' => $batchSize,
            'only_changes' => $onlyChanges
        ]);

        return 0;
    }

    /**
     * Despachar jobs según la entidad.
     */
    protected function dispatchJobs($companyId, $entity, $batchSize, $onlyChanges)
    {
        $jobsDispatched = 0;
        $entities = [];

        if ($entity === 'all') {
            $entities = ['products', 'customers', 'quotes', 'sellers', 'categories'];
        } else {
            $entities = [$entity];
        }

        foreach ($entities as $ent) {
            try {
                switch ($ent) {
                    case 'products':
                        SyncProductsFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);
                        $this->info("   📦 Products job dispatched");
                        $jobsDispatched++;
                        break;

                    case 'customers':
                        SyncCustomersFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);
                        $this->info("   👥 Customers job dispatched");
                        $jobsDispatched++;
                        break;

                    case 'quotes':
                        SyncQuotesFromMySqlToPgJob::dispatch($companyId, $batchSize, $onlyChanges);
                        $this->info("   📄 Quotes job dispatched");
                        $jobsDispatched++;
                        break;

                    case 'sellers':
                        SyncSellersFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);
                        $this->info("   👔 Sellers job dispatched");
                        $jobsDispatched++;
                        break;

                    case 'categories':
                        SyncCategoriesFromPgToMySqlJob::dispatch($companyId, $batchSize, $onlyChanges);
                        $this->info("   🏷️  Categories job dispatched");
                        $jobsDispatched++;
                        break;

                    default:
                        $this->warn("   ⚠️  Unknown entity: {$ent}");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Error dispatching {$ent}: {$e->getMessage()}");
                Log::error("Error dispatching sync job", [
                    'entity' => $ent,
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $jobsDispatched;
    }
}
