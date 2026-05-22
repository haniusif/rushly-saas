@extends('backend.partials.master')

@section('title')
    {{ __('TMS') }}
@endsection

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h2 class="pageheader-title">{{ __('TMS') }}</h2>
                
            </div>
        </div>
    </div>

    <!-- Map Card -->
    <div class="row">
        <div class="col-3">
            
            <div class="card h-100">
    <div class="card-body">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="driverTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="with-tab" data-toggle="tab" href="#with" role="tab" aria-controls="with" aria-selected="true">
                    Shipments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="without-tab" data-toggle="tab" href="#without" role="tab" aria-controls="without" aria-selected="false">
                    Free
                </a>
            </li>
        </ul>

        <!-- Tab Panes -->
        <div class="tab-content" id="driverTabsContent">
            <!-- With Shipments -->
            <div class="tab-pane fade show active" id="with" role="tabpanel" aria-labelledby="with-tab">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Shipments</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                        @forelse ($with_shipments as $index => $dmsh)
                          
                              
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $dmsh['name'] }}</td>
                                    <td>{{ $dmsh['shipment_count'] }}</td>
                                </tr>
                        @empty
                          <tr>
                                <td colspan="3" class="text-center">No drivers with shipments</td>
                            </tr>
                        @endforelse
                       
                    </tbody>
                </table>
            </div>

            <!-- Without Shipments -->
            <div class="tab-pane fade" id="without" role="tabpanel" aria-labelledby="without-tab">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>

                        </tr>
                    </thead>
                    <tbody>
                        
                           @forelse ($without_shipments as $index => $dmsh)
                          
                              
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $dmsh['name'] }}</td>

                                </tr>
                        @empty
                          <tr>
                                <td colspan="3" class="text-center">All drivers have shipments</td>
                            </tr>
                        @endforelse
                        
                   
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

            
        </div>
        <div class="col-8">
            <div class="card h-100">
                <div class="card-body">

                    <div id="googleMap" style="width: 100%; height: 600px;"></div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapSettingKey() }}"></script>
<script>
    const deliverymen = @json($locations);

    function initMap() {
        const defaultCenter = { lat: 25.2739584, lng: 55.4237952 };

        const map = new google.maps.Map(document.getElementById("googleMap"), {
            zoom: 10,
            center: defaultCenter,

            // ✅ Full Map Options
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            disableDefaultUI: false, // show all controls
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                position: google.maps.ControlPosition.TOP_LEFT
            },
            scaleControl: true,
            streetViewControl: true,
            streetViewControlOptions: {
                position: google.maps.ControlPosition.LEFT_TOP
            },
            fullscreenControl: true,
            fullscreenControlOptions: {
                position: google.maps.ControlPosition.RIGHT_TOP
            },
            rotateControl: true,
            draggable: true,
            scrollwheel: true,
            keyboardShortcuts: true,
        });

        deliverymen.forEach(dm => {
            if (dm.lat && dm.lng) {
                const marker = new google.maps.Marker({
                    position: { lat: dm.lat, lng: dm.lng },
                    map: map,
                    title: dm.name,
                     icon: {
                         url: dm.status == 1
                    ? "{{ static_asset('images/icons/car_map_on.svg') }}"
                    : "{{ static_asset('images/icons/car_map_off.svg') }}",
                    

        scaledSize: new google.maps.Size(32, 32) // Resize the SVG if needed
    }
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `<strong>${dm.name}</strong><br>Mobile: ${dm.mobile}`
                });

                marker.addListener("click", () => {
                    infoWindow.open(map, marker);
                });
            }
        });
    }

    window.onload = initMap;
</script>
@endpush
