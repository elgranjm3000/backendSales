<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SyncFailedNotification extends Notification
{
    use Queueable;

    /**
     * Crear una nueva instancia de notificación.
     */
    public function __construct(
        protected string $entity,
        protected \Throwable $exception,
        protected ?int $companyId = null
    ) {}

    /**
     * Obtener los canales de notificación.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Obtener la representación por email de la notificación.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("❌ Sync Failed: {$this->entity}")
            ->greeting('Hello Admin,')
            ->line("A synchronization job has failed permanently for entity: **{$this->entity}**")
            ->line("**Company ID:** " . ($this->companyId ?? 'N/A'))
            ->line("**Error:** {$this->exception->getMessage()}")
            ->line("**Time:** " . now()->toDateTimeString())
            ->action('View Logs', url('/admin/logs'))
            ->line('Please check the logs for more details.');
    }

    /**
     * Obtener el array de representación de la notificación.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'entity' => $this->entity,
            'company_id' => $this->companyId,
            'error' => $this->exception->getMessage(),
            'trace' => $this->exception->getTraceAsString(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
