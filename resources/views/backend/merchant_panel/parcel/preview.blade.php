@extends('backend.partials.master')

@section('title')
    {{ __('parcel.import_with_preview') }}
@endsection

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('merchant-panel.parcel.index') }}" class="breadcrumb-link">{{ __('parcel.title') }}</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                {{ __('parcel.import_with_preview') }}
                            </li>
                        </ol>
                    </nav>
                </div>
                <h3 class="mb-0">{{ __('parcel.import_with_preview') }}</h3>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">

            @if (session('importErrors'))
                <div class="alert alert-danger">
                    <strong>{{ __('parcel.validation_errors') }}</strong>
                    <ul class="mb-0">
                        @foreach (session('importErrors') as $row => $errors)
                            @foreach ($errors as $err)
                                <li>{{ __('parcel.row_number') }} {{ $row }}: {{ $err }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">{{ __('parcel.preview_title') }}</h5>
                            <div class="text-muted small">
                                {{ __('parcel.total_rows') }}: {{ number_format($totalRows ?? 0) }}
                                @if(isset($previewRows) && method_exists($previewRows, 'count') && isset($totalRows) && $totalRows > $previewRows->count())
                                    — {{ __('parcel.showing_first') }} {{ $previewRows->count() }} {{ __('parcel.rows_only') }}
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> {{ __('levels.back') }}
                            </a>
                            <form action="{{ route('merchant-panel.parcel.import.confirm') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-check"></i> {{ __('parcel.confirm_import') }}
                                </button>
                            </form>
                        </div>
                    </div>

                 @if (!empty($expected))
    <div class="alert alert-info mt-3 mb-0">
        <div class="mb-1">{{ __('parcel.expected_columns') }}</div>
        <code class="d-block" style="white-space:normal">{{ implode(', ', $expected->toArray()) }}</code>
    </div>
@endif

                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px">#</th>
                                    @if(!empty($headers) && method_exists($headers, 'count'))
                                        @foreach ($headers as $h)
                                            <th>{{ $h }}</th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNo = 1; @endphp
                                @if(isset($previewRows) && $previewRows && $previewRows->count())
                                    @foreach ($previewRows as $row)
                                        <tr>
                                            <td>{{ $rowNo++ }}</td>
                                            @foreach ($row as $cell)
                                                <td>
                                                    {{ is_scalar($cell) || is_null($cell) ? $cell : json_encode($cell, JSON_UNESCAPED_UNICODE) }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="{{ (isset($headers) ? $headers->count() : 0) + 1 }}" class="text-center text-muted">
                                            {{ __('parcel.no_data_to_preview') }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">
                            {{ __('levels.cancel') }}
                        </a>
                        <form action="{{ route('merchant-panel.parcel.import.confirm') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                {{ __('parcel.confirm_import') }}
                            </button>
                        </form>
                    </div>

                    @if(isset($totalRows, $previewRows) && $totalRows > $previewRows->count())
                        <p class="text-muted small mt-2 mb-0">
                            * {{ __('parcel.full_import_note', ['total' => number_format($totalRows)]) }}
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
