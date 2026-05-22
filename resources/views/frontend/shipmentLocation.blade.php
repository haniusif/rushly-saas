<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ __('Shipment confirmation') }}</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Cairo Font -->
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
 

  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Cairo', sans-serif;
      direction: {{ app()->isLocale('ar') ? 'rtl' : 'ltr' }};
      text-align: {{ app()->isLocale('ar') ? 'right' : 'left' }};
       padding-bottom: 90px;
    }
    .card-header {
      background-color: #a21f5c !important;
    }
    .btn-success {
      background-color: #a21f5c !important;
      border-color: #a21f5c !important;
    }
    .btn-success:hover {
      background-color: #891b4f !important;
      border-color: #891b4f !important;
    }
    #map {
      height: 400px;
      width: 100%;
      border-radius: .5rem;
    }
    .lang-switch {
      position: absolute;
      top: 15px;
      {{ app()->isLocale('ar') ? 'left' : 'right' }}: 15px;
    }
    .logo-container {
      text-align: center;
      margin-top: 30px;
      margin-bottom: 20px;
    }
    .logo-container img {
      height: 100px;
    }
    
      .fixed-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 1050;
    border-top: 1px solid #dee2e6;
  }
 
  </style>
</head>
<body>

  <!-- 🌍 زر تغيير اللغة -->
<div class="lang-switch">
  @if(app()->isLocale('ar'))
    <a href="{{ route('setLocale', 'en') }}" class="btn btn-outline-primary btn-sm">
      🇬🇧 English
    </a>
  @else
    <a href="{{ route('setLocale', 'ar') }}" class="btn btn-outline-primary btn-sm">
      🇦🇪 العربية
    </a>
  @endif
</div>


  <!-- 🔹 الشعار -->
  <div class="logo-container">
    <img src="{{ settings()->logo_image }}" alt="Logo">
  </div>

  <div class="container mt-4 mb-5">
    <div class="card shadow-sm">
      <div class="card-header text-white">
        <h5 class="mb-0">{{ __('Shipment Details') }} – {{ $parcel->tracking_id }}</h5>
      </div>

      <div class="card-body">
          
          @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <strong>✅ {{ __('Success!') }}</strong> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <strong>⚠️ {{ __('Error!') }}</strong> {{ __('Please check the form and try again.') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif


        <form action="{{ route('shipment.updateLocation', $parcel->id) }}" method="POST">
          @csrf
          @method('PUT')

          <!-- Shipment info -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">{{ __('Shipment Number') }}</label>
              <input type="text" class="form-control" disabled value="{{ $parcel->tracking_id }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">{{ __('Sender Name') }}</label>
              <input type="text" class="form-control" disabled value="{{ $parcel->merchant->business_name ?? 'Rushly' }}">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">{{ __('Customer Phone') }}</label>
              <input type="text" class="form-control" disabled value="{{ $parcel->customer_phone }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-bold">{{ __('Cash Collection (AED)') }}</label>
              <input type="text" class="form-control" disabled value="{{ $parcel->cash_collection }}">
            </div>
          </div>

          <hr class="my-4">
          <h6 class="text-primary mb-3 fw-bold">{{ __('Update Delivery Information') }}</h6>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">{{ __('Select City') }}</label>
              <input type="text" class="form-control" disabled value="{{ optional($parcel->city)->en_name ?? 'Dubai' }}">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">{{ __('Select Area') }}</label>
              <select name="area_id" class="form-select" @if($parcel->reschedule_area_id >= 1) disabled @endif >
                <option value="">{{ __('Select Area') }}</option>
                @foreach ($areas as $area)
                  <option value="{{ $area->id }}" @selected($parcel->area_id == $area->id)>
                    {{ app()->isLocale('ar') ? $area->name : $area->en_name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

        

          <div class="mb-3">
            <label class="form-label">{{ __('Additional Phone Number') }}</label>
            <input type="text" name="additional_phone" value="{{ $parcel->additional_phone }}" @if($parcel->additional_phone) disabled @endif  class="form-control" placeholder="{{ __('e.g. 05XXXXXXXX') }}">
          </div>

       <div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label">{{ __('Schedule Delivery Date') }}</label>
    <input type="date" name="delivery_date"  value="{{ $parcel->reschedule_delivery_date ?? $parcel->delivery_date }}"  @if($parcel->reschedule_delivery_date) disabled @endif   class="form-control" value="{{ date('Y-m-d') }}">
    <small class="form-text text-muted">
      {{ __('Available days: Monday to Saturday (closed on Sunday)') }}
    </small>
  </div>

  <div class="col-md-6 mb-3">
    <label class="form-label">{{ __('Schedule Delivery Time') }}</label>
    <input type="time" name="delivery_time" value="{{ $parcel->reschedule_delivery_time }}"  @if($parcel->reschedule_delivery_time) disabled @endif   class="form-control">
    <small class="form-text text-muted">
      {{ __('Available hours: 10:00 AM to 7:00 PM') }}
    </small>
  </div>
</div>


          <div class="mb-4">
            <label class="form-label fw-bold">{{ __('Select New Location on Map') }}</label>
            <input type="hidden" id="customer_lat" name="customer_lat" value="{{ $parcel->customer_lat }}">
            <input type="hidden" id="customer_long" name="customer_long" value="{{ $parcel->customer_long }}">
            <div id="map"></div>
          </div>
          
            <div class="mb-3">
            <label class="form-label">{{ __('New Address') }}</label>
            <textarea id="customer_address" name="customer_address" @if($parcel->reschedule_area_id >= 1) disabled @endif class="form-control" rows="2">{{ $parcel->customer_address }}</textarea>
          </div>
 @if(!$parcel->reschedule_area_id)
       <!-- ✅ زر الحفظ والإلغاء مثبت أسفل الصفحة -->
<div class="fixed-footer text-center py-3 bg-white shadow-lg">
  <button type="submit" class="btn btn-success px-4 me-2">
    {{ __('Save Changes') }}
  </button>
  <a href="#" onclick="history.back()" class="btn btn-secondary px-4">
    {{ __('Cancel') }}
  </a>
</div>

@endif

        </form>
      </div>
    </div>
  </div>

  <!-- Google Maps -->
  <script src="https://maps.googleapis.com/maps/api/js?key={{ googleMapSettingKey() }}&callback=initMap&libraries=places" async defer></script>

  <script>
    function initMap() {
      const latInput = document.getElementById("customer_lat");
      const lngInput = document.getElementById("customer_long");
      const addressInput = document.getElementById("customer_address");

      const lat = parseFloat(latInput.value) || 24.7136;
      const lng = parseFloat(lngInput.value) || 46.6753;

      const map = new google.maps.Map(document.getElementById("map"), {
        center: { lat, lng },
        zoom: 13,
      });
     @if(!$parcel->reschedule_area_id)
      const marker = new google.maps.Marker({
        position: { lat, lng },
        map,
        draggable: true,
      });

      // تحديث الإحداثيات والعنوان عند سحب العلامة
      marker.addListener("dragend", function (e) {
        const latVal = e.latLng.lat().toFixed(6);
        const lngVal = e.latLng.lng().toFixed(6);
        latInput.value = latVal;
        lngInput.value = lngVal;

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat: parseFloat(latVal), lng: parseFloat(lngVal) } }, (results, status) => {
          if (status === "OK" && results[0]) {
            addressInput.value = results[0].formatted_address;
          }
        });
      });
      
      @endif

      // الإكمال التلقائي للعنوان
      const autocomplete = new google.maps.places.Autocomplete(addressInput);
      autocomplete.bindTo("bounds", map);

      autocomplete.addListener("place_changed", function () {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        map.setCenter(place.geometry.location);
        marker.setPosition(place.geometry.location);
        latInput.value = place.geometry.location.lat().toFixed(6);
        lngInput.value = place.geometry.location.lng().toFixed(6);
      });
    }
  </script>
  <script>
  function validateDeliveryDateTime() {
    const dateInput = document.querySelector('[name="delivery_date"]');
    const timeInput = document.querySelector('[name="delivery_time"]');

    const selectedDate = new Date(dateInput.value);
    const day = selectedDate.getDay(); // 0=Sunday, 6=Saturday
    const timeValue = timeInput.value;

    // ✅ تحقق من اليوم (الإثنين إلى السبت)
    if (day === 0) { // Sunday
      alert("🚫 {{ __('Delivery is not available on Sundays. Please choose a day between Monday and Saturday.') }}");
      dateInput.value = '';
      return false;
    }

    // ✅ تحقق من الوقت (10:00 إلى 19:00)
    if (timeValue) {
      const [hours, minutes] = timeValue.split(':').map(Number);
      if (hours < 10 || (hours >= 19 && minutes > 0)) {
        alert("🕓 {{ __('Delivery time must be between 10:00 AM and 7:00 PM.') }}");
        timeInput.value = '';
        return false;
      }
    }

    return true;
  }

  // ✅ اربط التحقق بالنموذج
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    form.addEventListener("submit", (e) => {
      if (!validateDeliveryDateTime()) {
        e.preventDefault(); // إيقاف الإرسال عند وجود خطأ
      }
    });
  });
</script>

</body>
</html>
