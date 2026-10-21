<?php

namespace App\Models;

use App\Enums\LicenseEventType;
use Database\Factories\LicenseEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['license_id', 'actor_id', 'type', 'metadata'])]
class LicenseEvent extends Model
{
    /** @use HasFactory<LicenseEventFactory> */
    use HasFactory;

    /**
     * License events are append-only and never updated.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LicenseEventType::class,
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<License, $this>
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * The user who triggered the event, or null for system/machine events.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
