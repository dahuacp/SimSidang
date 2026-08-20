# AGENTS.md — SIMSIDANG

Sistem Manajemen Sidang Akademik. Laravel 13 / PHP 8.4 / MySQL 8 / Vite (Tailwind CSS v4 + Alpine.js + ApexCharts). Auth via Fortify, login by `username` (NIM/NIDN). RBAC via Gate (`mahasiswa`, `dosen`, `admin`). Frontend = ported TailAdmin template (Tailwind, not static assets).

> All project docs live in `docs/`. Full detail: see individual files.

## Commands

```bash
# Setup
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed

# Dev — run both, in parallel
php artisan serve        # terminal 1
npm run dev             # terminal 2 — Vite HMR for resources/css + resources/js

# Before marking task done (MANDATORY)
php artisan test         # → ./vendor/bin/pest also works
npm run lint
./vendor/bin/pint        # PSR-12 (Laravel Pint)

# NOTE: pdo_sqlite is NOT installed on the dev box, so the default `php artisan test`
# (DB_CONNECTION=sqlite :memory:) fails. Run the suite against MySQL instead:
vendor/bin/pest --configuration phpunit.mysql.xml tests/Feature
# WARNING: that wipes the `simsidang` dev DB (RefreshDatabase). Backup first:
# mysqldump -usimsidang -psimsidang simsidang > /tmp/opencode/simsidang_backup.sql
# Then restore afterwards: php artisan migrate:fresh --seed
```

## Critical constraints (agents miss these without help)

- **Memory vault protocol** — read `docs/MEMORY.md` at session start. Update it (status + log entry) before session end. Log entries are append-only (newest first); status section is a snapshot.
- **Assistant is read-only** (FR-05, Tahap 3) — never give DB write access. LLM gets aggregated query results only, not raw rows.
- **All form input** → Form Request (validation centralized). File uploads: submission PDF ≤10MB; attachments (.pdf/.docx/.jpeg/.png) ≤5MB each. Error messages in Bahasa Indonesia.
- **No email-based auth** — Fortify must be configured for `username` login. Default Laravel scaffolding uses email — override it.
- **Porting TailAdmin** — clone to `_reference/tailadmin-laravel/` and `_reference/tailadmin-react/` (gitignored, read-only). Don't copy-paste `.html` directly. Follow `docs/FRONTEND-GUIDE.md` checklist: extract common layout → Blade components, wire Vite directives, replace dummy data with Blade vars.
- **Theme** — indigo accent `#6366f1` mapped to `brand-500` in `resources/css/app.css` (`@theme` block). Only that file's theme tokens may be freely changed for theming.
- **Naming** — `GLOSSARY.md` is the single source of truth. DB columns = Bahasa Indonesia snake_case (`catatan_revisi`, `status_poin`). PHP classes/routes = English (`RevisionNote`). Add new domain terms to glossary before coding.
- **Roadmap order** — `docs/ROADMAP.md` Tahap 1 first (core submission→revisi loop). Don't skip to Tahap 2/3 features unless asked.
- **Folder layout** — controllers grouped by role (`app/Http/Controllers/Mahasiswa/`, `Dosen/`, `Admin/`). `resources/css/app.css` and `resources/js/components/` are ported from TailAdmin — change cautiously and log in MEMORY.md.

## Gotchas

- `PRD-SIMSIDANG-v2.md` is referenced by AGENTS.md, ARCHITECTURE.md, FRONTEND-GUIDE.md, and ROADMAP.md but **does not exist yet**. Treat ROADMAP.md + SCHEMA.md + GLOSSARY.md as the spec until it's written.
- Currently docs-only — no Laravel project scaffolded yet. First task: scaffold Laravel 13 + Fortify per `docs/ROADMAP.md` Tahap 1, item 1.
- TailAdmin is a standalone Vite project. Do **not** `npm install` it directly at repo root — port assets into Laravel's `resources/` per FRONTEND-GUIDE.md.
- Submission files stored on `FILESYSTEM_DISK=local` (not public). Ensure `storage/app` writable (`chmod -R 775 storage`).
- Eager-load relations when listing submissions: `Submission::with(['user', 'revisionNotes'])`.

## Quick refs

| Doc | Purpose |
|---|---|
| `docs/ROADMAP.md` | Feature priority order (Tahap 1 → 3) |
| `docs/SCHEMA.md` | DB schema, migration order |
| `docs/ARCHITECTURE.md` | Folder structure, request flow |
| `docs/FRONTEND-GUIDE.md` | TailAdmin→Blade porting checklist |
| `docs/CODING-STANDARDS.md` | PSR-12, test structure, commit format |
| `docs/GLOSSARY.md` | Domain term → code naming |
| `docs/SETUP.md` | Environment setup, .env vars |
| `docs/MEMORY.md` | Cross-session memory vault |

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

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

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

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

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

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

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
