# `/admin/deliveryman/create` — courier wizard

`resources/js/Pages/Admin/Deliveryman/Create.jsx`

7-step wizard for onboarding a courier (deliveryman). Replaces the legacy
948-line Blade form (`resources/views/backend/deliveryman/create.blade.php`,
which is still on disk but no longer reachable from this controller path).

## Controller

`App\Http\Controllers\Backend\DeliveryManController::create()` —
permission gated by `delivery_man_create`.

Returns:

```php
Inertia::render('Admin/Deliveryman/Create', [
    'hubs'              => $hubs->map->only(['id', 'name'])->values(),
    'supplierCompanies' => ...,
    'operationalAreas'  => ...,
    'managers'          => User::whereIn('user_type', [ADMIN, INCHARGE, HUB])
                              ->where('company_id', settings()->id)
                              ->orderBy('name')->get(['id', 'name', 'user_type']),
    'nationalities'     => Nationality::active()->orderBy('sorting')->orderBy('name')
                              ->get(['id', 'name', 'en_name', 'code']),
    't'                 => $this->deliverymanLabels(),
]);
```

`deliverymanLabels()` flattens `lang/{locale}/deliveryman.php` plus a few
`lang/{locale}/levels.php` keys to a single string-keyed map.

## Submit

POST to `route('deliveryman.store')` via `useForm.post(...)` with
`forceFormData: true` (the documents step uploads files). Server-side
validation lives in `App\Http\Requests\DeliveryMan\DeliveryManRequest`;
errors come back through `form.errors` and the wizard auto-jumps to the
first step with an error.

## Wizard structure

| Step | Key | Required-on-step fields | Notes |
|---|---|---|---|
| 1 | Basic | name, mobile, email, password | name_en, alt_mobile, gender, dob, nationality also here |
| 2 | ID | — | id_type (national_id/iqama), id_number, id_expiry, id_image_id (file) |
| 3 | Address | address | district, short_national_address |
| 4 | Employment | driver_type, status, hub_id (+ employee_number when company_courier, + supplier_company_id when outsourced) | joining_date, contract_end_date, direct_manager_id, operational_area_id, salary, delivery_charge, pickup_charge, return_charge, opening_balance |
| 5 | License | — | license_number, license_expiry, iqama_expiry |
| 6 | Bank | — (only visible when `driver_type === 'freelancer'`) | bank_account_no, iban |
| 7 | Documents | — | image_id, driving_license_image_id, iqama_image_id*, contract_image_id*, promissory_note_image_id* (* freelancer-only) |

`STEPS` constant carries `{ id, key, icon, showFor? }`. `visibleSteps`
filters out anything whose `showFor` doesn't include the current
`driver_type`. If the user is on a step that becomes hidden mid-flow, the
wizard falls back to the nearest previous visible step.

## Stepper UI

- Pill list above the form: active / done / error / disabled-skip states
- Thin gradient progress bar across the top
- Each pill renders the step icon, number (or checkmark when done), and
  label (label hidden on small screens)

`stepsWithErrors` recomputes which steps contain an `errors[*]` entry
whenever `form.errors` changes, so server-side validation paints the
matching pill red.

## File uploads

`FileInput` is a custom component that shows a dotted-border drop zone
with a 40×40 thumbnail. The hidden `<input type="file">` is wrapped in a
label so the whole zone is clickable. On selection: `URL.createObjectURL`
generates a local preview.

## Live summary panel

Right column shows initials avatar, mobile, email, nationality, driver
type pill, hub name, status pill, joining date, and a contract-expiry
warning (when contract ends within 30 days). All derived from
`form.data` — no server round-trip.

## Status / type label maps

Inline in the component:

```js
const typeLabels = {
  freelancer:      t.driver_type_freelancer,
  outsourced:      t.driver_type_outsourced,
  company_courier: t.driver_type_company_courier,
};
const statusLabels = {
  '1': t.status_active, '2': t.status_suspended,
  '3': t.status_leave,  '4': t.status_terminated,
};
```

## Translation map (`t`)

Pulled from `lang/{locale}/deliveryman.php`. Keys are flat (no nesting).
If a new field gets added on the server, also add the matching key in
`deliverymanLabels()` in the controller.
