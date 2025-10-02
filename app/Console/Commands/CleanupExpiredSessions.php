<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActiveSession;
use Carbon\Carbon;

class CleanupExpiredSessions extends Command
{
    protected $signature = 'sessions:cleanup {--hours=1}';
    protected $description = 'Eliminar sesiones expiradas o inactivas';

    public function handle()
    {
        $hours = $this->option('hours');
        $cutoffTime = Carbon::now()->subHours($hours);

        $this->info("Limpiando sesiones...");

        $expiredByDate = ActiveSession::where('expires_at', '<', now())->count();
        ActiveSession::where('expires_at', '<', now())->delete();

        $expiredByInactivity = ActiveSession::where('last_activity', '<', $cutoffTime)->count();
        ActiveSession::where('last_activity', '<', $cutoffTime)->delete();

        $total = $expiredByDate + $expiredByInactivity;

        $this->info("✓ Limpieza completada: {$total} sesiones eliminadas");

        return Command::SUCCESS;
    }
}