# Shipments

The Shipments (Parcels) section is where you create and manage deliveries.

## Create a shipment

Click **New shipment** (top bar) or **Add** in the Shipments list, then fill in:

- **Shop** — which of your [shops](shops.md) the parcel is picked up from.
- **Recipient** — name and phone (and optional email).
- **Delivery address** — city, area/district, street; add a map location if you
  can, for faster delivery.
- **Package** — category, weight, number of boxes, and an optional description.
- **Delivery type** — e.g. same-day, next-day, sub-city, outside-city.
- **Payment** — mark as **COD** and set the amount to collect, or prepaid.
- **Notes** — anything the courier should know (e.g. "call before delivery").

Save, and the shipment gets a **tracking number** (e.g. `RDS-10000012345`).

## Find & filter

The list shows your shipments with their status. Filter by date, status,
reference, or tracking number to find what you need.

## Track a shipment

Open any shipment to see:

- its **current status** and full **status timeline** (created → picked up →
  out for delivery → delivered, etc.),
- recipient, payment (COD), and package details,
- proof of delivery when available.

## Bulk actions

Select multiple shipments to act on them together (e.g. bulk create from an
import, or bulk status changes where allowed).

## Labels & invoices

From a shipment you can print its **AWB/label** and, where applicable, its
**invoice**.

## Statuses

Your parcels move through a set of statuses shown on the timeline (created,
pickup scheduled, picked up, at hub, in transit, out for delivery, delivered,
delivery failed, returning, returned, cancelled). You can get real-time updates
automatically via [Webhooks](webhooks.md).

## Cancelling

A shipment can be cancelled only while it's still newly **created** (before
pickup is assigned). After that, it follows the normal return flow.
