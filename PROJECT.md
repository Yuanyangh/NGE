# TyeUps Network Growth Engine — Phase 1

## PROJECT CONSTITUTION

This document is the single source of truth for building Phase 1 of the TyeUps Network Growth Engine. Every agent session should read this file before starting work. Do not deviate from the architecture defined here without explicit approval.

---

## 1. PRODUCT OVERVIEW

**What we're building:** A parameter-driven compensation engine that calculates affiliate and viral commissions for network marketing businesses. The engine reads all business rules from configuration — no plan logic is hardcoded.

**First tenant:** SoComm Affiliate Rewards Program (plan config included below).

**B2B model:** Each company gets its own tenant with its own compensation plan configuration. Onboarding a new company = creating a new plan config, not writing new code.

**Tech stack:** Laravel 11+, PHP 8.3+, MySQL 8, deployed on Laravel Cloud.

**What Phase 1 is NOT:** No affiliate-facing dashboard. No Monte Carlo simulator. No fraud detection. No notification engine. No referral link management. No Plan Builder UI. Those are Phase 2+.

---

## 2. ARCHITECTURE PRINCIPLES

1. **Config-driven:** All commission logic reads from a versioned JSON plan config. Zero hardcoded business rules.
2. **Idempotent calculations:** Running commissions for the same date twice produces identical results.
3. **Immutable movement ledger wallet:** Wallet balances are derived from SUM(wallet_movements.amount), never stored as mutable fields. This is a single-account immutable movement ledger — every state change (commission credit, release, clawback, withdrawal) is an append-only record. This is NOT a full accounting double-entry system with contra accounts; that's Phase 2 for accounting/ERP export.
4. **Multi-tenant from day one:** Every model is scoped to a `company_id`. Middleware resolves tenant context.
5. **Testable services:** Calculation services are stateless. They receive config DTOs + data, return results. No database calls inside calculators.
6. **Immutable ledgers:** Transaction and commission ledger entries are append-only. Corrections are new entries, not updates.

---

## 3. DIRECTORY STRUCTURE

```
app/
├── Models/
│   ├── Company.php
│   ├── User.php
│   ├── GenealogyNode.php
│   ├── Transaction.php
│   ├── CompensationPlan.php
│   ├── CommissionRun.php
│   ├── CommissionLedgerEntry.php
│   ├── WalletAccount.php
│   └── WalletMovement.php
│
├── DTOs/
│   ├── PlanConfig.php                    # Hydrated from JSON plan config
│   ├── AffiliateCommissionTier.php
│   ├── ViralCommissionTier.php
│   ├── QualificationResult.php
│   ├── CommissionResult.php
│   └── VolumeSnapshot.php
│
├── Services/
│   └── Commission/
│       ├── CommissionRunOrchestrator.php  # Coordinates a full daily run
│       ├── QualificationEvaluator.php    # Determines affiliate qualification status
│       ├── DirectCommissionCalculator.php # Calculates affiliate (direct) commissions
│       ├── ViralCommissionCalculator.php  # Calculates viral (network) commissions
│       ├── LegAggregator.php             # Computes leg volumes, large/small leg logic
│       ├── QvvCalculator.php             # Qualifying Viral Volume algorithm
│       ├── CapEnforcer.php               # Applies global and viral commission caps
│       └── WalletCreditService.php       # Credits wallet from approved commissions
│
├── Actions/
│   ├── RunDailyCommissions.php           # Artisan-callable action
│   ├── CreditWeeklyWallet.php            # Weekly wallet release
│   └── RecalculateCommissionRun.php      # Replay a historical run
│
├── Console/Commands/
│   ├── RunCommissionsCommand.php         # php artisan commissions:run
│   └── CreditWalletCommand.php           # php artisan wallet:credit
│
├── Http/
│   ├── Middleware/
│   │   └── ResolveTenant.php
│   └── Controllers/Api/
│       ├── CompanyController.php
│       ├── CompensationPlanController.php
│       ├── CommissionRunController.php
│       └── WalletController.php
│
└── Scopes/
    └── CompanyScope.php                  # Global scope for tenant isolation
```

---

## 4. DATABASE SCHEMA

### companies
```sql
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    timezone VARCHAR(50) NOT NULL DEFAULT 'UTC',
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### users
```sql
-- Extends Laravel's default users table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'affiliate', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    enrolled_at TIMESTAMP NULL,
    last_order_at TIMESTAMP NULL,
    last_reward_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY users_company_email_unique (company_id, email),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

### genealogy_nodes
Uses closure table pattern for fast subtree queries. Install `staudenmeir/laravel-adjacency-list`.

```sql
CREATE TABLE genealogy_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    sponsor_id BIGINT UNSIGNED NULL,          -- Direct referrer (parent in enrollment tree)
    position INT UNSIGNED NULL,               -- Optional: for placement tree ordering
    depth INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (sponsor_id) REFERENCES genealogy_nodes(id),
    INDEX idx_genealogy_company_sponsor (company_id, sponsor_id)
);
```

Closure table (auto-managed by the package):
```sql
CREATE TABLE genealogy_node_closure (
    ancestor_id BIGINT UNSIGNED NOT NULL,
    descendant_id BIGINT UNSIGNED NOT NULL,
    depth INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (ancestor_id, descendant_id),
    FOREIGN KEY (ancestor_id) REFERENCES genealogy_nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (descendant_id) REFERENCES genealogy_nodes(id) ON DELETE CASCADE
);
```

### transactions
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,              -- Who made the purchase
    referred_by_user_id BIGINT UNSIGNED NULL,      -- Sponsor who gets credit
    type ENUM('purchase', 'smartship', 'refund', 'adjustment') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    xp DECIMAL(12,2) NOT NULL,                     -- Volume / experience points
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    status ENUM('pending', 'confirmed', 'reversed') NOT NULL DEFAULT 'confirmed',
    qualifies_for_commission BOOLEAN NOT NULL DEFAULT TRUE,
    transaction_date DATE NOT NULL,
    reference VARCHAR(255) NULL,                   -- External order ID etc.
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_transactions_company_date (company_id, transaction_date),
    INDEX idx_transactions_user_date (user_id, transaction_date),
    INDEX idx_transactions_referred_date (referred_by_user_id, transaction_date),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (referred_by_user_id) REFERENCES users(id)
);
```

### compensation_plans
```sql
CREATE TABLE compensation_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(20) NOT NULL,
    config JSON NOT NULL,                          -- Full plan configuration
    effective_from DATE NOT NULL,
    effective_until DATE NULL,                     -- NULL = currently active
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_plans_company_active (company_id, is_active, effective_from),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

### commission_runs
```sql
CREATE TABLE commission_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    compensation_plan_id BIGINT UNSIGNED NOT NULL,
    run_date DATE NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    total_affiliate_commission DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_viral_commission DECIMAL(14,2) NOT NULL DEFAULT 0,
    total_company_volume DECIMAL(14,2) NOT NULL DEFAULT 0,
    viral_cap_triggered BOOLEAN NOT NULL DEFAULT FALSE,
    viral_cap_reduction_pct DECIMAL(8,4) NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY idx_runs_company_date (company_id, run_date),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (compensation_plan_id) REFERENCES compensation_plans(id)
);
```

### commission_ledger_entries
```sql
CREATE TABLE commission_ledger_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    commission_run_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('affiliate_commission', 'viral_commission', 'cap_adjustment', 'manual_adjustment') NOT NULL,
    amount DECIMAL(12,4) NOT NULL,                 -- Can be negative for adjustments
    tier_achieved INT UNSIGNED NULL,
    qualification_snapshot JSON NULL,               -- Snapshot of why this was earned
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_ledger_run (commission_run_id),
    INDEX idx_ledger_user (user_id, type, created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (commission_run_id) REFERENCES commission_runs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### wallet_accounts
```sql
CREATE TABLE wallet_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
-- Balance is ALWAYS derived: SUM(wallet_movements.amount) WHERE wallet_account_id = X
-- NEVER store a mutable balance field.
```

### wallet_movements
```sql
CREATE TABLE wallet_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    wallet_account_id BIGINT UNSIGNED NOT NULL,
    type ENUM('commission_credit', 'commission_release', 'withdrawal', 'clawback', 'hold', 'adjustment') NOT NULL,
    amount DECIMAL(12,4) NOT NULL,                 -- Positive = credit, negative = debit
    status ENUM('pending', 'approved', 'released', 'held', 'reversed') NOT NULL DEFAULT 'pending',
    reference_type VARCHAR(100) NULL,              -- e.g. 'commission_ledger_entry'
    reference_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    effective_at TIMESTAMP NOT NULL,               -- When this movement takes effect
    created_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (wallet_account_id) REFERENCES wallet_accounts(id),
    INDEX idx_wallet_movements_account (wallet_account_id, status, effective_at)
);
```

---

## 5. SOCOMM PLAN CONFIGURATION (FIRST TENANT)

This JSON is the canonical SoComm plan config that gets stored in `compensation_plans.config`.

```json
{
    "plan": {
        "name": "SoComm Affiliate Rewards Program",
        "version": "1.0",
        "effective_date": "2026-01-01",
        "currency": "USD",
        "calculation_frequency": "daily",
        "credit_frequency": "weekly",
        "day_definition": {
            "start": "00:00:00",
            "end": "23:59:59",
            "timezone": "UTC"
        }
    },

    "qualification": {
        "rolling_days": 30,
        "active_customer_min_order_xp": 20,
        "active_customer_threshold_type": "per_order",
        "affiliate_inactivity_downgrade_months": 12,
        "affiliate_inactivity_requires_no_orders": true,
        "affiliate_inactivity_requires_no_rewards": true
    },

    "affiliate_commission": {
        "type": "tiered_percentage",
        "payout_method": "daily_new_volume",
        "basis": "referred_volume_30d",
        "customer_basis": "referred_active_customers_30d",
        "self_purchase_earns_commission": false,
        "includes_smartship": true,
        "tiers": [
            {"min_active_customers": 1, "min_referred_volume": 0,    "rate": 0.10},
            {"min_active_customers": 2, "min_referred_volume": 200,  "rate": 0.11},
            {"min_active_customers": 2, "min_referred_volume": 400,  "rate": 0.12},
            {"min_active_customers": 3, "min_referred_volume": 600,  "rate": 0.13},
            {"min_active_customers": 4, "min_referred_volume": 800,  "rate": 0.14},
            {"min_active_customers": 5, "min_referred_volume": 1000, "rate": 0.15},
            {"min_active_customers": 6, "min_referred_volume": 1200, "rate": 0.16},
            {"min_active_customers": 7, "min_referred_volume": 1400, "rate": 0.17},
            {"min_active_customers": 8, "min_referred_volume": 1600, "rate": 0.18},
            {"min_active_customers": 9, "min_referred_volume": 1800, "rate": 0.19},
            {"min_active_customers": 10,"min_referred_volume": 2000, "rate": 0.20}
        ]
    },

    "viral_commission": {
        "type": "tiered_fixed_daily",
        "basis": "qualifying_viral_volume_30d",
        "tree": "enrollment",
        "qvv_algorithm": {
            "description": "Large leg cap with 2/3 small leg benchmark",
            "steps": [
                "1. Sum all leg volumes: Viral Volume = Leg1 + Leg2 + Leg3 + ...",
                "2. Identify Large Leg (L) = leg with highest volume (ties: pick one, rest are small)",
                "3. Sum all Small Legs: Y = s1 + s2 + s3 + ...",
                "4. Compute benchmark: X = (2/3) * Y",
                "5. If X >= L: no cap needed, use L as-is",
                "6. If X < L: cap L to X (large leg capped = X)",
                "7. Qualifying Viral Volume = capped_L + Y"
            ]
        },
        "tiers": [
            {"tier": 1,  "min_active_customers": 2, "min_referred_volume": 50,   "min_qvv": 100,       "daily_reward": 0.53},
            {"tier": 2,  "min_active_customers": 2, "min_referred_volume": 100,  "min_qvv": 250,       "daily_reward": 1.33},
            {"tier": 3,  "min_active_customers": 2, "min_referred_volume": 100,  "min_qvv": 500,       "daily_reward": 2.67},
            {"tier": 4,  "min_active_customers": 2, "min_referred_volume": 100,  "min_qvv": 750,       "daily_reward": 4.00},
            {"tier": 5,  "min_active_customers": 2, "min_referred_volume": 100,  "min_qvv": 1000,      "daily_reward": 5.00},
            {"tier": 6,  "min_active_customers": 2, "min_referred_volume": 150,  "min_qvv": 1500,      "daily_reward": 7.50},
            {"tier": 7,  "min_active_customers": 2, "min_referred_volume": 150,  "min_qvv": 2000,      "daily_reward": 10.00},
            {"tier": 8,  "min_active_customers": 2, "min_referred_volume": 150,  "min_qvv": 2500,      "daily_reward": 12.50},
            {"tier": 9,  "min_active_customers": 2, "min_referred_volume": 150,  "min_qvv": 3500,      "daily_reward": 17.50},
            {"tier": 10, "min_active_customers": 2, "min_referred_volume": 200,  "min_qvv": 5000,      "daily_reward": 23.33},
            {"tier": 11, "min_active_customers": 2, "min_referred_volume": 200,  "min_qvv": 7000,      "daily_reward": 32.67},
            {"tier": 12, "min_active_customers": 2, "min_referred_volume": 200,  "min_qvv": 9000,      "daily_reward": 42.00},
            {"tier": 13, "min_active_customers": 2, "min_referred_volume": 200,  "min_qvv": 12000,     "daily_reward": 52.00},
            {"tier": 14, "min_active_customers": 2, "min_referred_volume": 200,  "min_qvv": 15000,     "daily_reward": 60.00},
            {"tier": 15, "min_active_customers": 2, "min_referred_volume": 300,  "min_qvv": 20000,     "daily_reward": 73.33},
            {"tier": 16, "min_active_customers": 2, "min_referred_volume": 300,  "min_qvv": 25000,     "daily_reward": 83.33},
            {"tier": 17, "min_active_customers": 2, "min_referred_volume": 300,  "min_qvv": 30000,     "daily_reward": 90.00},
            {"tier": 18, "min_active_customers": 2, "min_referred_volume": 400,  "min_qvv": 40000,     "daily_reward": 106.67},
            {"tier": 19, "min_active_customers": 2, "min_referred_volume": 400,  "min_qvv": 50000,     "daily_reward": 125.00},
            {"tier": 20, "min_active_customers": 3, "min_referred_volume": 500,  "min_qvv": 60000,     "daily_reward": 140.00},
            {"tier": 21, "min_active_customers": 3, "min_referred_volume": 500,  "min_qvv": 80000,     "daily_reward": 186.67},
            {"tier": 22, "min_active_customers": 3, "min_referred_volume": 500,  "min_qvv": 120000,    "daily_reward": 260.00},
            {"tier": 23, "min_active_customers": 3, "min_referred_volume": 600,  "min_qvv": 160000,    "daily_reward": 320.00},
            {"tier": 24, "min_active_customers": 3, "min_referred_volume": 600,  "min_qvv": 240000,    "daily_reward": 480.00},
            {"tier": 25, "min_active_customers": 3, "min_referred_volume": 600,  "min_qvv": 320000,    "daily_reward": 640.00},
            {"tier": 26, "min_active_customers": 4, "min_referred_volume": 800,  "min_qvv": 400000,    "daily_reward": 800.00},
            {"tier": 27, "min_active_customers": 4, "min_referred_volume": 800,  "min_qvv": 500000,    "daily_reward": 1000.00},
            {"tier": 28, "min_active_customers": 4, "min_referred_volume": 800,  "min_qvv": 600000,    "daily_reward": 1200.00},
            {"tier": 29, "min_active_customers": 4, "min_referred_volume": 1000, "min_qvv": 700000,    "daily_reward": 1400.00},
            {"tier": 30, "min_active_customers": 4, "min_referred_volume": 1000, "min_qvv": 800000,    "daily_reward": 1600.00},
            {"tier": 31, "min_active_customers": 5, "min_referred_volume": 1000, "min_qvv": 1000000,   "daily_reward": 2000.00},
            {"tier": 32, "min_active_customers": 5, "min_referred_volume": 1200, "min_qvv": 1200000,   "daily_reward": 2400.00},
            {"tier": 33, "min_active_customers": 5, "min_referred_volume": 1200, "min_qvv": 1400000,   "daily_reward": 2800.00},
            {"tier": 34, "min_active_customers": 5, "min_referred_volume": 1200, "min_qvv": 1700000,   "daily_reward": 2975.00},
            {"tier": 35, "min_active_customers": 5, "min_referred_volume": 1300, "min_qvv": 2000000,   "daily_reward": 3200.00},
            {"tier": 36, "min_active_customers": 5, "min_referred_volume": 1300, "min_qvv": 2300000,   "daily_reward": 3526.67},
            {"tier": 37, "min_active_customers": 5, "min_referred_volume": 1300, "min_qvv": 2600000,   "daily_reward": 3871.97},
            {"tier": 38, "min_active_customers": 6, "min_referred_volume": 1400, "min_qvv": 3000000,   "daily_reward": 4467.63},
            {"tier": 39, "min_active_customers": 6, "min_referred_volume": 1400, "min_qvv": 3400000,   "daily_reward": 5063.33},
            {"tier": 40, "min_active_customers": 6, "min_referred_volume": 1400, "min_qvv": 3800000,   "daily_reward": 5425.00},
            {"tier": 41, "min_active_customers": 6, "min_referred_volume": 1500, "min_qvv": 4200000,   "daily_reward": 5786.67},
            {"tier": 42, "min_active_customers": 6, "min_referred_volume": 1500, "min_qvv": 4600000,   "daily_reward": 6148.33},
            {"tier": 43, "min_active_customers": 6, "min_referred_volume": 1500, "min_qvv": 5000000,   "daily_reward": 6510.00},
            {"tier": 44, "min_active_customers": 6, "min_referred_volume": 1700, "min_qvv": 5500000,   "daily_reward": 6871.67},
            {"tier": 45, "min_active_customers": 7, "min_referred_volume": 1700, "min_qvv": 6000000,   "daily_reward": 7233.33},
            {"tier": 46, "min_active_customers": 7, "min_referred_volume": 1700, "min_qvv": 6500000,   "daily_reward": 7483.33},
            {"tier": 47, "min_active_customers": 7, "min_referred_volume": 1800, "min_qvv": 7000000,   "daily_reward": 7706.67},
            {"tier": 48, "min_active_customers": 7, "min_referred_volume": 1800, "min_qvv": 7500000,   "daily_reward": 7966.67},
            {"tier": 49, "min_active_customers": 7, "min_referred_volume": 1800, "min_qvv": 8000000,   "daily_reward": 8060.00},
            {"tier": 50, "min_active_customers": 7, "min_referred_volume": 2000, "min_qvv": 8500000,   "daily_reward": 8395.83},
            {"tier": 51, "min_active_customers": 8, "min_referred_volume": 2000, "min_qvv": 9000000,   "daily_reward": 8731.67},
            {"tier": 52, "min_active_customers": 8, "min_referred_volume": 2000, "min_qvv": 9500000,   "daily_reward": 9067.50},
            {"tier": 53, "min_active_customers": 8, "min_referred_volume": 2100, "min_qvv": 10000000,  "daily_reward": 9403.33},
            {"tier": 54, "min_active_customers": 8, "min_referred_volume": 2100, "min_qvv": 10500000,  "daily_reward": 9739.17},
            {"tier": 55, "min_active_customers": 8, "min_referred_volume": 2100, "min_qvv": 11000000,  "daily_reward": 10075.00},
            {"tier": 56, "min_active_customers": 8, "min_referred_volume": 2200, "min_qvv": 11500000,  "daily_reward": 10410.83},
            {"tier": 57, "min_active_customers": 8, "min_referred_volume": 2200, "min_qvv": 12000000,  "daily_reward": 10746.67},
            {"tier": 58, "min_active_customers": 8, "min_referred_volume": 2200, "min_qvv": 12500000,  "daily_reward": 11082.50},
            {"tier": 59, "min_active_customers": 8, "min_referred_volume": 2300, "min_qvv": 13000000,  "daily_reward": 11418.33},
            {"tier": 60, "min_active_customers": 8, "min_referred_volume": 2300, "min_qvv": 13500000,  "daily_reward": 11754.17},
            {"tier": 61, "min_active_customers": 8, "min_referred_volume": 2300, "min_qvv": 14000000,  "daily_reward": 12090.00},
            {"tier": 62, "min_active_customers": 8, "min_referred_volume": 2400, "min_qvv": 14500000,  "daily_reward": 12425.83},
            {"tier": 63, "min_active_customers": 8, "min_referred_volume": 2400, "min_qvv": 15000000,  "daily_reward": 12761.67},
            {"tier": 64, "min_active_customers": 8, "min_referred_volume": 2400, "min_qvv": 15500000,  "daily_reward": 13097.50},
            {"tier": 65, "min_active_customers": 8, "min_referred_volume": 2400, "min_qvv": 16000000,  "daily_reward": 13433.33},
            {"tier": 66, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 16500000,  "daily_reward": 13769.17},
            {"tier": 67, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 17000000,  "daily_reward": 14105.00},
            {"tier": 68, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 17500000,  "daily_reward": 14440.83},
            {"tier": 69, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 18000000,  "daily_reward": 14776.67},
            {"tier": 70, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 18500000,  "daily_reward": 15112.50},
            {"tier": 71, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 19000000,  "daily_reward": 15448.33},
            {"tier": 72, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 19500000,  "daily_reward": 15784.17},
            {"tier": 73, "min_active_customers": 8, "min_referred_volume": 2500, "min_qvv": 20000000,  "daily_reward": 16120.00}
        ]
    },

    "caps": {
        "total_payout_cap_percent": 0.35,
        "total_payout_cap_enforcement": "proportional_reduction",
        "total_payout_cap_window": "rolling_30d",
        "viral_commission_cap": {
            "percent_of_company_volume": 0.15,
            "window": "rolling_30d",
            "enforcement": "daily_reduction",
            "reduction_method": "proportional_overage",
            "description": "If rolling 30-day viral commissions exceed 15% of rolling 30-day company volume, reduce all viral payouts for that day by the overage percentage"
        },
        "enforcement_order": ["viral_cap_first", "then_global_cap"]
    },

    "wallet": {
        "credit_timing": "weekly",
        "release_delay_days": 0,
        "minimum_withdrawal": 0,
        "clawback_window_days": 30
    }
}
```

---

## 6. CORE ALGORITHM SPECIFICATIONS

### 6.1 Rolling 30-Day Window

For any calculation date D:
- Window = D minus 29 days through D (inclusive, 30 days total)
- All volume and customer counts use this window
- Transactions with `qualifies_for_commission = true` and `status = confirmed` only

### 6.2 Active Customer Count

An Active Customer for affiliate A on date D is a user who:
1. Has `referred_by_user_id = A.user_id` in their transactions
2. Has at least one confirmed transaction within the 30-day window
3. That transaction has `xp >= plan.qualification.active_customer_min_order_xp` (20 XP for SoComm)
4. The user can be either a customer OR an affiliate (both count)

### 6.3 Referred Volume (30-Day)

Sum of `xp` from all confirmed, qualifying transactions where `referred_by_user_id = A.user_id` within the 30-day window.

### 6.4 Affiliate Commission Calculation

```
Input: affiliate A, date D, plan config

1. Count referred active customers in 30-day window → customer_count
2. Sum referred volume in 30-day window → referred_volume
3. Find highest matching tier where:
   - customer_count >= tier.min_active_customers
   - referred_volume >= tier.min_referred_volume
4. If no tier matches: commission = 0
5. If tier matches:
   a. Get today's NEW referred volume only (transactions on date D where
      referred_by_user_id = A.user_id, confirmed, qualifying)
   b. commission = today_new_volume * tier.rate

KEY PRINCIPLE: The rolling 30-day metrics determine the TIER (percentage).
The actual daily payout applies that tier rate to TODAY'S new volume only.
This prevents 30x overpayment from applying the rate to the full rolling window daily.

Example:
  - Rolling 30-day: 5 active customers, 1200 XP referred volume → tier = 16%
  - Today's new referred transactions: 40 XP
  - Today's affiliate commission: 40 * 0.16 = $6.40
```

**RESOLVED:** The tier is determined by rolling 30-day metrics. The daily payout applies that tier rate to that day's new qualifying volume only. This is the industry-standard approach and prevents overpayment.

### 6.5 Qualifying Viral Volume (QVV) Algorithm

This is the most critical algorithm. Implemented in `QvvCalculator.php`.

```
Input: affiliate A, date D, plan config

1. Get all direct referrals (children) of A in the genealogy tree.
   Each child and their entire downline = one "leg."

2. For each leg, compute Leg Viral Volume:
   Sum of all XP from confirmed qualifying transactions by ALL users
   in that leg's subtree within the 30-day window.

3. Identify Large Leg:
   L = the leg with the highest Leg Viral Volume.
   If tie: pick any one as L, rest become small legs.

4. Sum Small Legs:
   Y = sum of all leg volumes EXCEPT L.

5. Compute benchmark:
   X = (2/3) * Y

6. Apply cap:
   If X >= L (benchmark exceeds or equals large leg):
     capped_L = L          (no cap needed)
   If X < L (large leg exceeds benchmark):
     capped_L = X          (cap large leg to benchmark)

7. Qualifying Viral Volume = capped_L + Y
```

**Example:**
- Leg A: 5000 XP, Leg B: 2000 XP, Leg C: 1000 XP
- L = 5000 (Leg A), Y = 2000 + 1000 = 3000
- X = (2/3) * 3000 = 2000
- Is X >= L? 2000 >= 5000? No → cap L to X
- capped_L = 2000
- QVV = 2000 + 3000 = 5000

Without cap, total viral volume would be 8000. QVV reduces it to 5000 to penalize imbalanced trees.

### 6.6 Viral Commission Calculation

```
Input: affiliate A, date D, plan config, QVV from 6.5

1. Count referred active customers → customer_count
2. Sum referred volume in 30-day window → referred_volume
3. Compute QVV per algorithm 6.5
4. Find highest matching viral tier where:
   - customer_count >= tier.min_active_customers
   - referred_volume >= tier.min_referred_volume
   - QVV >= tier.min_qvv
5. If no tier matches: viral commission = 0
6. If tier matches: viral commission = tier.daily_reward (fixed USD amount per day)
```

### 6.7 Viral Commission Cap Enforcement

```
Input: all viral commissions for date D, company rolling 30-day volume

1. Compute rolling 30-day total company volume (all confirmed transactions)
2. Compute rolling 30-day total viral commissions paid out
3. viral_pct = rolling_30d_viral_commissions / rolling_30d_company_volume
4. If viral_pct <= 0.15: no adjustment
5. If viral_pct > 0.15:
   overage_pct = viral_pct - 0.15
   Reduce ALL viral commissions for today by overage_pct
   Example: if rolling ratio is 0.17, reduce today's viral payouts by 2%
```

### 6.8 Global Payout Cap

The total cap for ALL commissions (affiliate + viral) is 35% of rolling 30-day sales.

```
Input: all commissions for date D, company rolling 30-day volume

1. Compute rolling 30-day total company volume
2. Compute rolling 30-day total commissions paid (affiliate + viral combined)
3. total_pct = rolling_30d_all_commissions / rolling_30d_company_volume
4. If total_pct <= 0.35: no adjustment
5. If total_pct > 0.35:
   overage_pct = total_pct - 0.35
   Reduce ALL commissions for today (both affiliate and viral) by overage_pct
   Same proportional reduction method as the viral cap
```

**RESOLVED:** This is a hard cap with proportional reduction, same mechanics as the viral 15% cap. The SoComm doc explicitly says "a cap is applied to adjust the rewards to 35%." In practice, the viral 15% cap will almost always trigger first, making this a safety net. The CapEnforcer should apply viral cap first, then check the global cap on the remaining totals.

---

## 7. SERVICE CONTRACTS

### CommissionRunOrchestrator

```php
class CommissionRunOrchestrator
{
    public function run(Company $company, Carbon $date): CommissionRun
    {
        // 1. Get active plan for this company + date
        // 2. Hydrate PlanConfig DTO from plan JSON
        // 3. Get all affiliates for this company
        // 4. For each affiliate:
        //    a. QualificationEvaluator->evaluate(affiliate, date, planConfig)
        //    b. If qualified for affiliate commission:
        //       DirectCommissionCalculator->calculate(affiliate, date, planConfig)
        //    c. If qualified for viral commission:
        //       LegAggregator->getLegVolumes(affiliate, date, planConfig)
        //       QvvCalculator->calculate(legVolumes)
        //       ViralCommissionCalculator->calculate(affiliate, date, planConfig, qvv)
        // 5. CapEnforcer->enforce(allViralCommissions, company, date, planConfig)
        // 6. Write all results to commission_ledger_entries
        // 7. Mark CommissionRun as completed
        // 8. Return CommissionRun
    }
}
```

### QualificationEvaluator

```php
class QualificationEvaluator
{
    public function evaluate(User $affiliate, Carbon $date, PlanConfig $config): QualificationResult
    {
        // Returns:
        // - is_qualified: bool
        // - active_customer_count: int
        // - referred_volume_30d: float
        // - affiliate_tier: ?int
        // - viral_tier: ?int
        // - reasons: array (human-readable qualification explanations)
    }
}
```

### LegAggregator

```php
class LegAggregator
{
    public function getLegVolumes(User $affiliate, Carbon $date, PlanConfig $config): array
    {
        // Returns array of:
        // [
        //   ['leg_root_user_id' => 5, 'volume' => 5000.00],
        //   ['leg_root_user_id' => 8, 'volume' => 2000.00],
        //   ...
        // ]
        // Each entry = one direct referral and their entire downline subtree volume
    }
}
```

### QvvCalculator

```php
class QvvCalculator
{
    public function calculate(array $legVolumes, PlanConfig $config): VolumeSnapshot
    {
        // Implements the QVV algorithm from Section 6.5
        // Returns VolumeSnapshot with:
        // - total_viral_volume
        // - large_leg_volume (raw)
        // - small_legs_total (Y)
        // - benchmark (X)
        // - capped_large_leg
        // - qualifying_viral_volume
        // - was_capped: bool
    }
}
```

---

## 8. TEST SCENARIOS

These tests are the acceptance criteria for Phase 1. Every scenario must pass.

### Test 1: Basic Affiliate Commission

```
Setup:
  - Affiliate A has 3 referred active customers (each ordered 25 XP in window)
  - Total referred volume in 30-day window: 700 XP (determines tier)
  - Today's new referred transactions: 40 XP

Expected:
  - Matches tier: 3+ customers, 600+ volume → 13%
  - Today's commission: 40 * 0.13 = $5.20
  - (NOT 700 * 0.13 — rolling volume determines tier, daily volume determines payout)
```

### Test 2: Affiliate Commission Tier Boundary

```
Setup:
  - Affiliate A has 2 referred active customers
  - Total referred volume in 30-day window: 199 XP
  - Today's new referred transactions: 30 XP

Expected:
  - Only matches tier 1 (1+ customers, 0+ volume) → 10%
  - Does NOT match tier 2 (needs 200 volume)
  - Today's commission: 30 * 0.10 = $3.00
```

### Test 3: QVV Calculation — Balanced Tree

```
Setup:
  - Affiliate A has 3 legs:
    Leg 1: 3000 XP, Leg 2: 2500 XP, Leg 3: 2000 XP

Expected:
  - L = 3000 (Leg 1)
  - Y = 2500 + 2000 = 4500
  - X = (2/3) * 4500 = 3000
  - X >= L? 3000 >= 3000? Yes → no cap
  - QVV = 3000 + 4500 = 7500
```

### Test 4: QVV Calculation — Imbalanced Tree (Cap Triggered)

```
Setup:
  - Affiliate A has 3 legs:
    Leg 1: 10000 XP, Leg 2: 2000 XP, Leg 3: 1000 XP

Expected:
  - L = 10000 (Leg 1)
  - Y = 2000 + 1000 = 3000
  - X = (2/3) * 3000 = 2000
  - X >= L? 2000 >= 10000? No → cap L to 2000
  - QVV = 2000 + 3000 = 5000
  - (Total viral volume was 13000 but QVV is only 5000)
```

### Test 5: QVV Calculation — Single Leg

```
Setup:
  - Affiliate A has 1 leg: 5000 XP

Expected:
  - L = 5000
  - Y = 0 (no small legs)
  - X = (2/3) * 0 = 0
  - X >= L? 0 >= 5000? No → cap L to 0
  - QVV = 0 + 0 = 0
  - Affiliate earns no viral commission (need multiple legs!)
```

### Test 6: Viral Commission Tier Match

```
Setup:
  - Affiliate A: 2 active customers, 150 referred volume, QVV = 2500

Expected:
  - Matches tier 8: 2+ customers, 150+ referred volume, 2500+ QVV
  - Daily reward: $12.50
```

### Test 7: Viral Cap Enforcement

```
Setup:
  - Company rolling 30-day volume: 100,000 XP
  - Rolling 30-day viral commissions paid: 17,000 (= 17%)
  - Today's uncapped viral payouts: $500 total

Expected:
  - viral_pct = 17% > 15% cap
  - overage = 2%
  - Today's viral payouts reduced by 2%
  - Adjusted total: $500 * (1 - 0.02) = $490
```

### Test 8: No Qualification

```
Setup:
  - Affiliate A has 0 referred active customers
  - Referred volume: 500 XP

Expected:
  - Affiliate commission: matches tier 1 (1+ customer needed) → FAILS (0 customers)
  - Actually wait — tier 1 requires min_active_customers: 1
  - With 0 customers, no tier matches
  - Commission: $0
```

### Test 9: Idempotent Commission Run

```
Setup:
  - Run commissions for Company X, Date 2026-03-15
  - Run again for Company X, Date 2026-03-15

Expected:
  - Second run produces identical ledger entries
  - No duplicate records
  - Commission totals unchanged
```

### Test 10: Wallet Double-Entry

```
Setup:
  - Affiliate A earns $100 affiliate commission + $12.50 viral commission
  - Weekly wallet credit runs

Expected:
  - wallet_movements: two credit entries (+$100.00, +$12.50)
  - Wallet balance (derived from SUM): $112.50
  - No mutable balance field updated
```

---

## 9. AGENT TASK ASSIGNMENTS

### AGENT 1: Foundation (Database + Models)

**Brief:** Set up the database layer for the TyeUps Network Growth Engine.

**Tasks:**
1. Install `staudenmeir/laravel-adjacency-list` package
2. Create all migrations per Section 4 schema
3. Create Eloquent models with relationships, casts, scopes
4. Implement `CompanyScope` global scope for tenant isolation
5. Implement `ResolveTenant` middleware
6. Create model factories for all models
7. Create a `SoCommSeeder` that seeds:
   - SoComm company
   - SoComm compensation plan (with full JSON config from Section 5)
   - 20+ test users (mix of customers and affiliates)
   - A genealogy tree with 3-4 levels depth, multiple legs
   - 50+ transactions spread across the 30-day window
8. Ensure `php artisan migrate:fresh --seed` works cleanly

**Acceptance criteria:**
- All migrations run without error
- All models have correct relationships
- Factory-generated data is valid
- Seeder creates a realistic test dataset
- Tenant scoping works (queries only return data for the current company)

**Do NOT build:** Any services, controllers, or business logic. Models only.

---

### AGENT 2: Calculation Engine

**Brief:** Build the commission calculation services. These are the core of the product.

**Prerequisite:** Agent 1 must be complete (models and migrations in place).

**Tasks:**
1. Create all DTOs (PlanConfig, AffiliateCommissionTier, ViralCommissionTier, QualificationResult, CommissionResult, VolumeSnapshot)
2. Build `QualificationEvaluator` — computes active customer count and referred volume
3. Build `DirectCommissionCalculator` — implements affiliate commission tier matching
4. Build `LegAggregator` — queries genealogy tree for leg volumes using closure table
5. Build `QvvCalculator` — implements the QVV algorithm (Section 6.5)
6. Build `ViralCommissionCalculator` — matches viral tier from QVV result
7. Build `CapEnforcer` — implements viral commission cap logic (Section 6.7)
8. Build `CommissionRunOrchestrator` — coordinates full daily run
9. Build `WalletCreditService` — creates wallet movements from approved commissions

**Architecture rules:**
- Services receive PlanConfig DTO, never read plan JSON directly
- Services do NOT call each other's database queries — orchestrator passes data between them
- All monetary calculations use bcmath or the `brick/money` library (no floating point arithmetic for money)
- Every commission ledger entry includes a `qualification_snapshot` JSON showing exactly why it was earned

**Do NOT build:** Artisan commands, controllers, API routes. Services only.

---

### AGENT 3: Test Suite

**Brief:** Write comprehensive PHPUnit tests for the calculation engine.

**Prerequisite:** Can work in parallel with Agent 2 (write tests first, Agent 2 makes them pass).

**Tasks:**
1. Create a `CommissionTestCase` base class with helpers for:
   - Building a test company with plan config
   - Creating genealogy trees programmatically
   - Seeding transactions for specific date ranges
   - Asserting commission amounts with precision
2. Write tests for ALL scenarios in Section 8
3. Write additional edge case tests:
   - Affiliate with exactly 1 active customer at exactly 20 XP (boundary)
   - Customer with 19 XP (should NOT count as active)
   - Transaction outside 30-day window (should not be included)
   - Refunded transaction (should not count)
   - Tied large legs in QVV calculation
   - Zero legs (new affiliate with no referrals)
   - Commission run for a date with no transactions
   - Plan version transition (new plan starts mid-month)
4. Write a test that verifies the full SoComm seeder data produces expected commission results

**File structure:**
```
tests/
├── Feature/
│   └── Commission/
│       ├── CommissionTestCase.php
│       ├── QualificationEvaluatorTest.php
│       ├── DirectCommissionCalculatorTest.php
│       ├── QvvCalculatorTest.php
│       ├── ViralCommissionCalculatorTest.php
│       ├── CapEnforcerTest.php
│       ├── CommissionRunOrchestratorTest.php
│       └── WalletCreditServiceTest.php
└── Unit/
    └── DTOs/
        └── PlanConfigTest.php
```

---

### AGENT 4: Admin Panel + API + Commands

**Brief:** Build the operational interface and artisan commands.

**Prerequisite:** Agents 1-2 must be complete.

**Tasks:**
1. Install Filament admin panel
2. Create Filament resources for:
   - Companies (CRUD)
   - Users (list, view, filter by role/status)
   - Compensation Plans (CRUD, JSON editor for config)
   - Commission Runs (list, view details, trigger new run)
   - Commission Ledger (read-only, filterable)
   - Wallet Accounts (view balances, movement history)
3. Create Artisan commands:
   - `php artisan commissions:run {company} {--date=}` — runs daily commissions
   - `php artisan wallet:credit {company} {--date=}` — runs weekly wallet crediting
   - `php artisan commissions:recalculate {company} {run_id}` — replays a historical run
4. Create basic API endpoints:
   - `POST /api/companies/{company}/commission-runs` — trigger a commission run
   - `GET /api/companies/{company}/commission-runs/{run}` — get run results
   - `GET /api/users/{user}/wallet` — get wallet balance and movements
   - `GET /api/users/{user}/commissions` — get commission history
5. Add API authentication (Laravel Sanctum)

---

## 10. DEPLOYMENT SEQUENCE

Ship in this order. Each is a deployable increment:

1. **Migrations + Models** → deploy, verify schema on Laravel Cloud
2. **Seeders** → deploy, seed SoComm test data on staging
3. **Calculation Engine** → deploy behind artisan command, run test commissions
4. **Test Suite** → CI pipeline green
5. **Admin Panel** → deploy, verify operational visibility
6. **API Endpoints** → deploy, test with Postman/Insomnia

---

## 11. KEY PACKAGES

```
composer require staudenmeir/laravel-adjacency-list   # Closure table for genealogy
composer require filament/filament                     # Admin panel
composer require brick/money                           # Precise monetary calculations
composer require laravel/sanctum                       # API auth
composer require spatie/laravel-data                   # DTO support (optional but recommended)
```

---

## 12. RESOLVED DESIGN DECISIONS

These were ambiguities in the SoComm plan document. Each has been resolved with a default implementation. All are configurable in the plan config so they can be changed per-tenant without code changes.

### Decision 1: Affiliate Commission Daily Payout Method
**Resolution: Tier from rolling 30-day metrics, payout on today's new volume only.**
The rolling 30-day referred volume and active customer count determine which percentage tier applies. That percentage is then applied only to the new qualifying referred transactions from today. This prevents 30x overpayment and is the industry standard.
- Config key: `affiliate_commission.payout_method` = `"daily_new_volume"`
- Alternative values for other tenants: `"rolling_amortized"` (rolling / 30), `"rolling_full"` (full amount daily, rare)

### Decision 2: Affiliate Self-Purchases
**Resolution: An affiliate's own purchases do NOT earn them commission, but DO count as referred volume for their sponsor.**
The SoComm doc says commissions are based on "Referred Customers and Referred Affiliates." You are not your own referral. But your sponsor referred you, so your purchases count toward their referred volume.
- Config key: `affiliate_commission.self_purchase_earns_commission` = `false`
- The `referred_by_user_id` on transactions handles this naturally — an affiliate's purchases have their sponsor as `referred_by_user_id`, not themselves.

### Decision 3: Active Customer Minimum Order Threshold
**Resolution: Per-order threshold, not cumulative.**
The SoComm doc says "a minimum order of 20 XP" — singular "order." A customer must have at least one single order of >= 20 XP to count as active. Three orders of 10 XP each (30 XP total) does NOT qualify.
- Config key: `qualification.active_customer_min_order_xp` = `20`
- Config key: `qualification.active_customer_threshold_type` = `"per_order"`
- Alternative value for other tenants: `"cumulative_in_window"`

### Decision 4: Enrollment Tree = Sponsor/Referrer Tree
**Resolution: One tree. The enrollment tree is the sponsor/referrer tree.**
There is no separate placement tree in SoComm's model. The person who referred you is your enroller and your sponsor. Leg volumes are calculated by walking down from each of your direct referrals through the entire subtree.
- The `genealogy_nodes.sponsor_id` field represents this single tree.
- The `position` field exists for future tenants who need placement trees (e.g., binary plans) but is NULL for SoComm.

### Decision 5: Global 35% Cap Is a Hard Cap
**Resolution: Hard cap with proportional reduction, same mechanics as the viral 15% cap.**
The SoComm doc says "a cap is applied to adjust the rewards to 35%." This is active enforcement, not just reporting. The CapEnforcer applies the viral 15% cap first, then checks the global 35% cap on the combined remaining total. If still over, all commission types for that day are reduced proportionally.
- Config key: `caps.total_payout_cap_percent` = `0.35`
- Config key: `caps.total_payout_cap_enforcement` = `"proportional_reduction"`
- Alternative value for other tenants: `"report_only"` (flag but don't reduce)

---

## 13. CONVENTIONS

- **Dates:** Always Carbon instances. Store as DATE or TIMESTAMP in MySQL.
- **Money:** Use `brick/money` for all calculations. Store as DECIMAL(12,4) in database.
- **JSON configs:** Cast with `$casts = ['config' => 'array']` on models. Hydrate into DTOs in services.
- **Naming:** Services use verb methods (`calculate`, `evaluate`, `enforce`). DTOs are value objects.
- **Exceptions:** Custom exception classes in `app/Exceptions/Commission/` for business rule violations.
- **Logging:** Every commission run logs start, end, totals, and any cap triggers.
