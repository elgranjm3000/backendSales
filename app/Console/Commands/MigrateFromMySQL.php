<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateFromMySQL extends Command
{
    protected $signature = 'migrate:mysql-to-pgsql {--chunk=500}';
    protected $description = 'Migrate data from MySQL remote to PostgreSQL local';

    public function handle()
    {
        $this->info('Starting migration from MySQL (remote) to PostgreSQL (local)...');

        $mysql = DB::connection('mysql_remote');
        $pgsql = DB::connection('pgsql');

        // Test connections
        try {
            $mysql->getPdo();
            $this->info('✓ MySQL remote connection: OK');
        } catch (\Exception $e) {
            $this->error('✗ MySQL remote connection: FAILED - ' . $e->getMessage());
            return 1;
        }

        try {
            $pgsql->getPdo();
            $this->info('✓ PostgreSQL local connection: OK');
        } catch (\Exception $e) {
            $this->error('✗ PostgreSQL local connection: FAILED - ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        $tables = [
            'users',
            'companies',
            'categories',
            'products',
            'customers',
            'sellers',
            'quotes',
            'quote_items',
            'active_sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'sessions',
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
            'key_system_items',
            'acceso',
        ];

        foreach ($tables as $table) {
            $this->migrateTable($table, $mysql, $pgsql);
        }

        $this->newLine();
        $this->info('===========================================');
        $this->info('✓ Migration completed successfully!');
        $this->info('===========================================');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('1. Verify record counts: php artisan migrate:validate');
        $this->info('2. Test the application');

        return 0;
    }

    private function migrateTable($table, $mysql, $pgsql)
    {
        $this->info("Processing table: {$table}");

        $count = $mysql->table($table)->count();
        if ($count === 0) {
            $this->info("  → Skipping (empty table)");
            return;
        }

        $this->info("  → Found {$count} records");

        $chunkSize = (int) $this->option('chunk');
        $progressBar = $this->output->createProgressBar($count);

        $mysql->table($table)
            ->orderBy('id')
            ->chunk($chunkSize, function ($records) use ($pgsql, $table, $progressBar) {
                foreach ($records as $record) {
                    $recordArray = (array) $record;

                    // Convert JSON columns for PostgreSQL
                    $jsonColumns = ['metadata', 'images', 'attributes', 'additional_info', 'migration'];
                    foreach ($jsonColumns as $col) {
                        if (isset($recordArray[$col]) && !empty($recordArray[$col])) {
                            if (is_array($recordArray[$col]) || is_object($recordArray[$col])) {
                                $recordArray[$col] = json_encode($recordArray[$col]);
                            }
                        }
                    }

                    // Skip BLOB columns (logo, restaurant_image, main_image) - they need special handling
                    $blobColumns = ['logo', 'restaurant_image', 'main_image', 'logo_type', 'restaurant_image_type', 'main_image_type'];
                    foreach ($blobColumns as $col) {
                        unset($recordArray[$col]);
                    }

                    // Convert timestamps
                    if (isset($recordArray['created_at']) && $recordArray['created_at']) {
                        $recordArray['created_at'] = date('Y-m-d H:i:s', strtotime($recordArray['created_at']));
                    }
                    if (isset($recordArray['updated_at']) && $recordArray['updated_at']) {
                        $recordArray['updated_at'] = date('Y-m-d H:i:s', strtotime($recordArray['updated_at']));
                    }

                    // Handle email_verified_at
                    if (isset($recordArray['email_verified_at']) && $recordArray['email_verified_at']) {
                        $recordArray['email_verified_at'] = date('Y-m-d H:i:s', strtotime($recordArray['email_verified_at']));
                    }

                    try {
                        $pgsql->table($table)->insert($recordArray);
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->warn("    Warning: Could not insert record ID {$recordArray['id']}: " . $e->getMessage());
                    }

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();
        $this->info("  ✓ Completed table: {$table}");
        $this->newLine();
    }
}
