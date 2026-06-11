<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>TMS Dashboard</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      background: #f8f9fa;
      font-family: 'Cairo', sans-serif;
      /* full remaining viewport height for the map/side panels (navbar + top cards offset) */
      --tms-panel-h: calc(100vh - 200px);
    }
    @media (max-width: 768px) {
      body { --tms-panel-h: 70vh; }
    }

    .status-card { color: #fff; border-radius: 10px; padding: 12px; text-align: center; }
    .side-status { background: #fff; border-radius: 10px; padding: 12px; font-size: 14px; height: var(--tms-panel-h); overflow-y: auto; }
    .side-status div { border-bottom: 1px solid #eee; padding: 7px 0; }
    .side-status span { float: right; color: #e53935; font-weight: bold; }
    #map { width: 100%; height: var(--tms-panel-h); border-radius: 10px; }

    .drivers-card { background: #fff; border-radius: 10px; overflow: hidden; height: var(--tms-panel-h); display: flex; flex-direction: column; }
    .drivers-list { flex: 1; overflow-y: auto; transition: all 0.3s ease; }
    .driver-item { border-bottom: 1px solid #eee; padding: 10px; display: flex; justify-content: space-between; align-items: center; transition: opacity 0.3s ease; }
    .driver-info { display: flex; align-items: center; }
    .driver-info i { font-size: 28px; color: #ff9800; margin-right: 10px; }
    .driver-info small { color: #777; }
    .map-toolbar { position: absolute; top: 10px; left: 10px; right: 10px; z-index: 5; background: #fff; padding: 8px 10px; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }

    /* Bulk selection */
    .bulk-action-bar { display: none; align-items: center; justify-content: space-between; gap: 10px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; }
    .bulk-action-bar.is-active { display: flex; }
    .bulk-action-bar .bulk-count { font-weight: 700; color: #1d4ed8; }
    .driver-item .driver-check { margin-right: 8px; flex: 0 0 auto; }
    .driver-item .driver-info { flex: 1; min-width: 0; }
    .driver-item.is-selected { background: #f0f9ff; }
    .select-all-row { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 13px; }
    .select-all-row label { margin: 0; cursor: pointer; font-weight: 600; color: #475569; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-sm navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="{{ route('tms') }}">TMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mynavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mynavbar">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="/">{{ __("Dashboard") }}</a>
        </li>
      
      </ul>
      <form class="d-flex" method="GET" action="{{ route('tms') }}" >
        <input class="form-control me-2" name="date" type="date" value="{{ request('date', \Carbon\Carbon::today()->format('Y-m-d')) }}" placeholder="Search">
        <button class="btn btn-primary" type="submit">{{ __('Filter') }}</button>
      </form>
    </div>
  </div>
</nav>

<div class="container-fluid mt-3">
 


  <!-- Top Cards -->
<div class="row g-2 mb-3">
  @foreach ([
    ['New Shipments', 'linear-gradient(45deg, #8e2de2, #4a00e0)'],
    ['Ready for pick-up', 'linear-gradient(45deg, #ff416c, #ff4b2b)'],
    ['Picked', 'linear-gradient(45deg, #f7971e, #ffd200)'],
    ['OFD', 'linear-gradient(45deg, #00b09b, #96c93d)'],
    
    ['Not Delivered', 'linear-gradient(45deg, #c31432, #240b36)'],
    ['Delivered', 'linear-gradient(45deg, #00b09b, #96c93d)']
  ] as $status)
    <div class="col-md-2">
      <div class="status-card text-center text-white p-3 rounded" style="background: {{ $status[1] }}">
        <h4 class="mb-1">{{ $stats[$status[0]] ?? 0 }}</h4>
        <p class="mb-0 small">{{ $status[0] }}</p>
      </div>
    </div>
  @endforeach
</div>


  <div class="row g-3">
    <!-- Left Status -->

<div class="col-md-2">
  <div class="side-status shadow-sm">
    @foreach ($grouped as $status => $parcels)
      <div 
        class="status-item" 
        data-status="{{ $status }}" 
        data-parcels='@json($parcels)'
        style="cursor:pointer;">
        {{ $status }} <span>{{ count($parcels) }}</span>
      </div>
    @endforeach
  </div>
</div>



    <!-- Map Section -->
    <div class="col-md-7 position-relative">
      <div class="map-toolbar">
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-outline-success btn-sm">Online ({{ $onlineCount ?? 0 }})</button>
          <button class="btn btn-outline-secondary btn-sm">Offline ({{ $offlineCount ?? 0 }})</button>
          <button class="btn btn-outline-primary btn-sm">All ({{ $totalCount ?? 0 }})</button>
        </div>

        <div class="d-flex gap-2">
          <select id="hubSelect" class="form-select form-select-sm" style="width: 150px;">
            <option value="" selected>Select a Hub</option>
            @foreach($hubs as $hub)
              <option value="{{ $hub['id'] }}" data-lat="{{ $hub['hub_lat'] }}" data-lng="{{ $hub['hub_long'] }}">
                {{ $hub['name'] }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div id="map"></div>
    </div>

    <!-- Drivers -->
    <div class="col-md-3">
      <div class="drivers-card shadow-sm">
        <!-- Tabs -->
        <ul class="nav nav-tabs nav-justified bg-light" id="driverTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="packages-tab" data-bs-toggle="tab" data-bs-target="#packages" type="button" role="tab" aria-controls="packages" aria-selected="true">Packages</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="nopackages-tab" data-bs-toggle="tab" data-bs-target="#nopackages" type="button" role="tab" aria-controls="nopackages" aria-selected="false">No Packages</button>
          </li>
        </ul>

        <!-- Search -->
        <div class="p-2 border-bottom bg-white">
          <input type="text" id="driverSearch" class="form-control form-control-sm" placeholder="Search driver...">
        </div>

        <!-- Bulk action bar -->
        <div class="px-2 pt-2">
          <div id="bulkActionBar" class="bulk-action-bar">
            <span class="bulk-count"><span id="bulkCount">0</span> selected</span>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkClearBtn">Clear</button>
              <button type="button" class="btn btn-sm btn-primary" id="bulkPrintBtn">
                <i class="fa-solid fa-print me-1"></i> Print selected
              </button>
            </div>
          </div>
        </div>

        <!-- Tabs Content -->
        <div class="tab-content drivers-list" id="driverTabsContent">
          <!-- With Shipments -->
 <div class="tab-pane fade show active" id="packages" role="tabpanel" aria-labelledby="packages-tab">
  @if(count($with_shipments) > 0)
    <div class="select-all-row">
      <input type="checkbox" id="selectAllDrivers" class="form-check-input m-0">
      <label for="selectAllDrivers">Select all</label>
    </div>
  @endif
  @forelse($with_shipments as $driver)
    <div class="driver-item" data-driver-id="{{ $driver['driver_id'] }}" data-driver-name="{{ $driver['name'] }}">
      <input type="checkbox" class="form-check-input driver-check" value="{{ $driver['driver_id'] }}">
      <div class="driver-info">
        <i class="fa-solid fa-user-tie"></i>
        <div>
          <div class="fw-bold driver-name">{{ $driver['name'] }}</div>
          <small>
            <strong>Total:</strong> {{ $driver['shipment_count'] }} |
            <strong>DL:</strong> {{ $driver['total_delivered'] ?? 0 }} |
            <strong>OFD:</strong> {{ $driver['total_pending'] ?? 0 }}
          </small>
        </div>
      </div>

      <div class="driver-right text-end">
        <!-- Print Button -->
        <button
          value="{{ $driver['driver_id'] }}"
          data-name="{{ $driver['name'] }}"
          class="btn btn-outline-primary btn-sm btn-print-driver">
          Print
        </button>

        @php
          $percent = $driver['shipment_count'] > 0
              ? round(($driver['total_delivered'] / $driver['shipment_count']) * 100, 1)
              : 0;

          if ($percent == 100) {
              $barClass = 'bg-success';
          } elseif ($percent >= 50) {
              $barClass = 'bg-info';
          } else {
              $barClass = 'bg-danger';
          }
        @endphp

        <!-- Progress Bar -->
        <div class="progress mt-2" style="height: 6px; width: 100px;">
          <div class="progress-bar {{ $barClass }}" 
               role="progressbar" 
               style="width: {{ $percent }}%;" 
               aria-valuenow="{{ $percent }}" 
               aria-valuemin="0" 
               aria-valuemax="100">
          </div>
        </div>
        <small class="text-muted">{{ $percent }}%</small>
      </div>
    </div>
  @empty
    <div class="text-center p-3 text-muted">No drivers with shipments.</div>
  @endforelse
</div>

          <!-- Without Shipments -->
          <div class="tab-pane fade" id="nopackages" role="tabpanel" aria-labelledby="nopackages-tab">
            @forelse($without_shipments as $driver)
              <div class="driver-item">
                <div class="driver-info">
                  <i class="fa-solid fa-user-tie"></i>
                  <div>
                    <div class="fw-bold driver-name">{{ $driver['name'] }}</div>
                    <small>No Packages</small>
                  </div>
                </div>
                <div class="driver-right text-end">
                  <small class="text-muted">—</small>
                </div>
              </div>
            @empty
              <div class="text-center p-3 text-muted">All drivers have packages.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Print Options Modal -->

<!-- Print Options Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="printModalLabel">
          <i class="fa-solid fa-print me-2"></i> Print Driver Report
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="printForm">
          <input type="hidden" id="selectedDriverId">
          <input type="hidden" id="selectedDriverIds">

          <!-- 👇 Driver Name (single) -->
          <p class="fw-bold mb-2" id="singleDriverInfo">
            Driver: <span id="selectedDriverName" class="text-primary"></span>
          </p>

          <!-- 👇 Bulk Info -->
          <div class="alert alert-info py-2 mb-2" id="bulkDriverInfo" style="display:none;">
            <strong>Bulk print</strong> · <span id="bulkDriverCount">0</span> drivers selected
            <ul class="mb-0 mt-2 small" id="bulkDriverList" style="max-height:120px; overflow-y:auto; padding-inline-start:18px;"></ul>
          </div>

          <hr>

          <p class="fw-semibold">Choose output format:</p>

          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="printFormat" id="printExcel" value="excel" checked>
            <label class="form-check-label" for="printExcel">
              <i class="fa-solid fa-file-excel text-success me-2"></i> Excel Sheet
            </label>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="printFormat" id="printPDF" value="pdf">
            <label class="form-check-label" for="printPDF">
              <i class="fa-solid fa-file-pdf text-danger me-2"></i> PDF / Print
            </label>
          </div>

          <div class="text-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fa-solid fa-arrow-left me-1"></i> Back
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-check me-1"></i> Apply
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Parcels List Modal -->
<div class="modal fade" id="parcelsModal" tabindex="-1" aria-labelledby="parcelsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="parcelsModalLabel">
          <i class="fa-solid fa-truck me-2"></i> Parcels List
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="parcelTableWrapper"></div>
      </div>
    </div>
  </div>
</div>



<!-- Google Maps -->
<script>
  let map, hubMarker;
  let markers = [];

  function initMap() {
    const hubs = @json($hubs);
    let center = { lat: 23.8859, lng: 45.0792 };

    if (hubs.length > 0) {
      center = {
        lat: parseFloat(hubs[0].hub_lat),
        lng: parseFloat(hubs[0].hub_long)
      };
    }

    map = new google.maps.Map(document.getElementById("map"), {
      zoom: 8,
      center,
    });

    const locations = @json($locations);
    renderMarkers(locations);

    if (hubs.length > 0) {
      const firstHub = hubs[0];
      moveToHub(firstHub.name, parseFloat(firstHub.hub_lat), parseFloat(firstHub.hub_long));
    }

    const hubSelect = document.getElementById("hubSelect");
    if (hubSelect) {
      hubSelect.addEventListener("change", function() {
        const option = this.options[this.selectedIndex];
        const lat = parseFloat(option.dataset.lat);
        const lng = parseFloat(option.dataset.lng);
        const name = option.textContent;
        if (lat && lng) moveToHub(name, lat, lng);
      });
    }

    document.querySelector(".btn-outline-success").addEventListener("click", () => filterMarkers("online"));
    document.querySelector(".btn-outline-secondary").addEventListener("click", () => filterMarkers("offline"));
    document.querySelector(".btn-outline-primary").addEventListener("click", () => filterMarkers("all"));
  }

  function renderMarkers(locations) {
    clearMarkers();
    locations.forEach(dm => {
      if (dm.lat && dm.lng) {
        const marker = new google.maps.Marker({
          position: { lat: dm.lat, lng: dm.lng },
          map,
          title: dm.name,
          icon: {
            url: dm.status == 1
              ? "{{ static_asset('images/icons/car_map_on.svg') }}"
              : "{{ static_asset('images/icons/car_map_off.svg') }}",
            scaledSize: new google.maps.Size(38, 38)
          }
        });

        const infoWindow = new google.maps.InfoWindow({
          content: `<strong>${dm.name}</strong><br>${dm.mobile ?? ''}`
        });
        marker.addListener("click", () => infoWindow.open(map, marker));
        markers.push({ marker, status: dm.status, lat: dm.lat, lng: dm.lng });
      }
    });
  }

  function clearMarkers() {
    markers.forEach(obj => obj.marker.setMap(null));
    markers = [];
  }

  function moveToHub(name, lat, lng) {
    if (hubMarker) hubMarker.setMap(null);
    const position = { lat, lng };

    hubMarker = new google.maps.Marker({
      position,
      map,
      title: name,
      icon: {
        url: "{{ static_asset('images/icons/hub_pin.svg') }}",
        scaledSize: new google.maps.Size(45, 45)
      }
    });

    const infoWindow = new google.maps.InfoWindow({
      content: `<strong>${name}</strong><br>Hub Location`
    });
    hubMarker.addListener("click", () => infoWindow.open(map, hubMarker));

    map.panTo(position);
    map.setZoom(9);
  }

  function filterMarkers(type) {
    const visible = [];
    markers.forEach(obj => {
      const show = type === 'all' || (type === 'online' && obj.status == 1) || (type === 'offline' && obj.status == 0);
      obj.marker.setMap(show ? map : null);
      if (show) visible.push(obj.marker);
    });
    if (visible.length > 0) {
      const bounds = new google.maps.LatLngBounds();
      visible.forEach(m => bounds.extend(m.getPosition()));
      map.fitBounds(bounds);
    } else {
      map.setZoom(5);
      map.panTo({ lat: 23.8859, lng: 45.0792 });
    }
  }

  // 🔍 Search Functionality
  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("driverSearch");
    searchInput.addEventListener("keyup", function() {
      const query = this.value.toLowerCase();
      document.querySelectorAll(".driver-item").forEach(item => {
        const name = item.querySelector(".driver-name")?.textContent.toLowerCase() || "";
        item.style.display = name.includes(query) ? "flex" : "none";
      });
    });
  });
</script>


<script>
  document.addEventListener('DOMContentLoaded', () => {
    const printModal = new bootstrap.Modal(document.getElementById('printModal'));
    const selectedDriverIdInput  = document.getElementById('selectedDriverId');
    const selectedDriverIdsInput = document.getElementById('selectedDriverIds');
    const selectedDriverNameSpan = document.getElementById('selectedDriverName');
    const singleInfo = document.getElementById('singleDriverInfo');
    const bulkInfo   = document.getElementById('bulkDriverInfo');
    const bulkCountEl = document.getElementById('bulkDriverCount');
    const bulkListEl  = document.getElementById('bulkDriverList');
    const modalLabel  = document.getElementById('printModalLabel');

    function openSingleMode(driverId, driverName) {
      selectedDriverIdInput.value  = driverId;
      selectedDriverIdsInput.value = '';
      selectedDriverNameSpan.textContent = driverName || 'Unknown Driver';
      singleInfo.style.display = '';
      bulkInfo.style.display = 'none';
      modalLabel.innerHTML = '<i class="fa-solid fa-print me-2"></i> Print Driver Report';
      printModal.show();
    }

    function openBulkMode(drivers) {
      selectedDriverIdInput.value  = '';
      selectedDriverIdsInput.value = drivers.map(d => d.id).join(',');
      singleInfo.style.display = 'none';
      bulkInfo.style.display = '';
      bulkCountEl.textContent = drivers.length;
      bulkListEl.innerHTML = drivers.map(d => `<li>${d.name}</li>`).join('');
      modalLabel.innerHTML = '<i class="fa-solid fa-print me-2"></i> Print Selected Drivers';
      printModal.show();
    }

    // Per-driver Print button
    document.querySelectorAll('.btn-print-driver').forEach(btn => {
      btn.addEventListener('click', function() {
        openSingleMode(this.value, this.dataset.name);
      });
    });

    // ===== Bulk selection wiring =====
    const bulkBar      = document.getElementById('bulkActionBar');
    const bulkCountTop = document.getElementById('bulkCount');
    const bulkClearBtn = document.getElementById('bulkClearBtn');
    const bulkPrintBtn = document.getElementById('bulkPrintBtn');
    const selectAll    = document.getElementById('selectAllDrivers');

    function getCheckedDrivers() {
      return Array.from(document.querySelectorAll('#packages .driver-check:checked')).map(cb => {
        const row = cb.closest('.driver-item');
        return { id: cb.value, name: row?.dataset.driverName || ('Driver #' + cb.value) };
      });
    }

    function refreshBulkBar() {
      const drivers = getCheckedDrivers();
      bulkCountTop.textContent = drivers.length;
      bulkBar.classList.toggle('is-active', drivers.length > 0);
      // Sync "select all" indeterminate / checked state
      if (selectAll) {
        const allBoxes = document.querySelectorAll('#packages .driver-check');
        if (drivers.length === 0) {
          selectAll.checked = false; selectAll.indeterminate = false;
        } else if (drivers.length === allBoxes.length) {
          selectAll.checked = true; selectAll.indeterminate = false;
        } else {
          selectAll.checked = false; selectAll.indeterminate = true;
        }
      }
      // Toggle row highlight
      document.querySelectorAll('#packages .driver-item').forEach(row => {
        const cb = row.querySelector('.driver-check');
        row.classList.toggle('is-selected', cb && cb.checked);
      });
    }

    document.querySelectorAll('#packages .driver-check').forEach(cb => {
      cb.addEventListener('change', refreshBulkBar);
    });

    if (selectAll) {
      selectAll.addEventListener('change', function() {
        // Only consider currently visible rows (so search filter respects)
        document.querySelectorAll('#packages .driver-item').forEach(row => {
          if (row.style.display === 'none') return;
          const cb = row.querySelector('.driver-check');
          if (cb) cb.checked = this.checked;
        });
        refreshBulkBar();
      });
    }

    bulkClearBtn?.addEventListener('click', () => {
      document.querySelectorAll('#packages .driver-check').forEach(cb => cb.checked = false);
      refreshBulkBar();
    });

    bulkPrintBtn?.addEventListener('click', () => {
      const drivers = getCheckedDrivers();
      if (!drivers.length) return;
      openBulkMode(drivers);
    });

    // ===== Form submit (single or bulk) =====
    document.getElementById('printForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const format = document.querySelector('input[name="printFormat"]:checked').value;
      const reportdate = document.querySelector('input[name="date"]')?.value
        || '{{ request("date", \Carbon\Carbon::today()->format("Y-m-d")) }}';

      const ids = selectedDriverIdsInput.value;
      let url;
      if (ids) {
        // Bulk
        const qs = ids.split(',').filter(Boolean).map(id => `driver_ids[]=${encodeURIComponent(id)}`).join('&');
        url = `{{ route('tms.runsheet.bulk') }}?${qs}&format=${format}&date=${encodeURIComponent(reportdate)}`;
      } else {
        // Single (preserve existing behaviour)
        url = `/admin/tms/driver/${selectedDriverIdInput.value}/export?format=${format}&date=${encodeURIComponent(reportdate)}`;
      }
      // PDF/HTML opens in a new tab so the existing page stays put; Excel downloads in-place.
      if (format === 'pdf') {
        window.open(url, '_blank');
      } else {
        window.location.href = url;
      }
      printModal.hide();
    });
  });
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const parcelsModal = new bootstrap.Modal(document.getElementById('parcelsModal'));
  const tableWrapper = document.getElementById('parcelTableWrapper');
  const modalTitle = document.getElementById('parcelsModalLabel');

  document.querySelectorAll('.status-item').forEach(item => {
    item.addEventListener('click', () => {
      const status = item.dataset.status;
      const parcels = JSON.parse(item.dataset.parcels);

      modalTitle.innerHTML = `<i class="fa-solid fa-truck me-2"></i> ${status} Parcels (${parcels.length})`;

      // Build the table dynamically
      let html = `
        <table class="table table-bordered table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>#</th>
              <th>Invoice No</th>
              <th>Tracking ID</th>
              <th>Merchant</th>
              <th>Status</th>
              <th>Cash Collection</th>
              <th>Liquid Fragile</th>
              <th>Packaging</th>
              <th>Delivery Charge</th>
              <th>COD Charge</th>
              <th>COD Amount</th>
              <th>VAT (%)</th>
              <th>VAT Amount</th>
              <th>Total Delivery</th>
              <th>Current Payable</th>
            </tr>
          </thead>
          <tbody>`;

      if (parcels.length > 0) {
        parcels.forEach((p, index) => {
          html += `
            <tr>
              <td>${index + 1}</td>
              <td>${p.invoice_no ?? '-'}</td>
              <td>${p.tracking_id ?? '-'}</td>
              <td>${p.merchant?.business_name ?? p.merchant_id}</td>
              <td>${p.status_name ?? p.status}</td>
              <td>${parseFloat(p.cash_collection || 0).toFixed(2)}</td>
              <td>${parseFloat(p.liquid_fragile_amount || 0).toFixed(2)}</td>
              <td>${parseFloat(p.packaging_amount || 0).toFixed(2)}</td>
              <td>${parseFloat(p.delivery_charge || 0).toFixed(2)}</td>
              <td>${parseFloat(p.cod_charge || 0).toFixed(2)}</td>
              <td>${parseFloat(p.cod_amount || 0).toFixed(2)}</td>
              <td>${p.vat ?? '-'}</td>
              <td>${parseFloat(p.vat_amount || 0).toFixed(2)}</td>
              <td>${parseFloat(p.total_delivery_amount || 0).toFixed(2)}</td>
              <td>${parseFloat(p.current_payable || 0).toFixed(2)}</td>
            </tr>`;
        });
      } else {
        html += `<tr><td colspan="15" class="text-center text-muted">No records found</td></tr>`;
      }

      html += '</tbody></table>';
      tableWrapper.innerHTML = html;

      parcelsModal.show();
    });
  });
});
</script>



<!-- Scripts -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapSettingKey() }}&callback=initMap" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
