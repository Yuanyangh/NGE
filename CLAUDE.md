# CLAUDE.md — Project Instructions for Claude Code

## Project

TyeUps Network Growth Engine — a parameter-driven compensation engine for affiliate/network marketing businesses, built in Laravel.

## Essential Reading

**Read `PROJECT.md` first.** It contains the complete architecture, database schema, plan configuration, algorithm specs, service contracts, test scenarios, and agent task assignments. That file is the single source of truth. Do not deviate from it.

## Tech Stack

- Laravel 11+ / PHP 8.3+
- MySQL 8
- Filament for admin panel
- `staudenmeir/laravel-adjacency-list` for genealogy tree
- `brick/money` for monetary calculations
- `spatie/laravel-data` for DTOs
- Laravel Sanctum for API auth
- Deployed on Laravel Cloud

## Key Commands

```bash
php artisan migrate:fresh --seed          # Reset DB with SoComm test data
php artisan test                          # Run full test suite
php artisan test --filter=Commission      # Run commission tests only
php artisan commissions:run socomm        # Run daily commissions for SoComm
php artisan wallet:credit socomm          # Run weekly wallet crediting
```

## Architecture Rules

1. **All commission logic reads from PlanConfig DTO.** Never hardcode business rules.
2. **Services are stateless.** They receive data + config, return results. No side effects except the orchestrator.
3. **Immutable movement ledger wallet.** Balance = SUM(wallet_movements.amount). No mutable balance field. This is a single-account movement ledger, not an accounting double-entry system. Every wallet state change (credit, release, clawback, withdrawal) is an append-only movement record. Phase 2 may introduce full double-entry with contra accounts for accounting export.
4. **Multi-tenant.** Every query scoped to company_id. Use CompanyScope global scope.
5. **Immutable ledgers.** Never UPDATE transaction or commission_ledger rows. Corrections = new entries.
6. **Money precision.** Use brick/money or bcmath. Never use float for money.
7. **Idempotent runs.** Same company + same date = same results. Delete old entries before re-running.

## Agent Workflow

When starting a new task, identify which agent role applies (see PROJECT.md Section 9):
- **Agent 1:** Database + Models (migrations, models, factories, seeders)
- **Agent 2:** Calculation Engine (services, DTOs, business logic)
- **Agent 3:** Test Suite (PHPUnit tests for all scenarios)
- **Agent 4:** Admin Panel + API + Commands (Filament, routes, artisan)

Stay within your agent's scope. Do not build services when tasked with models. Do not build UI when tasked with calculations.

## Testing

- Tests live in `tests/Feature/Commission/` and `tests/Unit/DTOs/`
- Use the `CommissionTestCase` base class for all commission tests
- Every test scenario from PROJECT.md Section 8 must have a corresponding test
- Run tests frequently. Never push code that breaks existing tests.

## Code Style

- Follow PSR-12
- Use PHP 8.3+ features (typed properties, enums, match expressions, named arguments)
- Prefer composition over inheritance
- DTOs should be immutable (readonly properties)
- Models use $casts for JSON columns
- Services use constructor injection

## File Organization

```
app/Models/          — Eloquent models only
app/DTOs/            — Data transfer objects (readonly, no logic)
app/Services/        — Business logic (stateless, testable)
app/Actions/         — Single-purpose action classes
app/Console/Commands — Artisan commands
app/Http/            — Controllers, middleware, requests
app/Scopes/          — Global query scopes
app/Exceptions/      — Custom exceptions
```
