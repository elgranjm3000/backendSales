<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;

  class BatchSyncLog extends Model
  {
      use HasFactory;
      
      protected $table = 'sync_logs';  

      /**
       * Tipos de entidad permitidos
       */
      const ENTITY_PRODUCTS = 'products';
      const ENTITY_CUSTOMERS = 'customers';
      const ENTITY_CATEGORIES = 'categories';
      const ENTITY_SELLERS = 'sellers';
      const ENTITY_QUOTES = 'quotes';

      /**
       * Estados de sincronización
       */
      const STATUS_COMPLETED = 'completed';
      const STATUS_PARTIAL = 'partial';
      const STATUS_FAILED = 'failed';

      /**
       * Los atributos que son asignables masivamente
       */
      protected $fillable = [
          'company_id',
          'user_id',
          'entity_type',
          'records_processed',
          'records_created',
          'records_updated',
          'records_failed',
          'status',
          'error_details',
          'started_at',
          'completed_at',
      ];

      /**
       * Los atributos que deben ser convertidos
       */
      protected $casts = [
          'records_processed' => 'integer',
          'records_created' => 'integer',
          'records_updated' => 'integer',
          'records_failed' => 'integer',
          'error_details' => 'array', // JSONB a array
          'started_at' => 'datetime',
          'completed_at' => 'datetime',
          'created_at' => 'datetime',
          'updated_at' => 'datetime',
      ];

      /**
       * Campos de fecha personalizados
       */
      protected $dates = [
          'started_at',
          'completed_at',
          'created_at',
          'updated_at',
      ];

      /**
       * Relación con Company
       */
      public function company(): BelongsTo
      {
          return $this->belongsTo(Company::class);
      }

      /**
       * Relación con User
       */
      public function user(): BelongsTo
      {
          return $this->belongsTo(User::class);
      }

      // =========================================================================
      // SCOPES
      // =========================================================================

      /**
       * Scope para filtrar por compañía
       */
      public function scopeByCompany($query, $companyId)
      {
          return $query->where('company_id', $companyId);
      }

      /**
       * Scope para filtrar por entidad
       */
      public function scopeByEntity($query, $entityType)
      {
          return $query->where('entity_type', $entityType);
      }

      /**
       * Scope para filtrar por estado
       */
      public function scopeByStatus($query, $status)
      {
          return $query->where('status', $status);
      }

      /**
       * Scope para sincronizaciones exitosas
       */
      public function scopeSuccessful($query)
      {
          return $query->where('status', self::STATUS_COMPLETED);
      }

      /**
       * Scope para sincronizaciones con errores
       */
      public function scopeWithErrors($query)
      {
          return $query->where('records_failed', '>', 0);
      }

      /**
       * Scope para sincronizaciones fallidas
       */
      public function scopeFailed($query)
      {
          return $query->where('status', self::STATUS_FAILED);
      }

      /**
       * Scope para sincronizaciones parciales
       */
      public function scopePartial($query)
      {
          return $query->where('status', self::STATUS_PARTIAL);
      }

      /**
       * Scope para sincronizaciones recientes
       */
      public function scopeRecent($query, $days = 7)
      {
          return $query->where('created_at', '>=', now()->subDays($days));
      }

      /**
       * Scope para sincronizaciones completadas (que finished_at no es null)
       */
      public function scopeCompleted($query)
      {
          return $query->whereNotNull('completed_at');
      }

      // =========================================================================
      // ACCESSORS & MUTATORS
      // =========================================================================

      /**
       * Obtener duración en segundos
       */
      public function getDurationSecondsAttribute(): ?float
      {
          if (!$this->completed_at || !$this->started_at) {
              return null;
          }

          return $this->started_at->diffInSeconds($this->completed_at);
      }

      /**
       * Obtener duración en formato legible
       */
      public function getDurationFormattedAttribute(): ?string
      {
          $seconds = $this->duration_seconds;

          if ($seconds === null) {
              return null;
          }

          if ($seconds < 60) {
              return $seconds . 's';
          } elseif ($seconds < 3600) {
              return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
          } else {
              $hours = floor($seconds / 3600);
              $minutes = floor(($seconds % 3600) / 60);
              return $hours . 'h ' . $minutes . 'm';
          }
      }

      /**
       * Obtener nombre del tipo de entidad en español
       */
      public function getEntityTypeNameAttribute(): string
      {
          return match($this->entity_type) {
              self::ENTITY_PRODUCTS => 'Productos',
              self::ENTITY_CUSTOMERS => 'Clientes',
              self::ENTITY_CATEGORIES => 'Categorías',
              self::ENTITY_SELLERS => 'Vendedores',
              self::ENTITY_QUOTES => 'Cotizaciones',
              default => ucfirst($this->entity_type),
          };
      }

      /**
       * Obtener nombre del estado en español
       */
      public function getStatusNameAttribute(): string
      {
          return match($this->status) {
              self::STATUS_COMPLETED => 'Completado',
              self::STATUS_PARTIAL => 'Parcial',
              self::STATUS_FAILED => 'Fallido',
              default => ucfirst($this->status),
          };
      }

      /**
       * Verificar si la sincronización fue exitosa
       */
      public function getIsSuccessfulAttribute(): bool
      {
          return $this->status === self::STATUS_COMPLETED && $this->records_failed === 0;
      }

      /**
       * Verificar si la sincronización tiene errores
       */
      public function getHasErrorsAttribute(): bool
      {
          return $this->records_failed > 0;
      }

      /**
       * Obtener porcentaje de éxito
       */
      public function getSuccessRateAttribute(): ?float
      {
          if ($this->records_processed === 0) {
              return null;
          }

          return round(
              (($this->records_processed - $this->records_failed) / $this->records_processed) * 100,
              2
          );
      }

      // =========================================================================
      // MÉTODOS DE CLASE
      // =========================================================================

      /**
       * Obtener tipos de entidad disponibles
       */
      public static function getEntityTypes(): array
      {
          return [
              self::ENTITY_PRODUCTS => 'Productos',
              self::ENTITY_CUSTOMERS => 'Clientes',
              self::ENTITY_CATEGORIES => 'Categorías',
              self::ENTITY_SELLERS => 'Vendedores',
              self::ENTITY_QUOTES => 'Cotizaciones',
          ];
      }

      /**
       * Obtener estados disponibles
       */
      public static function getStatuses(): array
      {
          return [
              self::STATUS_COMPLETED => 'Completado',
              self::STATUS_PARTIAL => 'Parcial',
              self::STATUS_FAILED => 'Fallido',
          ];
      }

      /**
       * Crear log de sincronización
       */
      public static function log(array $data): self
      {
          return self::create(array_merge([
              'status' => self::STATUS_COMPLETED,
              'records_processed' => 0,
              'records_created' => 0,
              'records_updated' => 0,
              'records_failed' => 0,
              'started_at' => now(),
              'completed_at' => now(),
          ], $data));
      }

      /**
       * Obtener última sincronización de una entidad
       */
      public static function getLastSync(int $companyId, string $entityType): ?self
      {
          return self::byCompany($companyId)
              ->byEntity($entityType)
              ->successful()
              ->completed()
              ->orderBy('completed_at', 'desc')
              ->first();
      }

      /**
       * Verificar si necesita sincronización
       */
      public static function needsSync(int $companyId, string $entityType, int $hours = 24): bool
      {
          $lastSync = self::getLastSync($companyId, $entityType);

          if (!$lastSync) {
              return true;
          }

          return $lastSync->completed_at->lt(now()->subHours($hours));
      }
  }
