{{--
    Product picker partial. Used by both:
      - /admin/parcel/create
      - /merchant-panel/parcel/create

    Hidden by default. The host page's JS fetches an endpoint that returns
    { has_fulfillment: bool, products: [...] } when the relevant merchant
    is fulfillment-enabled, then unhides this block and populates the SKU
    select with the returned options.

    Submitted shape: items[][wms_product_id], items[][quantity], items[][note]
    The controller / repo persists these as ParcelItem rows tied to the
    new parcel (snapshotting sku + name at save time).

    Required JS hooks on the host page:
      window.rlProductPickerEndpoint = "{{ route('parcel.merchantProducts') }}";
      window.rlProductPickerMerchantParam = "merchant_id"; // or null on merchant-panel
--}}
<div id="rl-product-picker" class="card rl-section-card mb-3" style="display:none;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">
                <i class="ti ti-package mr-1"></i>{{ __('merchant.fulfillment_products') }}
            </h5>
            <small class="text-muted">{{ __('merchant.fulfillment_products_help') }}</small>
        </div>
        <hr>

        <div id="rl-product-picker-empty" class="text-muted small mb-2" style="display:none;">
            {{ __('merchant.fulfillment_products_empty') }}
        </div>

        <table class="table table-sm mb-2" id="rl-product-picker-table">
            <thead>
                <tr>
                    <th style="width:55%">{{ __('merchant.product') }}</th>
                    <th style="width:15%">{{ __('levels.qty') }}</th>
                    <th style="width:25%">{{ __('levels.note') }}</th>
                    <th style="width:5%"></th>
                </tr>
            </thead>
            <tbody id="rl-product-picker-rows">
                {{-- rows added by JS --}}
            </tbody>
        </table>

        <button type="button" class="btn btn-sm btn-outline-primary" id="rl-product-picker-add">
            <i class="ti ti-plus"></i> {{ __('merchant.add_product') }}
        </button>
    </div>
</div>

<template id="rl-product-picker-row">
    <tr>
        <td>
            <select name="items[][wms_product_id]" class="form-control form-control-sm rl-product-select" required>
                <option value="">— {{ __('levels.select') }} —</option>
            </select>
        </td>
        <td><input type="number" name="items[][quantity]" class="form-control form-control-sm" min="1" value="1" required></td>
        <td><input type="text" name="items[][note]" class="form-control form-control-sm" maxlength="200"></td>
        <td><button type="button" class="btn btn-sm btn-link text-danger rl-product-row-del"><i class="ti ti-trash"></i></button></td>
    </tr>
</template>
