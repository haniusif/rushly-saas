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
            <ol class="breadcrumb">
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

      <div class="card">
        <div class="card-body">
          <h3 class="pageheader-title mb-4">{{ __('Bulk action') }}</h3>

          {{-- ✅ Flash / Validation messages --}}
          <div id="flashArea" class="mb-3">
            {{-- Success --}}
            @if (session('success'))
              <div class="alert alert-success  fade show" role="alert">
                <strong>{{ __('Success') }}:</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            {{-- Warning (e.g., partial 3PL success with failures) --}}
            @if (session('warning'))
              <div class="alert alert-warning  fade show" role="alert">
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

            {{-- Error --}}
            @if (session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>{{ __('Error') }}:</strong> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            @endif

            {{-- Validation errors --}}
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
          {{-- /Flash / Validation messages --}}

          <form method="POST" action="{{ route('parcel.bulk_action_apply') }}" id="bulkActionForm">
            @csrf

            <div class="form-group">
              <label for="shipment_ids">{{ __('Enter Shipment or Tracking IDs') }}</label>
              <textarea name="shipment_ids" id="shipment_ids" class="form-control" rows="5" 
                        placeholder="RL12345678 or shipment IDs, one per line">{{ old('shipment_ids' , 'RL620587854382') }}</textarea>
              <button type="button" class="btn btn-info btn-sm mt-2" id="checkBtn">
                {{ __('Check Shipments') }}
              </button>
            </div>

            {{-- ✅ Action box appears after successful check --}}
            <div id="actionBox" class="border rounded p-3 mt-3 d-none">
              <div class="form-group mb-3">
                <label class="d-block mb-2">{{ __('Select Action Type') }}</label>
                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                  {{-- Uncomment if you want to enable these later
                  <label class="btn btn-outline-primary flex-fill">
                    <input type="radio" name="action_type" value="change_status" autocomplete="off">
                    {{ __('Change Status') }}
                  </label>
                  <label class="btn btn-outline-success flex-fill">
                    <input type="radio" name="action_type" value="assign_deliveryman" autocomplete="off">
                    {{ __('Assign to Deliveryman') }}
                  </label>
                  --}}
                  <label class="btn btn-outline-warning flex-fill">
                    <input type="radio" name="action_type" value="assign_3pl" autocomplete="off">
                    {{ __('Assign to 3PL') }}
                  </label>
                </div>
              </div>

              {{-- Change status (hidden by default) --}}
              <div class="form-group" id="status_select">
                <label class="d-block mb-2">{{ __('Select Status') }}</label>
                <select name="status" class="form-control">
                  @foreach (trans('parcelStatusFilter') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Assign deliveryman (hidden by default) --}}
              <div class="form-group d-none" id="deliveryman_select">
                <label class="d-block mb-2">{{ __('Select Deliveryman') }}</label>
                <select name="deliveryman_id" class="form-control select2">
                  @foreach ($deliverymans ?? [] as $deliveryman)
                    <option value="{{ $deliveryman->id }}">{{ $deliveryman->user->name ?? '' }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Assign 3PL --}}
              <div class="form-group d-none" id="company_select">
                <label class="d-block mb-2">{{ __('Select 3PL Company') }}</label>
                <select name="company" class="custom-select" required>
                  <option value="">{{ __('Select 3PL Company') }}</option>
                  <option value="panda">Panda</option>
                  {{-- <option value="zajil">Zajil</option> --}}
                </select>
              </div>

              <small class="text-muted d-block">{{ __('Choose an action, then click Apply Bulk Action') }}</small>
            </div>

            <button id="applyBtn" type="submit" class="btn btn-primary mt-2" disabled>
              {{ __('Apply Bulk Action') }}
            </button>
          </form>

          <hr>

          <h5 class="d-flex align-items-center gap-2">
            {{ __('Shipment Details Preview') }}
            <span id="previewCount" class="badge badge-secondary ml-2 d-none">0</span>
          </h5>

          <div class="table-responsive">
            <table class="table table-bordered" id="previewTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>{{ __('Tracking ID') }}</th>
                  <th>{{ __('Customer Name') }}</th>
                  <th>{{ __('Customer Phone') }}</th>
                  <th>{{ __('Status') }}</th>
                </tr>
              </thead>
              <tbody>
                {{-- filled dynamically --}}
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
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  $(document).ready(function() {
    $('.select2').select2();

    // Auto-hide success alerts after 6s (optional)
    setTimeout(function() {
      $('.alert-success').alert('close');
    }, 6000);

    // Show/Hide dependent fields
    $(document).on('change', 'input[name="action_type"]', function () {
      const type = $(this).val();
      $('#status_select').addClass('d-none');
      $('#deliveryman_select').addClass('d-none');
      $('#company_select').addClass('d-none');

      if (type === 'change_status') {
        $('#status_select').removeClass('d-none');
      } else if (type === 'assign_deliveryman') {
        $('#deliveryman_select').removeClass('d-none');
      } else if (type === 'assign_3pl') {
        $('#company_select').removeClass('d-none');
      }
    });

    function toggleActions(hasResults) {
      if (hasResults) {
        $('#actionBox').removeClass('d-none');
        $('#applyBtn').prop('disabled', false);
      } else {
        $('#actionBox').addClass('d-none');
        $('#applyBtn').prop('disabled', true);
        $('input[name="action_type"]').prop('checked', false).parent('.btn').removeClass('active');
        $('#status_select, #deliveryman_select, #company_select').addClass('d-none');
      }
    }

    const loadingRowHtml = `
      <tr id="loadingRow">
        <td colspan="5" class="text-center">
          <span class="spinner-border spinner-border-sm"></span>
          <span class="mx-1">{{ __('Loading...') }}</span>
        </td>
      </tr>`;

    $('#checkBtn').on('click', function() {
      const ids = $('#shipment_ids').val();
      const tbody = $('#previewTable tbody');

      tbody.empty().append(loadingRowHtml);
      $('#previewCount').addClass('d-none').text('0');
      toggleActions(false);

      $.ajax({
        url: '{{ route("parcel.check_bulk_action") }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}', ids: ids },
        success: function(response) {
          tbody.empty();

          if (!response.data || response.data.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-danger">{{ __("No shipments found.") }}</td></tr>');
            toggleActions(false);
          } else {
            response.data.forEach((parcel, index) => {
              tbody.append(`
                <tr>
                  <td>${index + 1}</td>
                  <td>${parcel.tracking_id ?? '-'}</td>
                  <td>${parcel.customer_name ?? '-'}</td>
                  <td>${parcel.customer_phone ?? '-'}</td>
                  <td>${parcel.status_label ?? '-'}</td>
                </tr>
              `);
            });
            $('#previewCount').text(response.data.length).removeClass('d-none');
            toggleActions(true);
          }
        },
        error: function() {
          alert('{{ __("Error fetching shipment data.") }}');
          tbody.empty();
          toggleActions(false);
        }
      });
    });

    // Client-side guard before submit
    $('#bulkActionForm').on('submit', function(e) {
      if (!$('#actionBox').hasClass('d-none')) {
        const type = $('input[name="action_type"]:checked').val() || '';
        if (type === 'change_status' && !$('select[name="status"]').val()) {
          e.preventDefault(); alert('{{ __("Please select a status.") }}'); return false;
        }
        if (type === 'assign_deliveryman' && !$('select[name="deliveryman_id"]').val()) {
          e.preventDefault(); alert('{{ __("Please select a deliveryman.") }}'); return false;
        }
        if (type === 'assign_3pl' && !$('select[name="company"]').val()) {
          e.preventDefault(); alert('{{ __("Please select a 3PL company.") }}'); return false;
        }
      }
    });
  });
</script>
@endpush
