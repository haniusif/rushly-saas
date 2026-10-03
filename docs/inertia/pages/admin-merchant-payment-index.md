# Admin · Client payments (merchant payouts) index

- Route: `GET /admin/payment/index` (`merchant.manage.payment.index`) →
  `MerchantmanagePaymentController::index`, permission `payment_read`
- Legacy filter URL `GET /admin/payment/merchant/filter`
  (`merchantmanage.payment.filter`) now delegates to `index()`, which
  applies the filters itself when any of `date / merchant_id /
  merchant_account / from_account` is present.
- Page: `resources/js/Pages/Admin/MerchantPayment/Index.jsx` (uses the
  shared `Components/wms/ListPage.jsx` chrome)

**Filter**: date range (two `<input type="date">`, joined to the legacy
wire format `YYYY-MM-DD To YYYY-MM-DD`), client (merchant), client payout
account (the full tenant list is shipped in `lookups.merchant_accounts`
keyed by `merchant_id` and narrowed client-side — the select is disabled
until a client is picked), from (courier) account.

**Table**: #, Client (avatar, business, contact, email), Payout account
(method icon + bank/mobile lines, or "Cash"), Trans. ID + date + reference
download, From account (title + gateway lines), Description (clamped),
Status pill (`pending` amber / `processed` emerald / `reject` rose),
Amount, Actions. Footer shows the page total.

**Actions** (`permissions.reject/process/update/delete`; URLs are `null`
when the permission is missing): pending → Process (GET to the process
form), Reject, Edit, Delete (`router.delete`, confirm); processed →
Cancel processed; rejected → Cancel reject. The GET actions that mutate
(reject / cancel-*) sit behind `window.confirm(t.confirm_action)`.

Row shapes are built by two controller helpers, `payoutAccountSummary()`
(bank / mobile / cash → `{method, method_label, lines, label}`) and
`fromAccountSummary()` (cash / bank / Bkash-Rocket-Nagad →
`{title, lines, balance, label}`), so the JSX never branches on gateway ids.

Note: the legacy Blade populated the "from account" dropdown from
`AccountInterface::all()` which paginates to 10; the Inertia page uses
`getAll()` so every courier account is listed.
