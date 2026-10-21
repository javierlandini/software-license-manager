<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'plan_id',
    'converted_from_license_id',
    'key',
    'type',
    'status',
    'max_activations',
    'starts_at',
    'expires_at',
    'updates_until',
])]
class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LicenseType::class,
            'status' => LicenseStatus::class,
            'max_activations' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'updates_until' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * The trial license this license was converted from.
     *
     * @return BelongsTo<License, $this>
     */
    public function convertedFrom(): BelongsTo
    {
        return $this->belongsTo(License::class, 'converted_from_license_id');
    }

    /**
     * The paid license this trial was converted into.
     *
     * @return HasOne<License, $this>
     */
    public function convertedTo(): HasOne
    {
        return $this->hasOne(License::class, 'converted_from_license_id');
    }

    /**
     * @return HasMany<Activation, $this>
     */
    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    /**
     * Activations that currently occupy a seat.
     *
     * @return HasMany<Activation, $this>
     */
    public function activeActivations(): HasMany
    {
        return $this->activations()->whereNull('deactivated_at');
    }

    /**
     * @return HasMany<LicenseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(LicenseEvent::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
