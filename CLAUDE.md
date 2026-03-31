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

**Claude Code is the orchestrator. It does not build — it delegates.**
For every task, identify the correct agent and spawn it via the Agent tool. Do not write code, create files, or run artisan commands yourself unless no agent covers the domain.

## Agent Delegation (MANDATORY)

**CRITICAL RULE: The main conversation MUST NOT do implementation work directly. ALL implementation MUST be delegated to the specialized agents in `.claude/agents/`.**

### Agent Roster

| Agent | Spawn when task involves |
|---|---|
| `db-architect` | Migrations, Eloquent models, factories, seeders, `CompanyScope`, `ResolveTenant` |
| `calc-engine` | Commission services, DTOs, QVV algorithm, `app/Services/`, `app/Actions/`, `app/DTOs/` |
| `test-suite` | PHPUnit tests in `tests/Feature/Commission/` or `tests/Unit/` |
| `admin-api` | Filament resources, `routes/api.php`, artisan commands, Sanctum auth |
| `admin-ui` | Filament theming, panel aesthetics, admin CSS, visual design of admin panel |
| `frontend-dev` | Blade views, Livewire, Tailwind, affiliate routes, `resources/views/`, `app/Http/Controllers/Affiliate/` |
| `devops` | Laravel Cloud, queue config, scheduled commands, CI/CD, `.env` |
| `documentation` | `.claude/specs/` sync, PHPDoc, README |
| `security-auditor` | Pre-ship security audit (read-only — reports findings, does not fix) |
| `tech-lead` | Pre-QA architectural review (read-only — verdicts: APPROVED / NEEDS REVISION / BLOCKED) |
| `qa-lead` | Final validation gate (read-only — verdicts: SIGNED OFF / BLOCKED) |

### Pre-Ship Pipeline (required for every feature)

1. Spawn `security-auditor` → read its report
2. Spawn `tech-lead` (pass the security report) → wait for verdict
3. If **NEEDS REVISION**: route fix to the named builder agent, then restart from step 1
4. If **APPROVED**: spawn `qa-lead` → wait for verdict
5. If `qa-lead` returns **BLOCKED**: fix the named issue, restart from step 1
6. Only `qa-lead` **SIGNED OFF** = feature is complete

## Orchestration Rules

1. **You are the coordinator, not the coder.** Spawn agents. Review their output. Route follow-up work.
2. **One agent per domain.** Do not ask `calc-engine` to touch migrations. Do not ask `db-architect` to touch services.
3. **Respect sequencing.** `db-architect` must complete before `calc-engine` or `admin-api`. `test-suite` can run in parallel with `calc-engine`.
4. **Always pass context to agents.** Include the feature name, relevant spec file path, and output from predecessor agents.
5. **Gate agents are read-only.** `security-auditor`, `tech-lead`, and `qa-lead` never write code. Route all fixes to builder agents.

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
