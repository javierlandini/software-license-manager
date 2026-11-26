<?php

/*
|--------------------------------------------------------------------------
| Integration Test Bootstrap
|--------------------------------------------------------------------------
|
| Bootstrap for phpunit.integration.xml only. It points the default connection at the dedicated
| DB_TEST_DATABASE provided by compose.yaml (`${DB_DATABASE}_testing`) before any application is
| booted, so RefreshDatabase never touches development data. The real environment wins over
| `.env`, which only fills variables that are not already defined.
|
*/

require __DIR__.'/../vendor/autoload.php';

$testDatabase = getenv('DB_TEST_DATABASE');

if (! is_string($testDatabase) || ! str_ends_with($testDatabase, '_testing')) {
    fwrite(STDERR, 'Integration tests need DB_TEST_DATABASE ending in "_testing" (set by compose.yaml). Run them with `composer test:integration`.'.PHP_EOL);

    exit(1);
}

putenv("DB_DATABASE={$testDatabase}");
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $testDatabase;
