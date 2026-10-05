# Webhooks

Webhooks push shipment updates to **your** server in real time, so you don't
have to poll. When a shipment changes status, Rushly sends a signed `POST` to
your URL. Manage them under **Settings → Webhooks**.

## Add an endpoint

1. Click **Create**.
2. Enter your **HTTPS URL** (where Rushly should POST).
3. Choose the **environment** (Live / Test).
4. Tick the **events** you want (see below).
5. Create — a **signing secret** (`whsec_…`) is shown **once**. Copy it; you'll
   use it to verify incoming requests.

## Events

You'll be notified when a shipment reaches any status you subscribe to:

`shipment.created`, `shipment.pickup_scheduled`, `shipment.picked_up`,
`shipment.at_hub`, `shipment.in_transit`, `shipment.out_for_delivery`,
`shipment.delivered`, `shipment.delivery_failed`, `shipment.returning`,
`shipment.returned`, `shipment.cancelled`.

## What you receive

A JSON body like:

```json
{
  "event": "shipment.delivered",
  "data": {
    "tracking_number": "RDS-10000012345",
    "reference": "ORD-100025",
    "status": { "code": "delivered", "name": "Delivered" }
  }
}
```

with headers `X-Rushly-Event`, `X-Rushly-Delivery`, and `X-Rushly-Signature`.
**Always verify the signature** using your secret before trusting the request —
see the signature steps in the developer docs (`api.rushly.tech/docs` →
Webhooks). Respond `2xx` quickly; failures are retried automatically.

## Manage

- **Deliveries** — see recent attempts for an endpoint (status, HTTP code,
  attempts) to debug.
- **Rotate secret** — generate a new signing secret (update your server to use
  it).
- **Disable / delete** — stop deliveries to an endpoint.

## Tips

- Use a **Test** endpoint while building, then add a **Live** one.
- De-duplicate on `X-Rushly-Delivery` in case of a retry.
