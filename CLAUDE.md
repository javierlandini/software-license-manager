# Software License Manager

Laravel backend to sell and manage software licenses: product/plan catalog, license issuing,
per-machine activation control (seats), payment orders, and a per-license audit log.

**Current state:** only the data layer exists (models, enums, migrations, factories, seeder,
schema tests). No business routes, controllers, API, authentication or payment gateway yet.
The product is a **pure JSON API** consumed by the client software; an admin panel is planned
but its technology is undecided — don't add a UI stack without asking.
`bootstrap/app.php` already renders JSON errors for `api/*`; `routes/api.php` does not exist yet.

## Domain model

```
Product 1─* Plan 1─* License 1─* Activation
              │         ├─* LicenseEvent (append-only)
              └─* Order *─1 (License, optional)
User (role: admin | support | customer) 1─* License, 1─* Order
```

- **Plan** sets the license type (`LicenseType`: perpetual | subscription | trial), price,
  `billing_interval` (subscription only), `trial_days`, `updates_months`, `max_activations`.
- **License**: unique `key` in `XXXXX-XXXXX-XXXXX-XXXXX` format, `status` (`LicenseStatus`),
  copies `max_activations` from its plan, `expires_at` (null = perpetual), `updates_until`.
  A trial converted to paid is linked via `converted_from_license_id` (`convertedFrom`/`convertedTo`).
- **Activation**: one per (license, `machine_fingerprint`). Occupies a seat while
  `deactivated_at` is null → use `License::activeActivations()`.
- **LicenseEvent**: append-only audit log (`UPDATED_AT = null`). Null `actor_id` = system event.
- **Order**: `amount_cents` + `currency`, unique `external_reference` (payment provider id).

Enums live in `app/Enums/` (string-backed): `LicenseType`, `LicenseStatus`, `LicenseEventType`,
`OrderStatus`, `BillingInterval`, `UserRole`.

## Stack

PHP 8.5 · Laravel 13 · Pest 5 · Pint · Vite 8 + Tailwind 4 · MySQL 8.4 + Redis 8 (Docker) ·
in-memory SQLite for tests (plus an `integration` group on real MySQL + Redis) · Laravel Boost (MCP server in `.mcp.json`).

## Commands

Docker is the canonical dev environment (`compose.yaml`: php-fpm `app`, nginx on
`localhost:8000`, mysql, redis). `.env` points to `DB_HOST=mysql` / `REDIS_HOST=redis`, so
anything touching the DB or Redis runs **inside the `app` container**. The image has no Node:
run `npm` on the host.

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
npm install && npm run build                     # host; `npm run dev` for HMR
```

Tests (in-memory SQLite via `phpunit.xml`, work on host or in the container; the `integration`
group is excluded):

```bash
php artisan test --compact
php artisan test --compact tests/Feature/LicenseSchemaTest.php
php artisan test --compact --filter="license keys must be unique"
```

Integration tests (Pest group `integration`, config `phpunit.integration.xml`) run against the
real MySQL and Redis services, inside the `app` container. They use a separate
`${DB_DATABASE}_testing` database (created by the `mysql-test-database` compose job on every
`docker compose up`) and Redis DBs 14/15, so dev data is never touched;
`tests/integration-bootstrap.php` aborts if `DB_TEST_DATABASE` is missing or doesn't end in
`_testing`. Mark a test file with `uses(RefreshDatabase::class)->group('integration');` for
anything that needs MySQL behaviour (`FOR UPDATE`, locks) or Redis. CI runs this group in
`.github/workflows/integration.yml`.

```bash
docker compose up -d               # once, creates the test database
composer test:integration          # host; runs the group inside the app container
```

Formatting (no `pint.json` → default Laravel preset):

```bash
vendor/bin/pint --dirty --format agent
```

Quality scripts (Larastan config in `phpstan.neon`, **level 3**, no baseline; analyses `app/`,
`bootstrap/app.php`, `config/`, `database/`, `routes/` — not `tests/`):

```bash
composer lint       # Pint, fixes style
composer analyse    # Larastan
composer check      # Pint --test + Larastan + tests; run before opening a PR
```

Raising the level needs code changes first: level 4 flags enum-cast attributes
(`User::$role`, `Plan::$license_type`) as `string`.

CI (`.github/workflows/`) covers the same steps as `composer check` as separate jobs (tests, Pint,
Larastan) but does **not** run `npm run build`: check it locally before a PR.

Seeder creates `admin@example.com`, `support@example.com`, 5 customers, 2 products × 4 plans.

## Conventions (deduced from the code)

- Models: `#[Fillable([...])]` / `#[Hidden([...])]` attributes (not `$fillable`), `casts()` method,
  enum casts, relations with generic PHPDoc (`@return HasMany<License, $this>`).
- Money is always integer cents (`*_cents`) + `currency` char(3). Never floats.
- Enums are stored as `string` columns, not DB `enum` types.
- Foreign-key delete policies are deliberate — follow them in new migrations:
  `restrictOnDelete` for users/plans referenced by licenses and orders;
  `cascadeOnDelete` for activations/license_events; `nullOnDelete` for order→license and trial→paid.
- Factories have one state per business variant (`perpetual()`, `subscription()`, `trial()`,
  `suspended()`, `expired()`, `paid()`, `admin()`, `deactivated()`…). Use them instead of
  hand-built attributes.
- Pest tests in `tests/Feature`, lowercase names describing the rule
  (`test('a machine can only be activated once per license', …)`), `uses(RefreshDatabase::class)`
  per file (it is commented out in `tests/Pest.php`).
- Code, names, comments, commits and docs in English.

## Git

- Branches `feature/<kebab-case>` → PR to `main` → merge commit.
- Commit messages: imperative, one sentence ending with a period
  (`Add core models, migrations and factories.`).
- Never commit or push unless asked.

## Sensitive areas (ask before changing)

- **Migrations**: there is no production data yet (system in development), so editing existing
  migrations is acceptable for now — but say so explicitly and expect `migrate:fresh`. Once there
  is a production deploy, only add new migrations.
- **`LicenseEvent` is append-only**: never update/delete events; record a new one.
- **`License.key` format** and uniqueness: consumed by third-party client software.
- **FK / delete policies** (see Conventions): they decide what data gets lost.
- **Amounts and currency** on `Plan`/`Order`.
- **Dependencies** (`composer.json`, `package.json`, lockfiles): no additions/upgrades without approval.
- **The Laravel Boost guidelines block** below: regenerated by `boost:update`; edit outside it and
  never write its opening tag literally above it (Boost matches from the first occurrence).
- `compose.yaml`, `docker/`, `.github/workflows/`, `.env` (never commit `.env`).

## Open questions

<!-- Not deducible from the code. Remove each item once answered. -->
- Admin panel technology (Blade, Livewire, Filament, separate SPA…)?
- Activation API auth: license key, per-machine token, Sanctum?
- Expiration: is there a grace period? What moves `status` to `expired` (scheduler)?
- Does an expired/suspended license free its seats? Can the client deactivate a machine?
- Trials: on conversion, is the trial `revoked`/`expired`? Are activations carried over?
- Refund/chargeback: does a `refunded` order revoke or suspend the license?
- `support` role: what can it do compared to `admin`?
- Payment provider (Stripe, Paddle, MercadoPago…) and webhooks?
- Production key generation: today only `LicenseFactory::generateKey()` exists (tests only).
- Deploy target: Laravel Cloud (`boost.json` has `"cloud": true`)? Environments?

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
