<?php

namespace App\Console\Commands;

use App\Models\Acceso;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAccesoApiKeys extends Command
{
    protected $signature = 'acceso:generate-keys
                            {--id= : Generar key solo para un ID específico}
                            {--force : Regenerar aunque ya tenga key}';

    protected $description = 'Genera API keys para las empresas de la tabla acceso';

    public function handle(): int
    {
        $query = Acceso::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif (!$this->option('force')) {
            $query->whereNull('api_key');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('No hay empresas pendientes por generar.');
            return 0;
        }

        if (!$this->confirm("Se generarán keys para {$total} empresa(s). ¿Continuar?")) {
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $generated = 0;
        $query->each(function (Acceso $acceso) use ($bar, &$generated) {
            $acceso->update([
                'api_key' => 'chr_' . Str::random(60),
                'api_key_expires_at' => null, // sin expiración
            ]);
            $generated++;
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("{$generated} key(s) generadas correctamente.");

        return 0;
    }
}
