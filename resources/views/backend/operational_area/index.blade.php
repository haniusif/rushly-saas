@extends('backend.partials.master')
@section('title')
    المناطق التشغيلية {{ __('levels.list') }}
@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">المناطق التشغيلية</a></li>
                            <li class="breadcrumb-item active">{{ __('levels.list') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">المناطق التشغيلية</p>
                    </div>
                    @if(hasPermission('operational_area_create'))
                    <div class="col-6">
                        <a href="{{ route('operational_areas.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" title="{{ __('levels.add') }}">
                            <i class="fa fa-plus"></i>
                        </a>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>الاسم</th>
                                    <th>الرمز</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(hasPermission('operational_area_update') || hasPermission('operational_area_delete'))
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($areas->currentPage() - 1) * $areas->perPage() + 1; @endphp
                                @forelse($areas as $row)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->code }}</td>
                                    <td>{!! $row->my_status !!}</td>
                                    @if(hasPermission('operational_area_update') || hasPermission('operational_area_delete'))
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('operational_area_update'))
                                                    <a href="{{ route('operational_areas.edit', $row->id) }}" class="dropdown-item"><i class="fas fa-edit"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('operational_area_delete'))
                                                    <form action="{{ route('operational_area.delete', $row->id) }}" method="POST" data-title="حذف المنطقة التشغيلية">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" id="deleteTitle" value="المنطقة التشغيلية">
                                                        <button type="submit" class="dropdown-item"><i class="fa fa-trash"></i> {{ __('levels.delete') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">لا توجد بيانات</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($areas->total() > 0)
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $areas->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $areas->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $areas->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $areas->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
