<?php

use App\Http\Controllers\Backend\CurrencyController;
use App\Http\Controllers\Backend\DatabaseBackupController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\DesignationController;
use App\Http\Controllers\Backend\FrontWeb\BlogController;
use App\Http\Controllers\Backend\FrontWeb\FaqController;
use App\Http\Controllers\Backend\FrontWeb\PageController;
use App\Http\Controllers\Backend\FrontWeb\PartnerController;
use App\Http\Controllers\Backend\FrontWeb\SectionController;
use App\Http\Controllers\Backend\FrontWeb\ServiceController;
use App\Http\Controllers\Backend\FrontWeb\SocialLinkController;
use App\Http\Controllers\Backend\FrontWeb\WhyCourierController;
use App\Http\Controllers\Backend\GeneralSettingsController;
use App\Http\Controllers\Backend\IntegrationsController;
use App\Http\Controllers\Backend\PayoutSetupController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SalaryGenerateController;
use App\Http\Controllers\Backend\SmsSettingsController;
use App\Http\Controllers\Backend\Superadmin\CompanyController;
use App\Http\Controllers\Backend\Superadmin\PlanController;
use App\Http\Controllers\Backend\SupportController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\DashbordController;
use App\Http\Controllers\Frontend\FrontendController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Database\Models\Domain;

Route::middleware(['XSS', 'IsInstalled'])->group(function () {
    
    $domain = false; 
    if(Config::get('app.app_installed') == 'yes'  && Schema::hasTable('domains')):
        $domain = in_array(request()->getHost(), Domain::pluck('domain')->toArray());
    endif;

    if (!$domain):

        Auth::routes();
        //frontend
        Route::controller(FrontendController::class)->group(function () {
            Route::get('/',                      'index')->name('home');
            Route::get('/tracking',              'tracking')->name('tracking.index');
            Route::get('/about-us',              'aboutUs')->name('aboutus.index');
            Route::get('/privacy-and-policy',    'privacyPolicy')->name('privacy.policy.index');
            Route::get('/terms-of-condition',    'termsOfCondition')->name('termsof.condition.index');
            Route::get('/faq-list',              'faq')->name('get.faq.index');
            Route::post('subscribe-store',       'subscribe')->name('subscribe.store');
            Route::get('contact-send',           'contactSendPage')->name('contact.send.page');
            Route::post('contact-message-send',  'contactMessageSend')->name('contact.message.send');
            Route::get('blog-details/{id}',      'blogDetails')->name('blog.details');
            Route::get('get-blogs',              'blogs')->name('get.blogs');
            Route::get('service-details/{id}',    'serviceDetails')->name('service.details');
            Route::get('/account-delete',    'account_delete')->name('account_delete');

        });
        //end frontend

        Route::get('company/sign-up',                   [CompanyController::class, 'signUp'])->name('company.sign-up');
        Route::post('company/sign-up/store',            [CompanyController::class, 'signUpStore'])->name('company.sign-up.store');
        Route::get('company/otp-verification-form',     [CompanyController::class, 'otpVerificationForm'])->name('company.otp-verification-form');
        Route::post('company/resend-otp',               [CompanyController::class, 'resendOTP'])->name('company.resend-otp');
        Route::post('company/otp-verification',         [CompanyController::class, 'otpVerification'])->name('company.otp-verification');

        Route::group(['middleware' => 'auth'], function () {

            Route::get('/dashboard',                   [DashbordController::class, 'index'])->name('dashboard.index');
            Route::get('/subscription',                [PlanController::class, 'subscription'])->name('subscription.index');
            Route::get('/admin/subscription/history',  [PlanController::class, 'subscriptionHistory'])->name('admin.subscription.history');

            Route::prefix('super-admin')->group(function () {
                // Business-logic surface — fulfillment-router fallback
                // configuration (global platform defaults + per-tenant
                // overrides). Feature-flag gated on the controller.
                Route::prefix('business-logic')->name('super-admin.business-logic.')->group(function () {
                    Route::get('fulfillment-defaults',                       [\App\Http\Controllers\Backend\Superadmin\FulfillmentDefaultsController::class, 'index'])->name('fulfillment-defaults.index')->middleware('hasPermission:integrations_read');
                    Route::post('fulfillment-defaults/global',               [\App\Http\Controllers\Backend\Superadmin\FulfillmentDefaultsController::class, 'updateGlobal'])->name('fulfillment-defaults.update-global')->middleware('hasPermission:integrations_update');
                    Route::post('fulfillment-defaults/overrides',            [\App\Http\Controllers\Backend\Superadmin\FulfillmentDefaultsController::class, 'storeOverride'])->name('fulfillment-defaults.store-override')->middleware('hasPermission:integrations_update');
                    Route::delete('fulfillment-defaults/overrides/{id}',     [\App\Http\Controllers\Backend\Superadmin\FulfillmentDefaultsController::class, 'destroyOverride'])->whereNumber('id')->name('fulfillment-defaults.destroy-override')->middleware('hasPermission:integrations_update');
                });

                Route::prefix('plan')
                    ->controller(PlanController::class)
                    ->name('plan.')
                    ->group(function () {
                        Route::get('/',                'index')->name('index')->middleware('hasPermission:plans_read');
                        Route::get('/create',           'create')->name('create')->middleware('hasPermission:plans_create');
                        Route::post('/store',           'store')->name('store')->middleware('hasPermission:plans_create');
                        Route::get('/edit/{id}',        'edit')->name('edit')->middleware('hasPermission:plans_update');
                        Route::put('/update',            'update')->name('update')->middleware('hasPermission:plans_update');
                        Route::delete('/delete/{id}',     'delete')->name('delete')->middleware('hasPermission:plans_delete');
                        Route::get('/modules/{plan_id}',   'modulesView')->name('modules.view')->middleware('hasPermission:plans_read');
                    });
                Route::get('/subscription/history', [PlanController::class, 'subscriptionHistory'])->name('subscription.history');
                Route::prefix('company')
                    ->controller(CompanyController::class)
                    ->name('company.')
                    ->group(function () {
                        Route::get('/',               'index')->name('index')->middleware('hasPermission:company_read');
                        Route::get('/create',         'create')->name('create')->middleware('hasPermission:company_create');
                        Route::post('/store',         'store')->name('store')->middleware('hasPermission:company_create');
                        Route::get('/edit/{id}',      'edit')->name('edit')->middleware('hasPermission:company_update');
                        Route::put('/update',         'update')->name('update')->middleware('hasPermission:company_update');
                        Route::delete('/delete/{id}', 'delete')->name('delete')->middleware('hasPermission:company_delete');
                        Route::get('/subscription/switch/{id}', 'switchSubscription')->name('subscription.switch')->middleware('hasPermission:company_subscribe');
                        Route::post('/subscription/switch/store', 'switchSubscriptionStore')->name('subscription.switch.store')->middleware('hasPermission:company_subscribe');
                    });
            });


            Route::group(['prefix' => 'admin'], function () {

                Route::get('subscribe',                            [SalaryGenerateController::class, 'subscribe'])->name('subscribe.index');

                // Support route
                Route::get('support/index',         [SupportController::class, 'index'])->name('support.index')->middleware('hasPermission:support_read');
                Route::get('support/create',        [SupportController::class, 'create'])->name('support.add')->middleware('hasPermission:support_create');
                Route::post('support/store',        [SupportController::class, 'store'])->name('support.store')->middleware('hasPermission:support_create');
                Route::get('support/edit/{id}',     [SupportController::class, 'edit'])->name('support.edit')->middleware('hasPermission:support_update');
                Route::put('support/update',        [SupportController::class, 'update'])->name('support.update')->middleware('hasPermission:support_update');
                Route::delete('support/delete/{id}', [SupportController::class, 'destroy'])->name('support.delete')->middleware('hasPermission:support_delete');
                Route::get('support/view/{id}',     [SupportController::class, 'view'])->name('support.view');
                Route::post('support/reply',        [SupportController::class, 'supportReply'])->name('support.reply')->middleware('hasPermission:support_reply');
                Route::get('support/status-update/{id}',  [SupportController::class, 'statusUpdate'])->name('support.status.update')->middleware('hasPermission:support_status_update');


                Route::get('roles',                                             [RoleController::class, 'index'])->name('roles.index')->middleware('hasPermission:role_read');
                Route::get('roles/create',                                      [RoleController::class, 'create'])->name('roles.create')->middleware('hasPermission:role_create');
                Route::post('roles/store',                                      [RoleController::class, 'store'])->name('roles.store')->middleware('hasPermission:role_create');
                Route::get('roles/edit/{id}',                                   [RoleController::class, 'edit'])->name('roles.edit')->middleware('hasPermission:role_update');
                Route::put('roles/update',                                      [RoleController::class, 'update'])->name('roles.update')->middleware('hasPermission:role_update');
                Route::delete('role/delete/{id}',                               [RoleController::class, 'destroy'])->name('role.delete')->middleware('hasPermission:role_delete');

                // Designation
                Route::get('designations',              [DesignationController::class, 'index'])->name('designations.index')->middleware('hasPermission:designation_read');
                Route::get('designations/create',       [DesignationController::class, 'create'])->name('designations.create')->middleware('hasPermission:designation_create');
                Route::post('designations/store',       [DesignationController::class, 'store'])->name('designations.store')->middleware('hasPermission:designation_create');
                Route::get('designations/edit/{id}',    [DesignationController::class, 'edit'])->name('designations.edit')->middleware('hasPermission:designation_update');
                Route::put('designations/update',       [DesignationController::class, 'update'])->name('designations.update')->middleware('hasPermission:designation_update');
                Route::delete('designation/delete/{id}', [DesignationController::class, 'destroy'])->name('designation.delete')->middleware('hasPermission:designation_delete');
                // Department
                Route::get('departments',               [DepartmentController::class, 'index'])->name('departments.index')->middleware('hasPermission:department_read');
                Route::get('departments/create',        [DepartmentController::class, 'create'])->name('departments.create')->middleware('hasPermission:department_create');
                Route::post('departments/store',        [DepartmentController::class, 'store'])->name('departments.store')->middleware('hasPermission:department_create');
                Route::get('departments/edit/{id}',     [DepartmentController::class, 'edit'])->name('departments.edit')->middleware('hasPermission:department_update');
                Route::put('departments/update',        [DepartmentController::class, 'update'])->name('departments.update')->middleware('hasPermission:department_update');
                Route::delete('department/delete/{id}', [DepartmentController::class, 'destroy'])->name('department.delete')->middleware('hasPermission:department_delete');


                Route::get('users',          [UserController::class, 'index'])->name('users.index')->middleware('hasPermission:user_read');
                Route::get('users/filter',   [UserController::class, 'filter'])->name('users.filter')->middleware('hasPermission:user_read');
                Route::get('users/create',   [UserController::class, 'create'])->name('users.create')->middleware('hasPermission:user_create');
                Route::post('users/store',   [UserController::class, 'store'])->name('users.store')->middleware('hasPermission:user_create');
                Route::get('users/edit/{id}', [UserController::class, 'edit'])->name('users.edit')->middleware('hasPermission:user_update');
                Route::put('users/update',   [UserController::class, 'update'])->name('users.update')->middleware('hasPermission:user_update');
                Route::get('users/permissions/{id}',  [UserController::class, 'permission'])->name('users.permission')->middleware('hasPermission:permission_update');
                Route::put('users/permissions/update', [UserController::class, 'permissionsUpdate'])->name('users.permissions.update')->middleware('hasPermission:permission_update');
                Route::delete('user/delete/{id}',     [UserController::class, 'destroy'])->name('user.delete')->middleware('hasPermission:user_delete');

                // User profile
                Route::get('profile/{id}',                  [ProfileController::class, 'view'])->name('profile.index');
                Route::get('profile/update/{id}',           [ProfileController::class, 'create'])->name('profile.edit');
                Route::get('profile/change-password/{id}',  [ProfileController::class, 'changePassword'])->name('password.change');
                Route::put('profile/update/{id}',           [ProfileController::class, 'update'])->name('profile.update');
                Route::put('profile/update-password/{id}',  [ProfileController::class, 'updatePassword'])->name('profile.password.update');

                // General settings
                Route::get('general-settings/index',        [GeneralSettingsController::class, 'index'])->name('general-settings.index')->middleware('hasPermission:general_settings_read');
                Route::put('general-settings/update',       [GeneralSettingsController::class, 'update'])->name('general-settings.update')->middleware('hasPermission:general_settings_update');

                // E-commerce integrations (Salla / Zid / Shopify)
                Route::get('integrations',                  [IntegrationsController::class, 'index'])->name('integrations.index')->middleware('hasPermission:integrations_read');
                Route::get('integrations/{platform}/edit',  [IntegrationsController::class, 'edit'])->name('integrations.edit')->middleware('hasPermission:integrations_update');
                Route::put('integrations/{platform}',       [IntegrationsController::class, 'update'])->name('integrations.update')->middleware('hasPermission:integrations_update');

                // Per-Salla-merchant management
                Route::get('integrations/salla/stores',            [\App\Http\Controllers\Backend\SallaStoresController::class, 'index'])->name('salla.stores.index')->middleware('hasPermission:integrations_read');
                Route::get('integrations/salla/stores/{id}/edit',  [\App\Http\Controllers\Backend\SallaStoresController::class, 'edit'])->name('salla.stores.edit')->middleware('hasPermission:integrations_update');
                Route::put('integrations/salla/stores/{id}',       [\App\Http\Controllers\Backend\SallaStoresController::class, 'update'])->name('salla.stores.update')->middleware('hasPermission:integrations_update');

                // Qoyod accounting integration
                Route::get('integrations/qoyod',                    [\App\Http\Controllers\Backend\QoyodSettingsController::class, 'index'])->name('qoyod.settings.index')->middleware('hasPermission:integrations_read');
                Route::put('integrations/qoyod',                    [\App\Http\Controllers\Backend\QoyodSettingsController::class, 'update'])->name('qoyod.settings.update')->middleware('hasPermission:integrations_update');
                Route::post('integrations/qoyod/test',              [\App\Http\Controllers\Backend\QoyodSettingsController::class, 'test'])->name('qoyod.settings.test')->middleware('hasPermission:integrations_update');
                Route::post('integrations/qoyod/resync-all',        [\App\Http\Controllers\Backend\QoyodSettingsController::class, 'resyncAll'])->name('qoyod.settings.resync_all')->middleware('hasPermission:integrations_update');
                Route::post('integrations/qoyod/vendors',           [\App\Http\Controllers\Backend\QoyodSettingsController::class, 'storeVendor'])->name('qoyod.vendors.store')->middleware('hasPermission:integrations_update');
                Route::post('integrations/qoyod/vendors/{id}/sync', [\App\Http\Controllers\Backend\QoyodSettingsController::class, 'syncVendor'])->name('qoyod.vendors.sync')->middleware('hasPermission:integrations_update');

                // Daftra accounting integration
                Route::get('integrations/daftra',             [\App\Http\Controllers\Backend\DaftraSettingsController::class, 'index'])->name('daftra.settings.index')->middleware('hasPermission:integrations_read');
                Route::put('integrations/daftra',             [\App\Http\Controllers\Backend\DaftraSettingsController::class, 'update'])->name('daftra.settings.update')->middleware('hasPermission:integrations_update');
                Route::post('integrations/daftra/test',       [\App\Http\Controllers\Backend\DaftraSettingsController::class, 'test'])->name('daftra.settings.test')->middleware('hasPermission:integrations_update');
                Route::post('integrations/daftra/resync-all', [\App\Http\Controllers\Backend\DaftraSettingsController::class, 'resyncAll'])->name('daftra.settings.resync_all')->middleware('hasPermission:integrations_update');

                // Per-tenant public tracking API keys. Mirror of the tenant-scoped
                // routes/web.php entry so CLI route:list can see them.
                Route::prefix('settings/public-tracking-api-keys')->name('settings.public-tracking-api-keys.')->group(function () {
                    Route::get('/',                 [\App\Http\Controllers\Backend\Settings\PublicTrackingApiKeyController::class, 'index'])->name('index')->middleware('hasPermission:integrations_read');
                    Route::post('/',                [\App\Http\Controllers\Backend\Settings\PublicTrackingApiKeyController::class, 'store'])->name('store')->middleware('hasPermission:integrations_update');
                    Route::put('/{id}',             [\App\Http\Controllers\Backend\Settings\PublicTrackingApiKeyController::class, 'update'])->whereNumber('id')->name('update')->middleware('hasPermission:integrations_update');
                    Route::post('/{id}/regenerate', [\App\Http\Controllers\Backend\Settings\PublicTrackingApiKeyController::class, 'regenerate'])->whereNumber('id')->name('regenerate')->middleware('hasPermission:integrations_update');
                    Route::post('/{id}/toggle',     [\App\Http\Controllers\Backend\Settings\PublicTrackingApiKeyController::class, 'toggle'])->whereNumber('id')->name('toggle')->middleware('hasPermission:integrations_update');
                    Route::delete('/{id}',          [\App\Http\Controllers\Backend\Settings\PublicTrackingApiKeyController::class, 'destroy'])->whereNumber('id')->name('destroy')->middleware('hasPermission:integrations_update');
                });

                // Legacy Logestechs settings page — superseded by /admin/shipping/connections.
                // Kept as a redirect so any bookmarks / cross-context route() calls still resolve.
                Route::get('integrations/logestechs', fn () => redirect()->route('shipping.connections.index'))
                    ->name('logestechs.settings.index')
                    ->middleware('hasPermission:integrations_read');

                // Generic shipping module — connections CRUD for all providers.
                // Literal routes first, wildcard {provider} store last (see routes/web.php).
                Route::prefix('shipping')->name('shipping.')->group(function () {
                    Route::get('connections',                              [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'index'])->name('connections.index')->middleware('hasPermission:integrations_read');
                    Route::get('connections/create',                       [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'create'])->name('connections.create')->middleware('hasPermission:integrations_update');
                    Route::post('connections/test',                        [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'test'])->name('connections.test')->middleware('hasPermission:integrations_update');
                    Route::post('connections/resolve-domain/{provider}',   [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'resolveDomain'])->name('connections.resolve_domain')->middleware('hasPermission:integrations_update');
                    Route::get('connections/{id}/edit',                    [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'edit'])->whereNumber('id')->name('connections.edit')->middleware('hasPermission:integrations_update');
                    Route::put('connections/{id}',                         [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'update'])->whereNumber('id')->name('connections.update')->middleware('hasPermission:integrations_update');
                    Route::delete('connections/{id}',                      [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'destroy'])->whereNumber('id')->name('connections.destroy')->middleware('hasPermission:integrations_update');
                    Route::post('connections/{id}/default',                [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'setDefault'])->whereNumber('id')->name('connections.set_default')->middleware('hasPermission:integrations_update');
                    Route::post('connections/{provider}',                  [\App\Http\Controllers\Backend\Shipping\ShippingConnectionsController::class, 'store'])->name('connections.store')->middleware('hasPermission:integrations_update');
                });

                // Generic commerce module — Phase 2 connections CRUD.
                // Parallel registration with routes/web.php so CLI tooling
                // (`php artisan route:list`) can see the route names. Actual
                // tenant access goes through the web.php registration on the
                // tenant subdomain; this central copy 404s at request time
                // because the ConnectionController gates itself on
                // config('features.commerce_layer') anyway.
                Route::prefix('commerce')->name('commerce.')->group(function () {
                    // Phase 3.5 — Salla OAuth install (parallel central registration).
                    Route::get('connections/salla/oauth/redirect',         [\App\Http\Controllers\Backend\Commerce\SallaOAuthController::class, 'redirect'])->name('salla.oauth.redirect')->middleware('hasPermission:integrations_update');
                    Route::get('connections/salla/oauth/callback',         [\App\Http\Controllers\Backend\Commerce\SallaOAuthController::class, 'callback'])->name('salla.oauth.callback')->middleware('hasPermission:integrations_update');

                    Route::get('connections',                              [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'index'])->name('connections.index')->middleware('hasPermission:integrations_read');
                    Route::get('connections/create',                       [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'create'])->name('connections.create')->middleware('hasPermission:integrations_update');
                    Route::post('connections/test',                        [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'test'])->name('connections.test')->middleware('hasPermission:integrations_update');
                    Route::get('connections/{id}/edit',                    [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'edit'])->whereNumber('id')->name('connections.edit')->middleware('hasPermission:integrations_update');
                    Route::put('connections/{id}',                         [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'update'])->whereNumber('id')->name('connections.update')->middleware('hasPermission:integrations_update');
                    Route::delete('connections/{id}',                      [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'destroy'])->whereNumber('id')->name('connections.destroy')->middleware('hasPermission:integrations_update');
                    Route::post('connections/{id}/default',                [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'setDefault'])->whereNumber('id')->name('connections.set_default')->middleware('hasPermission:integrations_update');
                    Route::post('connections/{provider}',                  [\App\Http\Controllers\Backend\Commerce\ConnectionController::class, 'store'])->name('connections.store')->middleware('hasPermission:integrations_update');

                    // Phase 3 — webhook events viewer + replay (parallel central registration).
                    Route::get('webhook-events',                           [\App\Http\Controllers\Backend\Commerce\WebhookEventController::class, 'index'])->name('webhook-events.index')->middleware('hasPermission:integrations_read');
                    Route::get('webhook-events/{id}',                      [\App\Http\Controllers\Backend\Commerce\WebhookEventController::class, 'show'])->whereNumber('id')->name('webhook-events.show')->middleware('hasPermission:integrations_read');
                    Route::post('webhook-events/{id}/replay',              [\App\Http\Controllers\Backend\Commerce\WebhookEventController::class, 'replay'])->whereNumber('id')->name('webhook-events.replay')->middleware('hasPermission:integrations_update');

                    // Phase 9 — consolidated health dashboard.
                    Route::get('health',                                   [\App\Http\Controllers\Backend\Commerce\HealthController::class, 'index'])->name('health.index')->middleware('hasPermission:integrations_read');
                });

                // Phase 5 — OMS orders viewer (parallel central registration).
                Route::prefix('oms')->name('oms.')->group(function () {
                    Route::get('orders',                                   [\App\Http\Controllers\Backend\Oms\OrderController::class, 'index'])->name('orders.index')->middleware('hasPermission:integrations_read');
                    Route::get('orders/{id}',                              [\App\Http\Controllers\Backend\Oms\OrderController::class, 'show'])->whereNumber('id')->name('orders.show')->middleware('hasPermission:integrations_read');
                });

                // Phase 8 — failed-jobs viewer (parallel central registration).
                Route::prefix('ops')->name('ops.')->group(function () {
                    Route::get('failed-jobs',            [\App\Http\Controllers\Backend\Ops\FailedJobsController::class, 'index'])->name('failed-jobs.index')->middleware('hasPermission:integrations_read');
                    Route::post('failed-jobs/{id}/retry', [\App\Http\Controllers\Backend\Ops\FailedJobsController::class, 'retry'])->whereNumber('id')->name('failed-jobs.retry')->middleware('hasPermission:integrations_update');
                    Route::delete('failed-jobs/{id}',    [\App\Http\Controllers\Backend\Ops\FailedJobsController::class, 'forget'])->whereNumber('id')->name('failed-jobs.forget')->middleware('hasPermission:integrations_update');
                });

                // Phase 6 — Fulfillment routes CRUD + fulfillments viewer (parallel central registration).
                Route::prefix('fulfillment')->name('fulfillment.')->group(function () {
                    Route::get('routes',                                   [\App\Http\Controllers\Backend\Fulfillment\FulfillmentRouteController::class, 'index'])->name('routes.index')->middleware('hasPermission:integrations_read');
                    Route::get('routes/create',                            [\App\Http\Controllers\Backend\Fulfillment\FulfillmentRouteController::class, 'create'])->name('routes.create')->middleware('hasPermission:integrations_update');
                    Route::post('routes',                                  [\App\Http\Controllers\Backend\Fulfillment\FulfillmentRouteController::class, 'store'])->name('routes.store')->middleware('hasPermission:integrations_update');
                    Route::get('routes/{id}/edit',                         [\App\Http\Controllers\Backend\Fulfillment\FulfillmentRouteController::class, 'edit'])->whereNumber('id')->name('routes.edit')->middleware('hasPermission:integrations_update');
                    Route::put('routes/{id}',                              [\App\Http\Controllers\Backend\Fulfillment\FulfillmentRouteController::class, 'update'])->whereNumber('id')->name('routes.update')->middleware('hasPermission:integrations_update');
                    Route::delete('routes/{id}',                           [\App\Http\Controllers\Backend\Fulfillment\FulfillmentRouteController::class, 'destroy'])->whereNumber('id')->name('routes.destroy')->middleware('hasPermission:integrations_update');
                    Route::get('fulfillments',                             [\App\Http\Controllers\Backend\Fulfillment\FulfillmentController::class, 'index'])->name('fulfillments.index')->middleware('hasPermission:integrations_read');
                });

                // Odoo ERP integration
                Route::get('integrations/odoo',                    [\App\Http\Controllers\Backend\OdooSettingsController::class, 'index'])->name('odoo.settings.index')->middleware('hasPermission:integrations_read');
                Route::put('integrations/odoo',                    [\App\Http\Controllers\Backend\OdooSettingsController::class, 'update'])->name('odoo.settings.update')->middleware('hasPermission:integrations_update');
                Route::post('integrations/odoo/test',              [\App\Http\Controllers\Backend\OdooSettingsController::class, 'test'])->name('odoo.settings.test')->middleware('hasPermission:integrations_update');
                Route::post('integrations/odoo/resync-all',        [\App\Http\Controllers\Backend\OdooSettingsController::class, 'resyncAll'])->name('odoo.settings.resync_all')->middleware('hasPermission:integrations_update');
                Route::post('integrations/odoo/vendors',           [\App\Http\Controllers\Backend\OdooSettingsController::class, 'storeVendor'])->name('odoo.vendors.store')->middleware('hasPermission:integrations_update');
                Route::post('integrations/odoo/vendors/{id}/sync', [\App\Http\Controllers\Backend\OdooSettingsController::class, 'syncVendor'])->name('odoo.vendors.sync')->middleware('hasPermission:integrations_update');


                //payout setup settings
                Route::get('/settings/pay-out/setup',                            [PayoutSetupController::class, 'index'])->name('payout.setup.settings.index')->middleware('hasPermission:payout_setup_settings_read');
                Route::put('/settings/pay-out/setup/update/{paymentmethod}',     [PayoutSetupController::class, 'PayoutSetupUpdate'])->name('payout.setup.settings.update')->middleware('hasPermission:payout_setup_settings_update');

                Route::get('sms-settings/index',            [SmsSettingsController::class, 'index'])->name('sms-settings.index')->middleware('hasPermission:sms_settings_read');
                Route::get('sms-settings/edit/{id}',        [SmsSettingsController::class, 'edit'])->name('sms-settings.edit')->middleware('hasPermission:sms_settings_update');
                Route::put('sms-settings/update/{id}',      [SmsSettingsController::class, 'update'])->name('sms-settings.update')->middleware('hasPermission:sms_settings_update');
                Route::post('sms-settings/status',          [SmsSettingsController::class, 'status'])->name('sms-settings.status')->middleware('hasPermission:sms_settings_status_change');

                //currency settings
                Route::get('currency',                      [CurrencyController::class, 'index'])->name('currency.index')->middleware('hasPermission:currency_read');
                Route::get('currency/create',               [CurrencyController::class, 'create'])->name('currency.create')->middleware('hasPermission:currency_create');
                Route::post('currency/store',               [CurrencyController::class, 'store'])->name('currency.store')->middleware('hasPermission:currency_create');
                Route::get('currency/edit/{id}',            [CurrencyController::class, 'edit'])->name('currency.edit')->middleware('hasPermission:currency_update');
                Route::put('currency/update',               [CurrencyController::class, 'update'])->name('currency.update')->middleware('hasPermission:currency_update');
                Route::delete('currency/delete/{id}',       [CurrencyController::class, 'delete'])->name('currency.delete')->middleware('hasPermission:currency_delete');

                // database backup
                Route::get('/database-backup',             [DatabaseBackupController::class, 'index'])->name('database.backup.index')->middleware('hasPermission:database_backup_read');
                Route::get('database-backup/download',     [DatabaseBackupController::class, 'databaseBackup'])->name('database.backup.download')->middleware('hasPermission:database_backup_read');


                //Front web (frontend setup) ============================
                Route::prefix('front-web')->group(function () {
                    //social link
                    Route::prefix('social-link')
                        ->name('social.link.')
                        ->controller(SocialLinkController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:social_link_read');
                            Route::get('create',         'create')->name('create')->middleware('hasPermission:social_link_create');
                            Route::post('store',         'store')->name('store')->middleware('hasPermission:social_link_create');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:social_link_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:social_link_update');
                            Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:social_link_delete');
                        });

                    //Service
                    Route::prefix('service')
                        ->name('service.')
                        ->controller(ServiceController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:service_read');
                            Route::get('create',         'create')->name('create')->middleware('hasPermission:service_create');
                            Route::post('store',         'store')->name('store')->middleware('hasPermission:service_create');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:service_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:service_update');
                            Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:service_delete');
                        });

                    //why rushly (formerly why-courier)
                    Route::prefix('why-rushly')
                        ->name('why.rushly.')
                        ->controller(WhyCourierController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:why_courier_read');
                            Route::get('create',         'create')->name('create')->middleware('hasPermission:why_courier_create');
                            Route::post('store',         'store')->name('store')->middleware('hasPermission:why_courier_create');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:why_courier_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:why_courier_update');
                            Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:why_courier_delete');
                        });

                    //faq
                    Route::prefix('faq')
                        ->name('faq.')
                        ->controller(FaqController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:faq_read');
                            Route::get('create',         'create')->name('create')->middleware('hasPermission:faq_create');
                            Route::post('store',         'store')->name('store')->middleware('hasPermission:faq_create');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:faq_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:faq_update');
                            Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:faq_delete');
                        });

                    //partner
                    Route::prefix('partner')
                        ->name('partner.')
                        ->controller(PartnerController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:partner_read');
                            Route::get('create',         'create')->name('create')->middleware('hasPermission:partner_create');
                            Route::post('store',         'store')->name('store')->middleware('hasPermission:partner_create');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:partner_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:partner_update');
                            Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:partner_delete');
                        });

                    //blogs
                    Route::prefix('blogs')
                        ->name('blogs.')
                        ->controller(BlogController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:blogs_read');
                            Route::get('create',         'create')->name('create')->middleware('hasPermission:blogs_create');
                            Route::post('store',         'store')->name('store')->middleware('hasPermission:blogs_create');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:blogs_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:blogs_update');
                            Route::delete('delete/{id}', 'delete')->name('delete')->middleware('hasPermission:blogs_delete');
                        });

                    //pages
                    Route::prefix('pages')
                        ->name('pages.')
                        ->controller(PageController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:pages_read');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:pages_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:pages_update');
                        });

                    //pages
                    Route::prefix('section')
                        ->name('section.')
                        ->controller(SectionController::class)
                        ->group(function () {
                            Route::get('/',              'index')->name('index')->middleware('hasPermission:section_read');
                            Route::get('edit/{id}',      'edit')->name('edit')->middleware('hasPermission:section_update');
                            Route::put('update/{id}',    'update')->name('update')->middleware('hasPermission:section_update');
                        });
                });
                //end Front web (frontend setup) ============================


            });
        });
    endif;
});
