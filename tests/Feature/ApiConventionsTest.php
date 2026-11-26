<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/*
 * Assert the production error shape: with debug on (as in a copied `.env.example`), Laravel adds
 * `exception`, `file`, `line` and `trace` to JSON error bodies.
 */
beforeEach(function () {
    config(['app.debug' => false]);
});

/**
 * Register a test-only route under the real v1 prefix and middleware group.
 */
function defineTestApiRoute(string $method, string $uri, Closure $action): void
{
    Route::middleware('api')->prefix('api/v1')->match([$method], $uri, $action);
}

test('ping returns json under the v1 prefix', function () {
    $this->getJson('/api/v1/ping')
        ->assertOk()
        ->assertExactJson(['data' => ['status' => 'ok', 'version' => 'v1']]);

    expect(route('api.v1.ping', absolute: false))->toBe('/api/v1/ping');
});

test('an unknown api route returns a json 404 even without json headers', function (string $uri) {
    $this->get($uri)
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('code', 'not_found')
        ->assertJsonStructure(['message', 'code']);
})->with([
    'under v1' => '/api/v1/does-not-exist',
    'outside v1' => '/api/nope',
]);

test('a validation error returns 422 with message, code and field errors', function () {
    defineTestApiRoute('post', '_test/validation', fn (Request $request) => $request->validate([
        'machine_fingerprint' => ['required', 'string'],
    ]));

    $this->postJson('/api/v1/_test/validation', [])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'validation_failed')
        ->assertJsonStructure(['message', 'code', 'errors' => ['machine_fingerprint']])
        ->assertJsonValidationErrors(['machine_fingerprint']);
});

test('a generic conflict returns 409 with the conflict code', function () {
    defineTestApiRoute('post', '_test/conflict', fn () => abort(409, 'The resource is in a conflicting state.'));

    $this->postJson('/api/v1/_test/conflict')
        ->assertConflict()
        ->assertExactJson([
            'message' => 'The resource is in a conflicting state.',
            'code' => 'conflict',
        ]);
});

test('a domain conflict keeps its own stable code', function () {
    defineTestApiRoute('post', '_test/domain-conflict', function () {
        throw new HttpResponseException(response()->json([
            'message' => 'This machine is already activated for the license.',
            'code' => 'machine_already_activated',
        ], 409));
    });

    $this->postJson('/api/v1/_test/domain-conflict')
        ->assertConflict()
        ->assertExactJson([
            'message' => 'This machine is already activated for the license.',
            'code' => 'machine_already_activated',
        ]);
});

test('json errors outside the api do not get an error code', function () {
    $this->getJson('/does-not-exist')
        ->assertNotFound()
        ->assertJsonMissingPath('code');
});
