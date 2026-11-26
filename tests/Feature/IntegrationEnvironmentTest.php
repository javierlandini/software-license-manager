<?php

use App\Models\License;
use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class)->group('integration');

test('integration tests run against the dedicated mysql test database', function () {
    $connection = DB::connection();
    $version = $connection->selectOne('select version() as version')->version;

    $this->note("MySQL {$version} (database `{$connection->getDatabaseName()}`)");

    expect($connection->getDriverName())->toBe('mysql')
        ->and($connection->getDatabaseName())->toEndWith('_testing')
        ->and(License::factory()->create()->exists)->toBeTrue();
});

test('integration tests use redis for the cache', function () {
    $version = Redis::connection('cache')->info('server')['redis_version'] ?? 'unknown';

    $this->note("Redis {$version}");

    Cache::put('integration-check', 'ok', 10);

    expect(Cache::getStore())->toBeInstanceOf(RedisStore::class)
        ->and(Cache::pull('integration-check'))->toBe('ok');
});
