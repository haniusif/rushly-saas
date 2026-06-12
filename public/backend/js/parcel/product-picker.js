/**
 * Product picker controller for the parcel-create form.
 *
 * Reads two globals set by the host page:
 *   window.rlProductPickerEndpoint     — URL to GET the merchant's products
 *   window.rlProductPickerMerchantSel  — CSS selector for the merchant <select>
 *                                        (null on merchant-panel; merchant is
 *                                         the logged-in user, no select needed)
 *
 * Endpoint returns { has_fulfillment: bool, products: [{id,sku,name,unit}, ...] }.
 * When has_fulfillment is false OR products is empty, the picker stays hidden.
 *
 * Submitted form fields:
 *   items[][wms_product_id]
 *   items[][quantity]
 *   items[][note]
 */
(function () {
    var picker      = document.getElementById('rl-product-picker');
    var empty       = document.getElementById('rl-product-picker-empty');
    var tableBody   = document.getElementById('rl-product-picker-rows');
    var addBtn      = document.getElementById('rl-product-picker-add');
    var rowTemplate = document.getElementById('rl-product-picker-row');

    if (!picker || !addBtn || !rowTemplate || !tableBody) return;

    var endpoint = window.rlProductPickerEndpoint;
    var merchantSel = window.rlProductPickerMerchantSel || null;
    if (!endpoint) return;

    var availableProducts = [];

    function renderRowSelect(select) {
        select.innerHTML = '<option value="">— —</option>';
        availableProducts.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = (p.sku ? '[' + p.sku + '] ' : '') + p.name;
            select.appendChild(opt);
        });
    }

    function addRow() {
        if (!availableProducts.length) return;
        var frag = rowTemplate.content.cloneNode(true);
        var select = frag.querySelector('select.rl-product-select');
        renderRowSelect(select);
        var delBtn = frag.querySelector('.rl-product-row-del');
        delBtn.addEventListener('click', function (e) {
            e.target.closest('tr').remove();
        });
        tableBody.appendChild(frag);
    }

    function load(merchantId) {
        var url = endpoint;
        if (merchantSel && merchantId) {
            url += (url.indexOf('?') > -1 ? '&' : '?') + 'merchant_id=' + encodeURIComponent(merchantId);
        }
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }})
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.has_fulfillment) {
                    picker.style.display = 'none';
                    tableBody.innerHTML = '';
                    return;
                }
                availableProducts = data.products || [];
                picker.style.display = '';
                if (!availableProducts.length) {
                    empty.style.display = '';
                    tableBody.innerHTML = '';
                } else {
                    empty.style.display = 'none';
                    // If the table is empty, seed one row for the user.
                    if (!tableBody.children.length) addRow();
                    // Re-render selects in any existing rows so they reflect
                    // the newly loaded products.
                    Array.prototype.forEach.call(tableBody.querySelectorAll('select.rl-product-select'), renderRowSelect);
                }
            })
            .catch(function () { picker.style.display = 'none'; });
    }

    addBtn.addEventListener('click', addRow);

    if (merchantSel) {
        var merchantEl = document.querySelector(merchantSel);
        if (merchantEl) {
            // jQuery select2 fires 'change' on the underlying <select> — plain
            // listener catches both jQuery-triggered and native events.
            merchantEl.addEventListener('change', function () { load(this.value); });
            // Honor server-side old() pre-selection.
            if (merchantEl.value) load(merchantEl.value);
        }
    } else {
        // Merchant-panel: merchant is implicit.
        load(null);
    }
})();
