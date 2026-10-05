# API Keys

Connect your own systems (website, ERP, OMS) to Rushly with the **Merchant API**.
API keys are how you authenticate those requests. Manage them under
**Settings → API Keys**.

## Live vs. Test

- **Live** keys (`rly_live_…`) work against the production API — real shipments.
- **Test** keys (`rly_test_…`) work against the staging API — for building and
  trying things safely.

A live key only works on production and a test key only on staging.

## Create a key

1. Click **Create key**.
2. Give it a **name** (e.g. "Website", "ERP").
3. Choose the **environment** (Live or Test — Test appears when available).
4. Select the **scopes** (permissions) the key needs:
   - `shipments:create`, `shipments:read`, `shipments:cancel`
   - `tracking:read`
   - `statuses:read`
   - `webhooks:manage`
5. Create — the **token is shown once**. Copy it immediately and store it
   securely; you can't see it again.

## Manage

- The list shows each key's name, environment, prefix, scopes, last used, and
  status.
- **Revoke** a key you no longer use (or if it may be exposed) — it stops working
  immediately.
- Lost a token? Revoke it and create a new one.

## Using a key

Send it as a bearer token:

```
Authorization: Bearer rly_live_xxxxxxxx
```

Full API reference and examples: **`api.rushly.tech/docs`**.

## Keep keys safe

- Never put a key in client-side/browser code or a public repo.
- Give each integration its own key with the **minimum scopes** it needs.
- Rotate (revoke + recreate) periodically.
