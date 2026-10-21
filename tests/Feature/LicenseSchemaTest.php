<?php

use App\Enums\LicenseEventType;
use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use App\Enums\UserRole;
use App\Models\Activation;
use App\Models\License;
use App\Models\LicenseEvent;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users default to the customer role', function () {
    $user = User::create([
        'name' => 'Jane',
        'email' => 'jane@example.com',
        'password' => 'password',
    ])->fresh();

    expect($user->role)->toBe(UserRole::Customer)
        ->and($user->isCustomer())->toBeTrue()
        ->and(User::factory()->admin()->create()->isAdmin())->toBeTrue()
        ->and(User::factory()->support()->create()->isSupport())->toBeTrue();
});

test('a license resolves its owner, plan, product and orders', function () {
    $license = License::factory()->subscription()->create();
    $order = Order::factory()->paid()->for($license->user)->for($license->plan)->for($license)->create();

    expect($license->type)->toBe(LicenseType::Subscription)
        ->and($license->status)->toBe(LicenseStatus::Active)
        ->and($license->plan->license_type)->toBe(LicenseType::Subscription)
        ->and($license->plan->product->plans->pluck('id'))->toContain($license->plan_id)
        ->and($license->user->licenses->pluck('id'))->toContain($license->id)
        ->and($license->orders->first()->is($order))->toBeTrue()
        ->and($license->max_activations)->toBe($license->plan->max_activations);
});

test('license keys must be unique', function () {
    $license = License::factory()->create();

    License::factory()->create(['key' => $license->key]);
})->throws(QueryException::class);

test('a machine can only be activated once per license', function () {
    $activation = Activation::factory()->create();

    Activation::factory()->for($activation->license)->create([
        'machine_fingerprint' => $activation->machine_fingerprint,
    ]);
})->throws(QueryException::class);

test('only activations without a deactivation date occupy a seat', function () {
    $license = License::factory()->create();
    Activation::factory(2)->for($license)->create();
    Activation::factory()->deactivated()->for($license)->create();

    expect($license->activations()->count())->toBe(3)
        ->and($license->activeActivations()->count())->toBe(2);
});

test('deleting a license removes its activations and events and detaches orders', function () {
    $license = License::factory()->create();
    Activation::factory()->for($license)->create();
    LicenseEvent::factory()->for($license)->create();
    $order = Order::factory()->paid()->for($license)->create();

    $license->delete();

    expect(Activation::count())->toBe(0)
        ->and(LicenseEvent::count())->toBe(0)
        ->and($order->fresh()->license_id)->toBeNull();
});

test('a paid license links back to the trial it was converted from', function () {
    $trial = License::factory()->trial()->create();
    $paid = License::factory()->perpetual()->for($trial->user)->create([
        'converted_from_license_id' => $trial->id,
    ]);

    expect($paid->convertedFrom->is($trial))->toBeTrue()
        ->and($trial->convertedTo->is($paid))->toBeTrue();
});

test('license events record their actor and metadata without an updated timestamp', function () {
    $admin = User::factory()->admin()->create();
    $event = LicenseEvent::factory()->for($admin, 'actor')->create([
        'type' => LicenseEventType::Suspended,
        'metadata' => ['reason' => 'chargeback'],
    ])->fresh();

    expect($event->type)->toBe(LicenseEventType::Suspended)
        ->and($event->metadata)->toBe(['reason' => 'chargeback'])
        ->and($event->actor->is($admin))->toBeTrue()
        ->and($event->created_at)->not->toBeNull();
});

test('a plan cannot be deleted while licenses use it', function () {
    $license = License::factory()->create();

    Plan::find($license->plan_id)->delete();
})->throws(QueryException::class);
