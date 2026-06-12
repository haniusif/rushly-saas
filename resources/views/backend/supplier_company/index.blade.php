@extends('backend.partials.master')
@section('title')
    الشركات المزودة {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">الشركات المزودة</a></li>
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
                        <p class="h3">الشركات المزودة</p>
                    </div>
                    @if(hasPermission('supplier_company_create'))
                    <div class="col-6">
                        <a href="{{ route('supplier_companies.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" title="{{ __('levels.add') }}">
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
                                    <th>هاتف التواصل</th>
                                    <th>{{ __('levels.status') }}</th>
                                    @if(hasPermission('supplier_company_update') || hasPermission('supplier_company_delete'))
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = ($suppliers->currentPage() - 1) * $suppliers->perPage() + 1; @endphp
                                @forelse($suppliers as $row)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->contact_phone }}</td>
                                    <td>{!! $row->my_status !!}</td>
                                    @if(hasPermission('supplier_company_update') || hasPermission('supplier_company_delete'))
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('supplier_company_update'))
                                                    <a href="{{ route('supplier_companies.edit', $row->id) }}" class="dropdown-item"><i class="fas fa-edit"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('supplier_company_delete'))
                                                    <form action="{{ route('supplier_company.delete', $row->id) }}" method="POST" data-title="حذف الشركة المزودة">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" id="deleteTitle" value="الشركة المزودة">
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
                @if($suppliers->total() > 0)
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $suppliers->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $suppliers->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $suppliers->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $suppliers->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
