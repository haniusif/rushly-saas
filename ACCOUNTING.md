# Rushly — Accounting Subsystem

How money moves through the rushly-saas Laravel monolith: the ledgers, the balances, the triggers, and the rules.

> Companion to `ARCHITECTURE.md`. This doc only covers accounting — tables, repositories, and the events that mutate them.

---

## 1. Mental model — three layers

```
┌───────────────────────────────────────────────────────────────────────┐
│  Layer 3  — Per-party running balances (mutable scalars)              │
│   merchants.current_balance     delivery_man.current_balance          │
│   hubs.current_balance          accounts.balance                      │
└───────────────────────────────────────────────────────────────────────┘
                                ▲
                                │ every event that touches money
                                │ also increments/decrements one of these
                                │
┌───────────────────────────────────────────────────────────────────────┐
│  Layer 2  — Per-party statement ledgers (append-only history)         │
│   merchant_statements    deliveryman_statements    hub_statements     │
│   courier_statements     vat_statements                               │
│   (each row: type = INCOME(1) or EXPENSE(2), amount, note, links)     │
└───────────────────────────────────────────────────────────────────────┘
                                ▲
                                │
┌───────────────────────────────────────────────────────────────────────┐
│  Layer 1  — Bank / cash layer                                         │
│   accounts (chart of accounts)                                        │
│   bank_transactions (immutable mirror of every accounts.balance move) │
└───────────────────────────────────────────────────────────────────────┘
                                ▲
                                │
   Triggers (next section) ─────┘
```

Every accounting event writes to **all three layers** in the same call: balance scalars get mutated, statement rows get appended, and a bank_transaction is recorded if the move involves a real account.

The system is **not** double-entry — there is no `debits = credits` invariant. Balances are maintained by application code calling `increment` / `decrement` on the affected party. If a write path is missed, balances drift.

---

## 2. Schema

### 2.1 Chart of accounts

| Table | Purpose | Key columns |
|---|---|---|
| `accounts` | Real cash / bank accounts (cash drawer, bank, mobile wallet). One row per account, per tenant. | `company_id`, `type` (1=admin, 2=user), `user_id`, `gateway`, `balance` decimal(16,2), `opening_balance`, `bank`, `branch_name`, `account_holder_name`, `account_no` |
| `account_heads` | Categories for income/expense. Drives which party's statement is touched. | `type` (1=INCOME, 2=EXPENSE), `name` |

### 2.2 Statement ledgers (append-only)

All five share the same shape: `company_id`, `type` (INCOME=1 / EXPENSE=2), `amount`, `date`, `note`, plus party FKs.

| Table | Whose ledger | Linked to |
|---|---|---|
| `merchant_statements` | Per-merchant running ledger | `merchant_id`, optional `parcel_id`, `delivery_man_id`, `expense_id` |
| `deliveryman_statements` | Per-driver running ledger | `delivery_man_id`, optional `parcel_id`, `hub_id`, `expense_id`, `cash_collection` flag |
| `hub_statements` | Per-hub running ledger | `hub_id`, optional `user_id`, `account_id`, `delivery_man_id` |
| `courier_statements` | Company-wide P&L row per money event | `income_id` OR `expense_id` OR `parcel_id`, `delivery_man_id` |
| `vat_statements` | VAT collected per parcel | `parcel_id` |

### 2.3 Transactions & flows

| Table | Purpose | Key columns |
|---|---|---|
| `bank_transactions` | Append-only mirror of every `accounts.balance` change. Polymorphic — at most one of `income_id`, `expense_id`, `fund_transfer_id`, `cash_received_dvry` is set per row. | `account_id`, `type`, `amount`, `date`, `note` |
| `fund_transfers` | Account → account transfers (writes two `bank_transactions` per row, one INCOME one EXPENSE). | `from_account`, `to_account`, `amount`, `description` |
| `incomes` | Manually-entered income (admin UI) — bounded by `account_head_id`. | `account_head_id`, `from`, `merchant_id` / `delivery_man_id` / `hub_id` / `user_id`, `account_id`, `parcel_id`, `amount`, `receipt` |
| `expenses` | Manually-entered expense. Same shape as `incomes` minus hub fields. | `account_head_id`, `merchant_id`, `delivery_man_id`, `user_id`, `parcel_id`, `account_id`, `amount`, `receipt`, `title` |
| `payments` | Merchant payouts (admin → merchant). Status: PENDING / PROCESSED. | `merchant_id`, `from_account`, `merchant_account`, `transaction_id`, `reference_file` |
| `hub_payments` | Hub-level payouts. | `hub_id`, `account_id`, `amount` |
| `salaries` | Staff salary payments. | `user_id`, `account_id`, `month`, `date`, `amount` |
| `salary_generates` | Generated salary slip cache. | `user_id`, `month`, `amount` |
| `wallets` | Merchant/user wallet balances. | tenant + party + amount |
| `invoices` | Merchant billing periods. | `merchant_id`, `invoice_id`, `cash_collection`, `total_charge`, `current_payable`, `parcels_id`, `status` (UNPAID / PROCESSING / PAID) |
| `invoice_parcels` | Invoice ↔ parcel pivot. | |
| `merchant_online_payments` / `_receiveds` | Stripe/PayPal/Razorpay/etc. inbound receipts. | gateway-specific |

All tables are scoped by `company_id` (FK → `general_settings.id`) and queried through `scopeCompanywise()` on the model. Tenant isolation is application-layer only.

---

## 3. Account heads — the seeded IDs

`AccountHeadSeeder` writes **seven** initial heads. Critical: the application code **hardcodes these IDs** (`if ($request->account_head_id == 1)` etc.) — they are not soft enums, they are positional. Do not reorder the seeder.

| ID | Type | Name | Drives writes to |
|---|---|---|---|
| 1 | INCOME | Payment received from Merchant | `merchant_statements` + `merchants.current_balance` |
| 2 | INCOME | Cash received from delivery man | `deliveryman_statements` + `delivery_man.current_balance` |
| 3 | INCOME | Others | (just `accounts.balance` + `bank_transactions` + `courier_statements`) |
| 4 | EXPENSE | Payment paid to merchant *(seeded INACTIVE)* | `merchant_statements` + `merchants.current_balance` |
| 5 | EXPENSE | Commission paid to delivery man | `deliveryman_statements` + `delivery_man.current_balance` |
| 6 | EXPENSE | Others | (just `accounts.balance` + `bank_transactions` + `courier_statements`) |
| 7 | INCOME | Payment receive from hub | `hub_statements` + `hubs.current_balance` + adjusts hub-user account |

Admins can add more heads through the AccountHeads CRUD, but **new heads with IDs > 7 only behave like "Others"** — none of the per-party balance code paths will fire for them. The seven seeded IDs are effectively part of the schema.

---

## 4. Triggers — every code path that mutates a balance

### 4.1 Manual income (`IncomeRepository::store`)

`app/Repositories/Income/IncomeRepository.php`

Per `account_head_id`:

- **1 (from merchant)** — creates `merchant_statements` (INCOME) + `merchants.current_balance += amount`.
- **2 (from deliveryman)** — creates `deliveryman_statements` (INCOME) + `delivery_man.current_balance += amount`.
- **7 (from hub)** — creates `hub_statements` (INCOME) + `hubs.current_balance += amount`, then **also** decrements the hub-user's chosen account (`hub_user_account_id`).
- **3 (others)** — no per-party update.

Then always: insert `incomes` row, `accounts.balance += amount` on the destination account, write `bank_transactions` (type=INCOME) linked via `income_id`, write `courier_statements` (type=INCOME).

> ⚠️ **Not transactional.** `IncomeRepository::store` wraps in try/catch but does **not** open a `DB::beginTransaction()`. Partial failures leave inconsistent balances. The `Expense`, `FundTransfer`, and `Payment` repos *do* use transactions — Income was missed.

### 4.2 Manual expense (`ExpenseRepository::store`)

`app/Repositories/Expense/ExpenseRepository.php` — wrapped in `DB::transaction`.

1. **Balance guard**: `if ($account->balance < $amount) return 2;` — refuses to overdraw. Return code `2` is the UI signal for "insufficient funds".
2. Insert `expenses` row.
3. `accounts.balance -= amount`.
4. Write `bank_transactions` (EXPENSE, linked by `expense_id`).
5. Write `courier_statements` (EXPENSE).
6. Per `account_head`:
   - **4 (paid to merchant)** — `merchants.current_balance -= amount`, write `merchant_statements` (INCOME ← note: this is INCOME from the merchant's POV because the courier owed it to them).
   - **5 (paid to deliveryman)** — `delivery_man.current_balance -= amount`, write `deliveryman_statements` (INCOME from driver's POV).
   - **6 (others)** — no per-party update.

The label flip (system EXPENSE → merchant/driver INCOME) is intentional: the same money is "money out the door" for the courier and "money received" for the recipient.

### 4.3 Fund transfer (`FundTransferRepository::store`)

`app/Repositories/FundTransfer/FundTransferRepository.php` — wrapped in `DB::transaction`.

Guards: source account has balance, amount > 0.

1. `from_account.balance -= amount`, `to_account.balance += amount`.
2. Insert `fund_transfers` row.
3. Write **two** `bank_transactions` linked by `fund_transfer_id` — one EXPENSE on `from_account`, one INCOME on `to_account`.

No statement-ledger writes — pure cash movement between courier-owned accounts.

### 4.4 Parcel delivery (`ParcelRepository` — automatic)

`app/Repositories/Parcel/ParcelRepository.php` ~line 2015–2130. This is where most of the money in the system actually moves.

On `delivered`:

1. **Delivery-man income** — `deliveryman_statements` (INCOME, amount = `delivery_man.delivery_charge`), `delivery_man.current_balance += charge`.
2. **Courier expense** — `courier_statements` (EXPENSE, same amount) — pays the driver out of company books.
3. **Cash collection** — if `parcel.cash_collection > 0`:
   - `deliveryman_statements` (EXPENSE, amount=cash, `cash_collection=1`), `delivery_man.current_balance -= cash`. The driver is now "holding" that cash on the courier's behalf.
4. **Merchant income** — `merchant_statements` (INCOME, amount=cash_collection), `merchants.current_balance += cash_collection`.
5. **Merchant delivery-charge expense** — `merchant_statements` (EXPENSE, amount=`total_delivery_amount`), then VAT expense (amount=`vat_amount`), then `merchants.current_balance -= (charge + vat)`.
6. **Courier income** — `courier_statements` (INCOME, amount=`total_delivery_amount`).
7. **VAT income** — `vat_statements` (INCOME, amount=`vat_amount`).
8. `parcel.status = DELIVERED`.

Net merchant delta = `cash_collection − total_delivery_amount − vat_amount` = what the courier owes the merchant for this parcel.

Pickup, return, partial-delivered, return-to-merchant follow analogous patterns (see other handlers in the same file).

### 4.5 Cash received from deliveryman → hub (`ReceivedRepository`)

`app/Repositories/CashReceivedFromDeliveryman/ReceivedRepository.php`

When a driver hands cash to a hub:

1. `hubs.current_balance -= amount` (note: the hub is now holding the cash, but the code stores it as a negative — the hub balance tracks "owed by hub to company").
2. Write `bank_transactions`.
3. Write `deliveryman_statements`, `delivery_man.current_balance += amount` (driver no longer holds it).

When the hub then deposits to a bank account, a `HubPayment` writes the other half.

### 4.6 Merchant payment (`PaymentRepository::store`)

Two modes:

- **Pending** — just creates the `payments` row, no balance moves.
- **Processed** (`isprocess = true`) — creates the row AND:
  1. `merchant_statements` (EXPENSE, note="payment_withdrawal"), `merchants.current_balance -= amount`.
  2. `bank_transactions` (EXPENSE) on `from_account`, `accounts.balance -= amount`.

A pending payment is later "processed" via the same logic in `update()`.

### 4.7 Salary (`SalaryRepository::store`)

1. Insert `salaries` row.
2. `accounts.balance -= amount` (the chosen payroll account).
3. Write `bank_transactions` (EXPENSE).

**No statement-ledger or per-party balance writes.** Salaries are tracked separately from the merchant/driver/hub ledgers.

### 4.8 Hub payment (`HubPaymentRepository`)

Hub-level payouts. Writes `bank_transactions` + `merchant_statements` depending on payment scope.

### 4.9 Invoice generation (`InvoiceRepository::store`)

Triggered by the `invoice:generate` artisan command (scheduled). Per merchant whose `payment_period` has elapsed:

1. Pull all `DELIVERED` parcels + `partial_delivered=YES` parcels + return parcels not yet invoiced (`invoice_id = null`).
2. Sum: `total_collected_amount`, `total_d_charge_amount`, `total_vat_amount`, `total_return_charges`.
3. `current_payable = (cash collected − delivery charges − VAT) − return charges`.
4. Insert `invoices` row (status=PROCESSING), stamp `parcel.invoice_id` on every included parcel.

Invoice generation is **read-only against balances** — it doesn't write statements or move money. It's a snapshot for billing. Payment of the invoice happens via the merchant-payment flow (§4.6).

---

## 5. Controllers (UI surface)

`app/Http/Controllers/Backend/`

| Controller | What it does | Routes mounted in |
|---|---|---|
| `AccountController` | CRUD on `accounts` (chart of accounts) | `web.php` (admin panel) |
| `AccountHeadsController` | CRUD on `account_heads` | `web.php` |
| `IncomeController` | Manual income entry → `IncomeRepository` | `web.php` |
| `ExpenseController` | Manual expense entry → `ExpenseRepository` | `web.php` |
| `FundTransferController` | Account-to-account transfer | `web.php` |
| `BankTransactionController` | Read-only listing + filter | `web.php` |
| `MerchantInvoiceController` | Invoice index/details/PDF | `web.php` |
| `PayoutController` | Online merchant payouts via Stripe/PayPal/Razorpay/SSLCommerz | `web.php` |
| `PayoutSetupController` | Gateway credentials per merchant | `web.php` |
| `SalaryController` | Salary entry → `SalaryRepository` | `web.php` |
| `SalaryGenerateController` | Generate monthly salary slips | `web.php` |
| `HubPaymentController` | Hub payouts | `web.php` |
| `Backend/MerchantPanel/StatementsController` | Merchant self-service statement view | `web.php` (merchant panel) |
| `Backend/HubPanel/ReceivedFromDeliverymanController` | Hub records cash received from a driver | `web.php` (hub panel) |
| `Backend/HubPanel/HubPaymentRequestController` | Hub requests a payout | `web.php` (hub panel) |
| `Backend/MerchantPanel/PaymentRequestController` | Merchant requests a payout | `web.php` (merchant panel) |

---

## 6. Reports

`ReportsRepository` and `TotalSummeryReportRepository` aggregate from these tables — no separate report cache, queries are run live against the ledgers.

| Report | Source tables |
|---|---|
| Daily income/expense | `incomes`, `expenses` filtered by `date` |
| Merchant statement | `merchant_statements` per merchant |
| Driver statement | `deliveryman_statements` per driver |
| Hub statement | `hub_statements` per hub |
| Courier P&L | `courier_statements` |
| VAT register | `vat_statements` |
| Account ledger | `bank_transactions` per account |

Dashboard helpers (`dayIncomeCount`, `dayExpenseCount`, `dayMerchantRevIncomeCount`, etc. in `app/Http/Helper/Helper.php`) are simple SUMs over `incomes`/`expenses`/`courier_statements` for the current day.

---

## 7. Money flow — worked example

A merchant ships a parcel with `cash_collection = 500`, `total_delivery_amount = 50`, `vat_amount = 5`. Driver delivery charge = `30`.

**On `delivered`:**

| Layer | Write | Amount |
|---|---|---|
| `deliveryman_statements` | INCOME (commission earned) | +30 |
| `delivery_man.current_balance` | += 30 | → +30 |
| `courier_statements` | EXPENSE (paying driver) | −30 |
| `deliveryman_statements` | EXPENSE (`cash_collection=1`) | −500 |
| `delivery_man.current_balance` | −= 500 | → −470 (driver owes 470) |
| `merchant_statements` | INCOME (cash collected for merchant) | +500 |
| `merchants.current_balance` | += 500 | → +500 |
| `merchant_statements` | EXPENSE (delivery charge) | −50 |
| `merchant_statements` | EXPENSE (VAT) | −5 |
| `merchants.current_balance` | −= 55 | → +445 (courier owes merchant 445) |
| `courier_statements` | INCOME (delivery fee) | +50 |
| `vat_statements` | INCOME | +5 |

**Then the driver hands the cash to a hub** (`ReceivedRepository`):

| Layer | Write | Amount |
|---|---|---|
| `bank_transactions` | recorded | 500 |
| `hubs.current_balance` | −= 500 | hub now holds 500 |
| `deliveryman_statements` | (cleared) | +500 |
| `delivery_man.current_balance` | += 500 | → +30 (driver only keeps their commission) |

**Then the hub deposits to a bank account** (`HubPaymentRepository`): increments `accounts.balance` by 500, writes a `bank_transactions` row.

**At invoice cut** (`InvoiceRepository::store`): invoice line for this parcel = `+500 − 50 − 5 = 445 payable to merchant`.

**On invoice payment** (`PaymentRepository::store`, isprocess=true): `accounts.balance -= 445`, `merchants.current_balance -= 445` → back to 0.

End state: courier kept `50 (delivery) + 5 (VAT) − 30 (commission) = 25`, merchant got `445`, driver got `30`.

---

## 8. Known characteristics & gotchas

- **Not double-entry.** No debit/credit columns, no `sum(debit) == sum(credit)` enforcement. Reports trust the per-party `current_balance` columns to be correct.
- **Hardcoded account-head IDs (1–7).** Adding heads in the UI is safe; reordering or deleting the seeded seven will silently break the parcel/income/expense logic.
- **`IncomeRepository::store` is not wrapped in a DB transaction.** Other repos (`ExpenseRepository`, `FundTransferRepository`, `PaymentRepository`, `ExpenseRepository::delete`) are. A mid-write failure on income leaves partial state.
- **`hubs.current_balance` semantics are inverted** — increases when cash flows *out* (deposit to bank), decreases when cash is *received* from a driver. Reads as "amount the hub owes the company". Easy to misread.
- **Per-party balance scalars are the source of truth for "current owed".** Replaying the statement rows would also work, but no code does that — if a balance drifts, manual SQL is needed to reset it (`UPDATE merchants SET current_balance = (SELECT SUM(...) FROM merchant_statements WHERE ...)`).
- **Account-head `4` is seeded INACTIVE.** "Payment paid to merchant" as a manual expense head is hidden from the UI by default — merchant payouts run through `PaymentRepository` instead, which writes its own statement row directly.
- **Tenant isolation = application layer.** Every model has `scopeCompanywise()` that filters by `settings()->id`. Forgetting to chain it in a custom query leaks cross-tenant data.
- **`bank_transactions` is the only append-only audit log for the cash layer.** Don't delete rows from it directly — the repositories soft-mirror moves via INCOME/EXPENSE rows even on reversals (see `ExpenseRepository::delete` which writes a reversing EXPENSE→INCOME row instead of unlinking the original).

---

## 9. Quick file map

| Concern | Path |
|---|---|
| Account model + scope | `app/Models/Backend/Account.php` |
| Account-head enum + seeder | `app/Enums/AccountHeads.php`, `database/seeders/AccountHeadSeeder.php` |
| Statement-type enum | `app/Enums/StatementType.php` |
| Income flow | `app/Repositories/Income/IncomeRepository.php` |
| Expense flow | `app/Repositories/Expense/ExpenseRepository.php` |
| Fund transfer | `app/Repositories/FundTransfer/FundTransferRepository.php` |
| Bank transactions | `app/Repositories/BankTransaction/BankTransactionRepository.php` |
| Parcel-driven accounting | `app/Repositories/Parcel/ParcelRepository.php` (search for `DeliverymanStatement`, `MerchantStatement`, `CourierStatement`, `VatStatement`) |
| Cash from driver → hub | `app/Repositories/CashReceivedFromDeliveryman/ReceivedRepository.php` |
| Merchant payout | `app/Repositories/MerchantManage/Payment/PaymentRepository.php` |
| Hub payout | `app/Repositories/HubManage/HubPayment/HubPaymentRepository.php` |
| Salary | `app/Repositories/Salary/SalaryRepository.php` |
| Invoice generation | `app/Repositories/Invoice/InvoiceRepository.php`, `app/Console/Commands/` (`invoice:generate`) |
| Reports | `app/Repositories/Reports/`, `app/Http/Controllers/Backend/ReportsController.php`, `TotalSummeryReportController.php` |
| Dashboard rollups | `app/Http/Helper/Helper.php` (`dayIncomeCount`, `dayExpenseCount`, `dayMerchantRev*`, `dayDeliverymanRev*`) |
