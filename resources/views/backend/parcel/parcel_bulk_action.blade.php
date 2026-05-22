@extends('backend.partials.master')
@section('title')
  {{ __('parcel.title') }} - {{ __('Bulk action') }}
@endsection

@section('maincontent')
<div class="container-fluid dashboard-content">
  <div class="row">
    <div class="col-xl-12">
      <div class="page-header">
        <div class="page-breadcrumb">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('parcel.dashboard') }}</a>
              </li>
              <li class="breadcrumb-item">
                <a href="{{ route('parcel.index') }}" class="breadcrumb-link">{{ __('parcel.title') }}</a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">{{ __('Bulk action') }}</li>
            </ol>
          </nav>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <h3 class="pageheader-title mb-0">{{ __('Bulk action') }}</h3>
            <span class="small text-muted">{{ __('Paste one ID per line, or separated by commas/spaces') }}</span>
          </div>

          {{-- ✅ Flash / Validation messages --}}
          <div id="flashArea" class="mt-3">
            @if (session('success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>{{ __('Success') }}:</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @if (session('warning'))
              <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>{{ __('Warning') }}:</strong> {{ session('warning') }}

                @if (session('errors_list') && is_array(session('errors_list')))
                  <details class="mt-2">
                    <summary class="text-dark" style="cursor:pointer">{{ __('Show details') }}</summary>
                    <ul class="mb-0 mt-2">
                      @foreach (session('errors_list') as $line)
                        <li>{{ $line }}</li>
                      @endforeach
                    </ul>
                  </details>
                @endif

                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @if (session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>{{ __('Error') }}:</strong> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>{{ __('Please fix the following') }}:</strong>
                <ul class="mb-0 mt-2">
                  @foreach ($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                  @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif
          </div>
          {{-- /Flash --}}

          <form method="POST" action="{{ route('parcel.bulk_action_apply') }}" id="bulkActionForm">
            @csrf

            {{-- STEP 1: Input --}}
            <div class="form-group mb-2">
              <label for="shipment_ids" class="font-weight-semibold">{{ __('Enter Shipment or Tracking IDs') }}</label>
              <textarea name="shipment_ids" id="shipment_ids" class="form-control" rows="5" placeholder="RL12345678 … one per line or separated by comma/space">{{ old('shipment_ids', 'RL620587854382') }}</textarea>
              <div class="d-flex align-items-center gap-2 mt-2">
                <button type="button" class="btn btn-info btn-sm" id="checkBtn">
                  <span class="spinner-border spinner-border-sm d-none" id="checkSpinner" aria-hidden="true"></span>
                  <span>{{ __('Check Shipments') }}</span>
                </button>
                <button type="button" class="btn btn-light btn-sm" id="clearBtn">{{ __('Clear') }}</button>
                <small id="parsedHint" class="text-muted ml-2 d-none"></small>
              </div>
            </div>

            {{-- Hidden payload to submit back after check --}}
            <input type="hidden" name="checked_ids" id="checked_ids" value="" >

            {{-- STEP 2: Actions (appear after successful check) --}}
            <fieldset id="actionBox" class="border rounded p-3 mt-3 d-none">
              <legend class="w-auto px-2 small mb-0">{{ __('Actions') }}</legend>

              <div class="form-group mb-3">
                <label class="d-block mb-2">{{ __('Select Action Type') }}</label>
                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                  <label class="btn btn-outline-warning flex-fill" id="radioAssign3pl">
                    <input type="radio" name="action_type" value="assign_3pl" autocomplete="off"> {{ __('Assign to 3PL') }}
                  </label>
                  <label class="btn btn-outline-primary flex-fill" id="radioChangeStatus">
                    <input type="radio" name="action_type" value="change_status" autocomplete="off"> {{ __('Change Status') }}
                  </label>
                </div>
              </div>

              {{-- Change status --}}
              <div class="form-group d-none" id="status_select">
                <label class="d-block mb-2">{{ __('Select Status') }}</label>
                <select name="status" class="form-control">
                  <option value="">—</option>
                  @foreach ($statuses as $status)
                    <option value="{{ $status['id'] }}">{{ $status['label'] }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Assign 3PL --}}
              <div class="form-group d-none" id="company_select">
                <label class="d-block mb-2">{{ __('Select 3PL Company') }}</label>
                <select name="company" class="custom-select">
                  <option value="">{{ __('Select 3PL Company') }}</option>
                  <option value="panda">Panda</option>
                </select>
              </div>
              
              
              {{-- Drivers list --}}
<div class="form-group d-none" id="driver_select">
  <label class="d-block mb-2">{{ __('Select Driver') }}</label>
  <select name="driver_id" class="custom-select">
    <option value="">{{ __('Select Driver') }}</option>
    @foreach ($deliverymans as $dm)
      <option value="{{ $dm->id }}">{{ $dm->name ?? ($dm->user->name ?? ('#'.$dm->id)) }}</option>
    @endforeach
  </select>
</div>

{{-- Schedule date/time (used with status=3) --}}
<div class="form-group  " id="schedule_date_group">
  <label class="d-block mb-2">{{ __('Schedule Date & Time') }}</label>
  <input type="date" name="schedule_at" class="form-control" />
</div>

{{-- Hub list --}}
<div class="form-group d-none" id="hub_select">
  <label class="d-block mb-2">{{ __('Select Hub') }}</label>
  <select name="hub_id" class="custom-select">
    <option value="">{{ __('Select Hub') }}</option>
    @foreach ($hubs as $hub)
      <option value="{{ $hub->id }}">{{ $hub->name ?? $hub->en_name ?? ('#'.$hub->id) }}</option>
    @endforeach
  </select>
  <small class="form-text text-muted d-none" id="hub_hint">
    {{ __('For Transfer To Hub, current parcel hub will be excluded (enforced on server).') }}
  </small>
</div>

{{-- Merchant list --}}
<div class="form-group d-none" id="merchant_select">
  <label class="d-block mb-2">{{ __('Select Merchant') }}</label>
  <select name="merchant_id" class="custom-select">
    <option value="">{{ __('Select Merchant') }}</option>
    @foreach ($merchants as $m)
      <option value="{{ $m->id }}">{{ $m->business_name ?? $m->name ?? ('#'.$m->id) }}</option>
    @endforeach
  </select>
</div>


              <div class="d-flex align-items-center">
                <button id="applyBtn" type="submit" class="btn btn-primary" disabled>
                  {{ __('Apply Bulk Action') }}
                </button>
                <small class="text-muted ml-3">{{ __('Choose an action, then click Apply Bulk Action') }}</small>
              </div>
            </fieldset>
          </form>

          <hr>

          {{-- STEP 3: Preview header + totals --}}
          <div class="d-flex align-items-center mb-2">
            <h5 class="mb-0">{{ __('Shipment Details Preview') }}</h5>
            <span id="previewCount" class="badge badge-secondary ml-2 d-none">0</span>

            {{-- 🔢 Totals (no canceled / return flow) --}}
            <div id="totalsArea" class="ml-3 d-none">
              <span id="totalBadge" class="badge badge-pill badge-secondary mr-1">{{ __('Total') }}: 0</span>
            </div>
          </div>

          {{-- 📊 Status distribution (non-zero only) + filter --}}
          <div class="mt-2 d-none" id="statusDistWrap">
            <div class="d-flex align-items-center">
              <h6 class="mb-0">{{ __('Status Distribution') }}</h6>
              <button type="button" id="clearFilterBtn" class="btn btn-link btn-sm p-0 ml-2 d-none">
                {{ __('Show all') }}
              </button>
            </div>
            <div id="statusChips" class="mt-1 chips"></div>
          </div>

          {{-- STEP 3: Preview table --}}
          <div class="table-responsive mt-2">
            <table class="table table-bordered table-hover" id="previewTable">
              <thead class="thead-light">
                <tr>
                  <th style="width:60px">#</th>
                  <th>{{ __('Tracking ID') }}</th>
                  <th>{{ __('Client') }}</th>
                  <th>{{ __('City') }}</th>
                  <th>{{ __('Area') }}</th>
                  <th>{{ __('Status') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr class="text-center text-muted" id="placeholderRow">
                  <td colspan="6">{{ __('Nothing to show yet. Paste IDs above and click “Check Shipments”.') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  .gap-2 { gap: .5rem; }
  details>summary { outline: none; }
  .text-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  .chips { display: flex; flex-wrap: wrap; gap: .25rem .5rem; }
  .status-chip { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
(function ($) {
  'use strict';

  // ===== DOM =====
  const $idsArea          = $('#shipment_ids');
  const $checkBtn         = $('#checkBtn');
  const $checkSpinner     = $('#checkSpinner');
  const $clearBtn         = $('#clearBtn');
  const $hint             = $('#parsedHint');

  const $previewTableBody = $('#previewTable tbody');
  const $placeholderRow   = $('#placeholderRow');
  const $previewCount     = $('#previewCount');

  const $actionBox        = $('#actionBox');
  const $applyBtn         = $('#applyBtn');
  const $statusSelectBox  = $('#status_select');       // container for status area
  const $companySelect    = $('#company_select');      // container for 3PL company
  const $checkedIds       = $('#checked_ids');

  const $totalsArea       = $('#totalsArea');
  const $totalBadge       = $('#totalBadge');

  const $statusDistWrap   = $('#statusDistWrap');
  const $statusChips      = $('#statusChips');
  const $clearFilterBtn   = $('#clearFilterBtn');

  // ===== Extra-fields for certain statuses =====
  const $statusSelect     = $('select[name="status"]');

  const $driverWrap       = $('#driver_select');
  const $dateWrap         = $('#schedule_date_group');
  const $hubWrap          = $('#hub_select');
  const $hubHint          = $('#hub_hint');
  const $merchantWrap     = $('#merchant_select');

  const $driverInput      = $('select[name="driver_id"]');
  const $dateInput        = $('input[name="schedule_at"]');
  const $hubInput         = $('select[name="hub_id"]');
  const $merchantInput    = $('select[name="merchant_id"]');

  // ===== State =====
  let allRows = [];            // full dataset from backend
  let visibleRows = [];        // rows currently shown in table
  let currentFilterId = null;  // selected status id
  let confirmOnce = false;     // guard against double-submit

  // ===== Utils =====
  const esc = (v) => $('<div>').text(v == null ? '' : String(v)).html();

  function parseIds(raw) {
    if (!raw) return [];
    const cleaned = raw
      .split(/[\n,\s]+/)
      .map(s => (s || '').trim())
      .filter(Boolean)
      .map(s => s.replace(/[^A-Za-z0-9_-]/g, ''))
      .filter(Boolean);
    const seen = new Set(), out = [];
    for (const id of cleaned) { if (!seen.has(id)) { seen.add(id); out.push(id); } }
    return out;
  }

  function setLoading(isLoading) {
    $checkSpinner.toggleClass('d-none', !isLoading);
    $checkBtn.prop('disabled', isLoading);
  }

  function toggleActions(hasResults) {
    if (hasResults) {
      $actionBox.removeClass('d-none');
      $applyBtn.prop('disabled', false);
    } else {
      $actionBox.addClass('d-none');
      $applyBtn.prop('disabled', true);
      $('input[name="action_type"]').prop('checked', false).parent('.btn').removeClass('active');
      $statusSelectBox.addClass('d-none');
      $companySelect.addClass('d-none');
      // also hide extras
      toggleStatusExtras(0);
    }
  }

  function resetUI() {
    allRows = [];
    visibleRows = [];
    currentFilterId = null;
    $previewTableBody.empty().append($placeholderRow);
    $previewCount.addClass('d-none').text('0');
    toggleActions(false);
    $totalsArea.addClass('d-none');
    $statusDistWrap.addClass('d-none');
    $statusChips.empty();
    $clearFilterBtn.addClass('d-none');
    $checkedIds.val('');
    confirmOnce = false;
  }

  // Convert badge class to btn tone (for chips)
  function toBtnClass(badgeCls) {
    let tone = 'secondary';
    if (/primary/.test(badgeCls)) tone = 'primary';
    else if (/success/.test(badgeCls)) tone = 'success';
    else if (/info/.test(badgeCls)) tone = 'info';
    else if (/warning/.test(badgeCls)) tone = 'warning';
    else if (/dark/.test(badgeCls)) tone = 'dark';
    else if (/secondary/.test(badgeCls)) tone = 'secondary';
    return 'btn btn-sm btn-' + tone;
  }

  // Keep #checked_ids in sync with the table
  function updateCheckedIdsFrom(rows) {
    const ids = (rows || []).map(r => r.id).filter(Boolean);
    $checkedIds.val(ids.join(','));
  }
  function setVisibleRows(rows) {
    visibleRows = rows.slice();
    updateCheckedIdsFrom(visibleRows);
  }

  // ===== Renderers =====
  function renderRows(rows) {
    $previewTableBody.empty();
    if (!rows || !rows.length) {
      $previewTableBody.append('<tr><td colspan="6" class="text-center text-danger">{{ __("No shipments found.") }}</td></tr>');
      $previewCount.text('0').addClass('d-none');
      toggleActions(false);
      return;
    }
    rows.forEach((parcel, i) => {
      const cls   = parcel.status_class || 'badge badge-secondary';
      const label = parcel.status_label ?? '-';
      $previewTableBody.append(`
        <tr>
          <td class="text-mono">${i + 1}</td>
          <td class="text-mono">${esc(parcel.tracking_id ?? '-')}</td>
          <td>${esc(parcel.merchant ?? '-')}</td>
          <td class="text-mono">${esc(parcel.city ?? '-')}</td>
          <td class="text-mono">${esc(parcel.area ?? '-')}</td>
          <td><span class="${esc(cls)}">${esc(label)}</span></td>
        </tr>
      `);
    });
    $previewCount.text(rows.length).removeClass('d-none');
    toggleActions(true);
  }

  function renderTotals(counts) {
    const total = counts?.total || 0;
    $totalBadge.text(`{{ __('Total') }}: ${total}`);
    $totalsArea.removeClass('d-none');
  }

  function renderStatusChips(nonZeroList) {
    $statusChips.empty();
    if (!Array.isArray(nonZeroList) || nonZeroList.length === 0) {
      $statusDistWrap.addClass('d-none');
      return;
    }
    nonZeroList.forEach(st => {
      const btnCls = toBtnClass(st.class || '');
      const activeCls = (currentFilterId === Number(st.id)) ? ' active' : '';
      $statusChips.append(
        `<button type="button" class="status-chip ${btnCls}${activeCls}" data-status-id="${esc(st.id)}">
          ${esc(st.label)}: ${esc(st.count || 0)}
        </button>`
      );
    });
    $statusDistWrap.removeClass('d-none');
  }

  function applyStatusFilter(statusId) {
    currentFilterId = statusId;
    const rows = (!statusId) ? allRows : allRows.filter(r => Number(r.status) === Number(statusId));
    renderRows(rows);
    setVisibleRows(rows);
    $statusChips.find('.status-chip').each(function(){
      const sid = Number($(this).data('status-id'));
      $(this).toggleClass('active', currentFilterId === sid);
    });
    $clearFilterBtn.toggleClass('d-none', !currentFilterId);
  }

  // ===== Status-specific extra inputs mapping =====
  // driver, date, hub, merchant, excludeCurrentHub (UI hint only; enforce in backend)
  const STATUS_RULES = {
    2:  { driver: true },                       // Pickup Assign
    3:  { driver: true, date: true },           // Pickup Re-Schedule
    5:  { hub: true },                          // Received Warehouse
    6:  { hub: true, excludeCurrentHub: true }, // Transfer To Hub
    7:  { driver: true },                       // Delivery Man Assign
    11: { hub: true },                          // Return Warehouse
    19: { hub: true },                          // Received By Hub
    26: { merchant: true },                     // Return Assign To Merchant
    30: { merchant: true },                     // Return Received By Merchant
  };

  function toggleStatusExtras(statusId) {
    const sid = Number(statusId) || 0;
    const r = STATUS_RULES[sid] || {};

    // show/hide groups
    $driverWrap.toggleClass('d-none', !r.driver);
    $dateWrap.toggleClass('d-none', !r.date);
    $hubWrap.toggleClass('d-none', !r.hub);
    $merchantWrap.toggleClass('d-none', !r.merchant);

    // hint for Transfer To Hub
    $hubHint.toggleClass('d-none', !r.excludeCurrentHub);

    // clear non-used inputs to avoid stale values
    if (!r.driver)   $driverInput.val('');
    if (!r.date)     $dateInput.val('');
    if (!r.hub)      $hubInput.val('');
    if (!r.merchant) $merchantInput.val('');
  }

  // ===== Events =====
  // Action type toggles main select boxes
  $(document).on('change', 'input[name="action_type"]', function () {
    const type = $(this).val();
    $statusSelectBox.toggleClass('d-none', type !== 'change_status');
    $companySelect.toggleClass('d-none', type !== 'assign_3pl');

    // if not change_status, hide all extras
    if (type !== 'change_status') {
      toggleStatusExtras(0);
    } else {
      // re-apply extras for current status
      toggleStatusExtras($statusSelect.val());
    }
  });

  // Status change -> show/hide extras for that status
  $statusSelect.on('change', function () {
    toggleStatusExtras($(this).val());
  });

  // Clear
  $clearBtn.on('click', function(){
    $idsArea.val('');
    $hint.addClass('d-none').text('');
    resetUI();
  });

  // chip click -> filter + sync checked_ids
  $(document).on('click', '.status-chip', function(){
    const sid = Number($(this).data('status-id'));
    if (currentFilterId === sid) applyStatusFilter(null);
    else applyStatusFilter(sid);
  });

  // When status <select> changes, keep checked_ids in sync with currently visible rows
  $(document).on('change', 'select[name="status"]', function(){
    updateCheckedIdsFrom(visibleRows);
  });

  // "Show all" button clears filter
  $clearFilterBtn.on('click', function(){
    applyStatusFilter(null);
  });

  // Check
  $checkBtn.on('click', function(){
    const raw = $idsArea.val();
    const ids = parseIds(raw);

    $hint.removeClass('d-none').text(`{{ __('Detected IDs') }}: ${ids.length}`);
    $checkedIds.val('');

    $previewTableBody.empty();
    setLoading(true);

    $.ajax({
      url: '{{ route('parcel.check_bulk_action') }}',
      type: 'POST',
      data: { _token: '{{ csrf_token() }}', ids: ids.join('\n'), hide_zero: 1 },
      success: function(resp){
        setLoading(false);
        if (!resp || !Array.isArray(resp.data)) {
          resetUI();
          return;
        }
        allRows = resp.data.slice();
        renderRows(allRows);
        setVisibleRows(allRows); // default: all selected

        if (resp.counts) {
          renderTotals(resp.counts);
          const list = Array.isArray(resp.counts.by_status_non_zero)
            ? resp.counts.by_status_non_zero
            : (resp.counts.by_status || []).filter(s => (s.count || 0) > 0);
          renderStatusChips(list);
          applyStatusFilter(null); // ensure chips state & "Show all" btn
        } else {
          $totalsArea.addClass('d-none');
          $statusDistWrap.addClass('d-none');
        }

        confirmOnce = false; // allow submit after a successful check
      },
      error: function(){
        setLoading(false);
        $previewTableBody.empty().append('<tr><td colspan="6" class="text-center text-danger">{{ __("Error fetching shipment data.") }}</td></tr>');
        toggleActions(false);
        $totalsArea.addClass('d-none');
        $statusDistWrap.addClass('d-none');
        $checkedIds.val('');
      }
    });
  });

  // ===== SweetAlert2 confirm on submit =====
  $('#bulkActionForm').off('submit').on('submit', async function(e){
    e.preventDefault();

    // keep checked_ids fresh with current visible rows
    updateCheckedIdsFrom(visibleRows);

    const count = Array.isArray(visibleRows) ? visibleRows.length : 0;
    const actionType = $('input[name="action_type"]:checked').val() || '';

    if (!count) {
      await Swal.fire({
        icon: 'warning',
        title: '{{ __("Nothing to apply") }}',
        text: '{{ __("There are no shipments in the current selection.") }}',
        confirmButtonText: '{{ __("OK") }}'
      });
      return false;
    }
    if (!actionType) {
      await Swal.fire({
        icon: 'warning',
        title: '{{ __("Select an action") }}',
        text: '{{ __("Please choose an action type (Change Status or Assign to 3PL).") }}',
        confirmButtonText: '{{ __("OK") }}'
      });
      return false;
    }

    let actionLabel = (actionType === 'change_status')
      ? '{{ __("Change Status") }}'
      : '{{ __("Assign to 3PL") }}';

    // Validate extras if change_status
    let summaryExtras = '';
    if (actionType === 'change_status') {
      const sid = Number($statusSelect.val());
      const rules = STATUS_RULES[sid] || {};

      const $statusOpt     = $statusSelect.find('option:selected');
      const statusVal      = ($statusOpt.val() || '').toString().trim();
      const statusText     = ($statusOpt.text() || '').trim();

      if (!statusVal) {
        await Swal.fire({
          icon: 'warning',
          title: '{{ __("Missing status") }}',
          text: '{{ __("Please select a status to apply.") }}',
          confirmButtonText: '{{ __("OK") }}'
        });
        return false;
      }

      // Hard validations per rules
      if (rules.driver && !$driverInput.val()) {
        await Swal.fire({ icon: 'warning', title: '{{ __("Missing driver") }}', text: '{{ __("Please select a driver.") }}', confirmButtonText: '{{ __("OK") }}' });
        return false;
      }
      if (rules.date && !$dateInput.val()) {
        await Swal.fire({ icon: 'warning', title: '{{ __("Missing date/time") }}', text: '{{ __("Please select a date/time.") }}', confirmButtonText: '{{ __("OK") }}' });
        return false;
      }
      if (rules.hub && !$hubInput.val()) {
        await Swal.fire({ icon: 'warning', title: '{{ __("Missing hub") }}', text: '{{ __("Please select a hub.") }}', confirmButtonText: '{{ __("OK") }}' });
        return false;
      }
      if (rules.merchant && !$merchantInput.val()) {
        await Swal.fire({ icon: 'warning', title: '{{ __("Missing merchant") }}', text: '{{ __("Please select a merchant.") }}', confirmButtonText: '{{ __("OK") }}' });
        return false;
      }

      // Build extras summary (only include those required)
      summaryExtras += `<div><strong>{{ __("Status") }}:</strong> ${esc(statusText)}</div>`;
      if (rules.driver) {
        const driverText = ($driverInput.find('option:selected').text() || '').trim();
        summaryExtras += `<div><strong>{{ __("Driver") }}:</strong> ${esc(driverText)}</div>`;
      }
      if (rules.date) {
        summaryExtras += `<div><strong>{{ __("Schedule") }}:</strong> ${esc($dateInput.val())}</div>`;
      }
      if (rules.hub) {
        const hubText = ($hubInput.find('option:selected').text() || '').trim();
        summaryExtras += `<div><strong>{{ __("Hub") }}:</strong> ${esc(hubText)}</div>`;
      }
      if (rules.merchant) {
        const merchantText = ($merchantInput.find('option:selected').text() || '').trim();
        summaryExtras += `<div><strong>{{ __("Merchant") }}:</strong> ${esc(merchantText)}</div>`;
      }
    } else if (actionType === 'assign_3pl') {
      const $opt = $('select[name="company"] option:selected');
      const companyVal = ($opt.val() || '').toString().trim();
      const companyText = ($opt.text() || '').trim();
      if (!companyVal) {
        await Swal.fire({
          icon: 'warning',
          title: '{{ __("Missing 3PL company") }}',
          text: '{{ __("Please select a 3PL company.") }}',
          confirmButtonText: '{{ __("OK") }}'
        });
        return false;
      }
      summaryExtras += `<div><strong>{{ __("Company") }}:</strong> ${esc(companyText)}</div>`;
    }

    const htmlSafe = `
      <div class="text-left">
        <div><strong>{{ __("Number of shipments") }}:</strong> ${count}</div>
        <div><strong>{{ __("Action type") }}:</strong> ${esc(actionLabel)}</div>
        ${summaryExtras}
      </div>
    `;

    const result = await Swal.fire({
      icon: 'question',
      title: '{{ __("Confirm bulk action?") }}',
      html: htmlSafe,
      showCancelButton: true,
      confirmButtonText: '{{ __("Confirm") }}',
      cancelButtonText: '{{ __("Cancel") }}',
      reverseButtons: true,
      focusCancel: true
    });

    if (!result.isConfirmed) return false;

    if (!confirmOnce) {
      confirmOnce = true;
      // Unbind this handler and submit the real form
      $('#bulkActionForm').off('submit');
      $('#bulkActionForm')[0].submit();
    }
    return true;
  });

  // ===== INIT =====
  // If page loads with a preselected action/status
  const initType = $('input[name="action_type"]:checked').val() || '';
  $statusSelectBox.toggleClass('d-none', initType !== 'change_status');
  $companySelect.toggleClass('d-none', initType !== 'assign_3pl');
  toggleStatusExtras($statusSelect.val());

  // (Optional) enable select2 if wanted
  // $('select[name="status"], select[name="driver_id"], select[name="hub_id"], select[name="merchant_id"], select[name="company"]').select2({ width: '100%' });

})(jQuery);
</script>
@endpush
