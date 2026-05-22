<!-- left sidebar -->
<div class="col-12 nav-left-sidebar sidebar-dark">
    <ul class="navbar-nav">
        <li class="nav-divider">
            {{ __('Menu') }}
        </li>
        <li class="nav-item ">
            <a class="nav-link {{ request()->is('/*') ? 'active' : '' }}" href="{{ url('/dashboard') }}"><i
                    class="fa fa-home"></i>{{ __('Dashboard') }}</a>
        </li>

        <li class="nav-item ">
            <a class="nav-link {{ request()->is('merchant/support*') ? 'active' : '' }}"
                href="{{ route('merchant-panel.support.index') }}"><i
                    class="fa fa-comments"></i>{{ __('Support') }}</a>
        </li>
         @if(Auth::user()->merchant->wallet_use_activation)
        <li class="nav-item ">
            <a class="nav-link {{ (request()->is('merchant/my-wallet*')) ? 'active' : '' }}" href="{{route('merchant-panel.my.wallet.index')}}"><i class="fa fa-wallet"></i>{{ __('My wallet') }}</a>
        </li>
        @endif

        <li class="nav-item">
            <a class="nav-link {{ request()->is('merchant/payment-request*', 'merchant/invoice*', 'merchant/payment/received*', 'merchant/online-payment*', 'merchant/invoice*') ? 'active' : '' }}"
                href="#" data-toggle="collapse" aria-expanded="false" data-target="#accounts"
                aria-controls="accounts"><i class="fa fa-users"></i> {{ __('Accounting') }}</a>
            <div id="accounts"
                class="{{ request()->is('merchant/payment-request*', 'merchant/invoice*', 'merchant/payment/received*', 'merchant/online-payment*', 'merchant/invoice*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">

                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/payment/received*') ? 'active' : '' }}"
                            href="{{ route('online.payment.received') }}"> {{ __('Payments received') }}</a>
                    </li>
                    {{-- payout --}}
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/online-payment*') ? 'active' : '' }}"
                            href="{{ route('online.payment.index') }}"> {{ __('Payout') }}</a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/invoice*') ? 'active' : '' }}"
                            href="{{ route('merchant.panel.invoice.index') }}">{{ __('Invoice') }}</a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item ">
            <a class="nav-link {{ request()->is('merchant/parcel/*') ? 'active' : '' }}"
                href="{{ route('merchant-panel.parcel.index') }}"><i
                    class="fa fa-dolly"></i>{{ __('Shipments') }}</a>
        </li>
        <li class="nav-item ">
            <a class="nav-link {{ request()->is('merchant/parcel-bank*') ? 'active' : '' }}"
                href="{{ route('merchant-panel.parcel-bank.index') }}"><i
                    class="fa fa-map"></i>{{ __('Shipment bank') }}</a>
        </li>


        <li class="nav-item">
            <a class="nav-link {{ request()->is('merchant/reports/*') ? 'active' : '' }}" href="#"
                data-toggle="collapse" aria-expanded="false" data-target="#reports" aria-controls="reports"><i
                    class="fas fa-print"></i>{{ __('Reports') }}</a>
            <div id="reports"
                class="{{ request()->is('merchant/reports*', 'merchant/accounts/statements*', 'merchant/accounts/account-transaction*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/reports/parcel-reports*', 'merchant/reports/parcel-filter-reports') ? 'active' : '' }}"
                            href="{{ route('merchant-panel.parcel.reports') }}" aria-expanded="false"
                            data-target="#submenu-1" aria-controls="submenu-1">{{ __('Shipments report') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/reports/total-summery*', 'merchant/reports/total-summery-filter*') ? 'active' : '' }}"
                            href="{{ route('merchant.total.summery') }}">{{ __('Total summery report') }}</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/accounts/account-transaction*') ? 'active' : '' }}"
                            href="{{ route('merchant.accounts.account-transaction.index') }}">{{ __('Account transactions') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/accounts/statements*') ? 'active' : '' }}"
                            href="{{ route('merchant.accounts.statements.index') }}">{{ __('Statements') }}</a>
                    </li>
                </ul>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('merchant/settings*', 'merchant/shops*') ? 'active' : '' }}"
                href="#" data-toggle="collapse" aria-expanded="false" data-target="#settings"
                aria-controls="settings"><i class="fa fa-fw fa-cogs"></i> {{ __('Settings') }}</a>
            <div id="settings"
                class="{{ request()->is('merchant/settings*', 'merchant/shops*') ? '' : 'collapse' }} submenu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/settings/cod-charges*') ? 'active' : '' }}"
                            href="{{ route('merchant.cod-charges.index') }}">{{ __('COD charges') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('merchant/settings/delivery-charges*') ? 'active' : ' ' }}"
                            href="{{ route('merchant.delivery-charges.index') }}">{{ __('Delivery charges') }}</a>
                    </li>
                    <li class="nav-item d-none">
                        <a class="nav-link {{ request()->is('merchant/settings/online-payment-setup*') ? 'active' : ' ' }}"
                            href="{{ route('merchant.online.payment.setup.index') }}">{{ __('Online payment setup') }}</a>
                    </li>
                    <li class="nav-item ">
                        <a class="nav-link {{ request()->is('merchant/shops*') ? 'active' : '' }}"
                            href="{{ route('merchant-panel.shops.index') }}"> {{ __('Pickup points') }}</a>
                    </li>

                </ul>
            </div>
        </li>


    </ul>
</div>
<!-- end left sidebar -->
