<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\JustificationDocument;
use App\Models\UniversityClass;
use App\Models\User;
use App\Justifications\State\JustificationState;
use App\Justifications\State\JustificationStateFactory;
use App\Justifications\Observers\JustificationCreatedEvent;
use App\Justifications\Observers\JustificationEvent;
use App\Justifications\Observers\JustificationStatusChangedEvent;
use App\Justifications\Observers\JustificationObservable;
use App\Justifications\Observers\JustificationObserver;
use Illuminate\Support\Facades\Event;

class Justification extends Model implements  JustificationObservable
{
    private ?JustificationEvent $pendingEvent = null;

    protected $fillable = [
        'description',
        'start_date',
        'end_date',
        'university_class_id',
        'student_id',
        'status'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Constantes para los estados
    const STATUS_PENDING = 'En Proceso';
    const STATUS_APPROVED = 'Aceptada';
    const STATUS_REJECTED = 'Rechazada';

    // Métodos para verificar el estado
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    // Obtener el estado como objeto (Patrón State)
    public function state(): JustificationState
    {
        return JustificationStateFactory::make($this->status);
    }

    public function approve(): void
    {
        $this->state()->approve($this);
    }

    public function reject(): void
    {
        $this->state()->reject($this);
    }

    // Método para obtener el texto del estado
    public function getStatusTextAttribute(): string
    {
        return $this->status; // Ya está en español
    }

    // Método para obtener la clase CSS del estado
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-200 text-yellow-900 dark:bg-yellow-300 dark:text-yellow-900 border border-yellow-300 dark:border-yellow-400',
            self::STATUS_APPROVED => 'bg-green-200 text-green-900 dark:bg-green-300 dark:text-green-900 border border-green-300 dark:border-green-400',
            self::STATUS_REJECTED => 'bg-red-200 text-red-900 dark:bg-red-300 dark:text-red-900 border border-red-300 dark:border-red-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600'
        };
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(UniversityClass::class, 'university_class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(JustificationDocument::class);
    }

  public function attachJustificationObserver(JustificationObserver $observer): void
    {
        // Optional: if you truly need runtime attach per UML, you can register dynamically:
        Event::listen(JustificationCreatedEvent::class, [$observer, 'handle']);
        Event::listen(JustificationStatusChangedEvent::class, [$observer, 'handle']);
    }

    public function detachJustificationObserver(JustificationObserver $observer): void
    {
        // Laravel can "forget" listeners by event name (removes a set of listeners). :contentReference[oaicite:5]{index=5}
        // If you need per-observer detach, you'd rebuild listeners after forget.
        Event::forget(JustificationCreatedEvent::class);
        Event::forget(JustificationStatusChangedEvent::class);
    }

    public function notifyJustificationObserver(): void
    {
        if ($this->pendingEvent) {
            event($this->pendingEvent); // dispatches to all Event::listen(...) listeners
            $this->pendingEvent = null;
        }
    }

    protected static function booted(): void
    {
        static::created(function (Justification $j) {
            $j->pendingEvent = new JustificationCreatedEvent($j);
            $j->notifyJustificationObserver();
        });

        static::updated(function (Justification $j) {
            if ($j->wasChanged('status')) {
                $j->pendingEvent = new JustificationStatusChangedEvent($j);
                $j->notifyJustificationObserver();
            }
        });
    }

}
