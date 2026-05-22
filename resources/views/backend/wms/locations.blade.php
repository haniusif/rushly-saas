@extends('backend.partials.master')
@section('title', __('Storage Locations'))

@section('maincontent')
<div class="container-fluid dashboard-content">

    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h2 class="pageheader-title">{{ __('Warehouse Management System') }}</h2>
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('wms.dashboard') }}" class="breadcrumb-link">{{ __('WMS') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('Storage Locations') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="card">
        <div class="card-body">
            <p>{{ __('Warehouse bin and rack location structure.') }}</p>
        </div>
    </div>

</div>
@endsection
