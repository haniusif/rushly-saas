@extends('backend.partials.master')

@section('title')
    {{ __('WMS Dashboard') }}
@endsection

@section('maincontent')
<!-- wrapper -->
<div class="container-fluid dashboard-content">

    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h2 class="pageheader-title">{{ __('Warehouse Management System') }}</h2>
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard.index') }}" class="breadcrumb-link">
                                    {{ __('Dashboard') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('wms.dashboard') }}" class="breadcrumb-link">
                                    {{ __('WMS') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ __('Overview') }}
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <!-- Dashboard Content Placeholder -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Summary') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('Welcome to the WMS Dashboard. You can monitor products, inventory, movements, and generate reports.') }}</p>
                    {{-- You can insert dashboard widgets or charts here --}}
                </div>
            </div>
        </div>
        <!-- End Content -->
    </div>

</div>
<!-- end wrapper -->
@endsection
