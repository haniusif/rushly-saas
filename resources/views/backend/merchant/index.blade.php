@extends('backend.partials.master')
@section('title')
    {{ __('merchant.title') }}  {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('merchantmanage.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('merchant.index') }}" class="breadcrumb-link">{{ __('merchant.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-10">
                        <div class="d-flex parcelsearchFlex">
                            <p class="h3">{{ __('merchant.title') }}</p>
                            <input id="Psearch" class="form-control parcelSearch d-lg-block" type="text" placeholder="Search..">
                        </div>
                    </div>
                    <div class="col-2 d-flex justify-content-end align-items-center" style="gap:6px;">
                        <div class="rl-mv-toggle btn-group btn-group-sm" role="group" aria-label="View mode">
                            <button type="button" class="btn btn-outline-secondary active" data-view="card" title="{{ __('merchant.card_view') }}"><i class="ti ti-layout-grid"></i></button>
                            <button type="button" class="btn btn-outline-secondary"        data-view="list" title="{{ __('merchant.list_view') }}"><i class="ti ti-list"></i></button>
                        </div>
                        @if( hasPermission('merchant_create') == true )
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="navigator.clipboard.writeText('{{ route('merchant.apply') }}');this.innerHTML='<i class=&quot;ti ti-check&quot;></i> {{ __('levels.copied') ?? 'Copied' }}';" data-toggle="tooltip" data-placement="top" title="{{ __('merchant.copy_apply_link') }}: {{ route('merchant.apply') }}"><i class="ti ti-link"></i></button>
                            <a href="{{route('merchant.create')}}" class="btn btn-primary btn-sm" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                        @endif
                    </div>
                    <div class="col-12 d-lg-none mt-2">
                        <input id="Psearch" class="form-control " type="text" placeholder="Search..">
                    </div>
                </div>
                <div class="card-body">
                    {{-- Card view (default) --}}
                    <div id="merchantCardView" class="rl-mv-grid">
                        @foreach($merchants as $merchant)
                            <div class="rl-mv-card" data-search="{{ strtolower(($merchant->user->name ?? '').' '.($merchant->business_name ?? '').' '.($merchant->user->mobile ?? '').' '.($merchant->user->email ?? '').' '.($merchant->user->unique_id ?? '')) }}">
                                <div class="rl-mv-card__head">
                                    <img class="rl-mv-avatar" src="{{ $merchant->user->image }}" alt="{{ $merchant->user->name }}">
                                    <div class="rl-mv-meta">
                                        <div class="rl-mv-name">{{ $merchant->user->name }}</div>
                                        <div class="rl-mv-sub">#{{ $merchant->user->unique_id }} · {{ $merchant->business_name }}</div>
                                    </div>
                                    @if(
                                        hasPermission('merchant_view') == true ||
                                        hasPermission('merchant_update') == true ||
                                        hasPermission('merchant_delete') == true
                                    )
                                    <div class="rl-mv-actions dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="{{ route('merchant.invoice.generate',$merchant->id) }}" class="dropdown-item"><i class="ti ti-file-invoice"></i> Invoice</a>
                                            @if(hasPermission('merchant_view'))
                                                <a href="{{ route('merchant.view',$merchant->id) }}" class="dropdown-item"><i class="ti ti-eye"></i> {{ __('levels.view') }}</a>
                                            @endif
                                            @if(hasPermission('merchant_update'))
                                                <a href="{{ route('merchant.edit',$merchant->id) }}" class="dropdown-item"><i class="ti ti-edit"></i> {{ __('levels.edit') }}</a>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="rl-mv-card__body">
                                    <div class="rl-mv-row"><i class="ti ti-phone"></i><span>{{ $merchant->user->mobile ?: '—' }}</span></div>
                                    <div class="rl-mv-row"><i class="ti ti-mail"></i><span>{{ $merchant->user->email ?: '—' }}</span></div>
                                    <div class="rl-mv-row"><i class="ti ti-building-warehouse"></i><span>{{ $merchant->user->hub->name ?? '—' }}</span></div>
                                    <div class="rl-mv-row">
                                        <i class="ti ti-globe"></i>
                                        <span>
                                            @if($merchant->countries->isEmpty())
                                                —
                                            @else
                                                {{ $merchant->countries->take(3)->pluck('code')->filter()->join(', ') ?: $merchant->countries->take(3)->pluck('name')->join(', ') }}@if($merchant->countries->count() > 3) +{{ $merchant->countries->count() - 3 }}@endif
                                                ·
                                                @if($merchant->covers_all_cities)
                                                    {{ __('merchant.covers_all_cities') }}
                                                @else
                                                    {{ $merchant->cities->count() }} {{ __('merchant.cities_covered') }}
                                                @endif
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="rl-mv-card__foot">
                                    <div class="rl-mv-status">
                                        {!! $merchant->user->my_status !!}
                                        {!! $merchant->WalletStatus !!}
                                    </div>
                                    <div class="rl-mv-balance" title="{{ __('levels.current_balance') }}">
                                        <span class="rl-mv-balance__lbl">{{ __('levels.current_balance') }}</span>
                                        <span class="rl-mv-balance__val">{{ settings()->currency }}{{ number_format($merchant->computed_balance ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if(count($merchants) === 0)
                            <div class="rl-mv-empty"><i class="ti ti-mood-empty"></i> {{ __('No results') }}</div>
                        @endif
                    </div>

                    {{-- List view --}}
                    <div id="merchantListView" class="table-responsive" style="display:none;">
                        <table id="table" class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.unique_id') }}</th>  
                                    <th>{{ __('levels.details') }}</th>
                                    <th>{{ __('levels.hub') }}</th>
                                    <th>{{ __('levels.business_name') }}</th>
                                    <th>{{ __('merchant.geography') }}</th>
                                    <th>{{ __('levels.phone') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('levels.wallet_activation') }}</th>
                                    <th>{{ __('levels.current_balance') }}</th>
                                    <th>computed_balance</th>
                                    @if(
                                        hasPermission('merchant_view') == true ||
                                        hasPermission('merchant_update') == true ||
                                        hasPermission('merchant_delete') == true
                                     )
                                        <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($merchants as $merchant)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{@$merchant->user->unique_id}}</td>
                                    <td>
                                        <div class="row">
                                            <div class="pr-3">
                                                <img src="{{$merchant->user->image}}" alt="user" class="rounded" width="40" height="40">
                                            </div>
                                            <div>
                                                <strong>{{$merchant->user->name}}</strong>
                                                <p>{{$merchant->user->email}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{@$merchant->user->hub->name}}</td>
                                    <td>{{@$merchant->business_name}}</td>
                                    {{-- <td>{{@$merchant->merchant_unique_id}}</td> --}}
                                    {{-- Coverage: country codes + cities-all / N-cities. Compact for the index. --}}
                                    <td>
                                        @php
                                            $rlCountries = $merchant->countries;
                                            $rlCityCount = $merchant->cities->count();
                                        @endphp
                                        @if($rlCountries->isEmpty())
                                            <span class="text-muted">—</span>
                                        @else
                                            @foreach($rlCountries->take(3) as $rlC)
                                                <span class="badge badge-light" title="{{ $rlC->name }}">{{ $rlC->code ?: $rlC->en_name ?: $rlC->name }}</span>
                                            @endforeach
                                            @if($rlCountries->count() > 3)
                                                <span class="badge badge-light" title="{{ $rlCountries->skip(3)->pluck('name')->join(', ') }}">+{{ $rlCountries->count() - 3 }}</span>
                                            @endif
                                            <br>
                                            @if($merchant->covers_all_cities)
                                                <span class="badge badge-success" style="font-size:10px;">{{ __('merchant.covers_all_cities') }}</span>
                                            @else
                                                <span class="badge badge-info" style="font-size:10px;">{{ $rlCityCount }} {{ __('merchant.cities_covered') }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{@$merchant->user->mobile}}</td>
                                    <td>
                                        {!! $merchant->user->my_status !!}
                                    </td>
                                    <td>
                                        {!! $merchant->WalletStatus !!} 
                                    </td>
                                    <td>{{settings()->currency}}{{$merchant->current_balance}} </td>
                                    <th>{{ number_format($merchant->computed_balance, 2) }}</th>
                                    @if(
                                        hasPermission('merchant_view') == true ||
                                        hasPermission('merchant_update') == true ||
                                        hasPermission('merchant_delete') == true
                                        )
                                        <td>
                                            <div class="row">
                                                <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    <a href="{{route('merchant.invoice.generate',$merchant->id)}}" class="dropdown-item"><i class="fa fa-file" aria-hidden="true"></i> Invoice Generate</a>
                                                    @if( hasPermission('merchant_view') == true  )
                                                        <a href="{{route('merchant.view',$merchant->id)}}" class="dropdown-item"><i class="fa fa-eye" aria-hidden="true"></i> {{ __('levels.view') }}</a>
                                                    @endif
                                                 
                                                    @if( hasPermission('merchant_update') == true   )
                                                        <a href="{{route('merchant.edit',$merchant->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                    @endif
                                                    @if( hasPermission('merchant_delete') == true )
                                                        <!--<form id="delete" value="Test" action="{{route('merchant.delete',$merchant->id)}}" method="POST" data-title="{{ __('delete.merchant') }}">-->
                                                        <!--    @method('DELETE')-->
                                                        <!--    @csrf-->
                                                        <!--    <input type="hidden" name="" value="Merchant" id="deleteTitle">-->
                                                        <!--    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>-->
                                                        <!--</form>-->
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{-- /#merchantListView --}}
                    <div class="px-3 d-flex flex-row-reverse align-items-center">
                        <span>{{ $merchants->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $merchants->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $merchants->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $merchants->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
<!-- js  -->
@push('styles')
<style>
.rl-mv-toggle .btn { padding: 4px 10px; }
.rl-mv-toggle .btn.active { background:#a8262c; border-color:#a8262c; color:#fff; }
.rl-mv-toggle .btn i { font-size: 16px; }

.rl-mv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
}
.rl-mv-card {
    background:#fff; border:1px solid #e9ecef; border-radius:12px;
    box-shadow: 0 1px 2px rgba(0,0,0,.04);
    display:flex; flex-direction:column; overflow:hidden;
    transition: box-shadow .15s ease, transform .15s ease;
}
.rl-mv-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); transform: translateY(-1px); }

.rl-mv-card__head { display:flex; align-items:center; gap:12px; padding:14px 16px; border-bottom:1px solid #f1f3f5; }
.rl-mv-avatar { width:48px; height:48px; border-radius:50%; object-fit:cover; border:2px solid #f1f3f5; flex:0 0 48px; }
.rl-mv-meta { flex:1; min-width:0; }
.rl-mv-name { font-weight:700; color:#111827; font-size:14px; line-height:1.3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rl-mv-sub  { color:#6b7280; font-size:12px; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rl-mv-actions .btn { padding: 4px 8px; }
.rl-mv-actions .btn i { font-size: 18px; color:#6b7280; }

.rl-mv-card__body { padding:12px 16px; flex:1; }
.rl-mv-row { display:flex; align-items:center; gap:8px; padding:4px 0; color:#374151; font-size:13px; }
.rl-mv-row i { color:#a8262c; font-size:16px; flex:0 0 16px; }
.rl-mv-row span { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.rl-mv-card__foot {
    display:flex; align-items:center; justify-content:space-between; gap:8px;
    padding:10px 16px; background:#fafbfc; border-top:1px solid #f1f3f5;
}
.rl-mv-status { display:flex; gap:4px; flex-wrap:wrap; }
.rl-mv-balance { text-align:end; }
.rl-mv-balance__lbl { display:block; font-size:11px; color:#6b7280; }
.rl-mv-balance__val { font-weight:700; color:#111827; font-size:14px; }

.rl-mv-empty { grid-column: 1 / -1; text-align:center; color:#9ca3af; padding:40px 20px; font-size:14px; }
.rl-mv-empty i { font-size:32px; display:block; margin-bottom:8px; }
</style>
@endpush
@push('scripts')
<script src="{{ static_asset('backend/js/parcel/parcel-search.js') }}"></script>
<script>
(function() {
    const KEY = 'rl_merchant_view';
    const cardView = document.getElementById('merchantCardView');
    const listView = document.getElementById('merchantListView');
    const buttons  = document.querySelectorAll('.rl-mv-toggle [data-view]');
    if (!cardView || !listView) return;

    function apply(view) {
        const isCard = view !== 'list';
        cardView.style.display = isCard ? '' : 'none';
        listView.style.display = isCard ? 'none' : '';
        buttons.forEach(b => b.classList.toggle('active', b.dataset.view === (isCard ? 'card' : 'list')));
    }

    apply(localStorage.getItem(KEY) || 'card');
    buttons.forEach(b => b.addEventListener('click', () => {
        const v = b.dataset.view;
        localStorage.setItem(KEY, v);
        apply(v);
    }));

    // Live filter cards using the existing search input
    const searches = document.querySelectorAll('#Psearch');
    searches.forEach(input => input.addEventListener('input', function() {
        const q = (this.value || '').toLowerCase().trim();
        document.querySelectorAll('#merchantCardView .rl-mv-card').forEach(c => {
            c.style.display = !q || (c.dataset.search || '').indexOf(q) !== -1 ? '' : 'none';
        });
    }));
})();
</script>
@endpush


