<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les logs de notifications
 *
 * @property int $id
 * @property int $notification_id
 * @property string $event
 * @property string|null $details
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class NotificationLog extends Model
{
    use HasFactory;

    /**
     * Types d'événements
     */
    public const EVENT_SENT = 'sent';
    public const EVENT_OPENED = 'opened';
    public const EVENT_CLICKED = 'clicked';
    public const EVENT_FAILED = 'failed';
    public const EVENT_BOUNCED = 'bounced';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'notification_id',
        'event',
        'details',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    /**
     * Relation avec la notification
     *
     * @return BelongsTo<Notification, NotificationLog>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Scope pour filtrer par événement
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $event
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}

