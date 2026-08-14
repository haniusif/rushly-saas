# Application Routes

_Generated 2026-06-20 09:14:57 UTC. Tenant-domain-gated routes were exposed by spoofing host `admin.rushly.tech` before kernel bootstrap._

Total routes: **988** across **179** groups.

## Table of contents

- [`/`](#) — 1 routes
- [`_debugbar`](#debugbar) — 6 routes
- [`_ignition`](#ignition) — 3 routes
- [`aamarpay-fail`](#aamarpay-fail) — 1 routes
- [`aamarpay-payment`](#aamarpay-payment) — 1 routes
- [`aamarpay-success`](#aamarpay-success) — 1 routes
- [`about-us`](#about-us) — 1 routes
- [`account-delete`](#account-delete) — 1 routes
- [`admin/abnormal`](#admin-abnormal) — 7 routes
- [`admin/account-heads`](#admin-account-heads) — 1 routes
- [`admin/accounts`](#admin-accounts) — 9 routes
- [`admin/addons`](#admin-addons) — 8 routes
- [`admin/areas`](#admin-areas) — 6 routes
- [`admin/asset-category`](#admin-asset-category) — 7 routes
- [`admin/assets`](#admin-assets) — 7 routes
- [`admin/assign-pickup`](#admin-assign-pickup) — 2 routes
- [`admin/assign-return-to-merchant`](#admin-assign-return-to-merchant) — 1 routes
- [`admin/bank-transaction`](#admin-bank-transaction) — 4 routes
- [`admin/bulk_action`](#admin-bulk-action) — 1 routes
- [`admin/cities`](#admin-cities) — 6 routes
- [`admin/countries`](#admin-countries) — 6 routes
- [`admin/currency`](#admin-currency) — 6 routes
- [`admin/database-backup`](#admin-database-backup) — 2 routes
- [`admin/delivery-category`](#admin-delivery-category) — 7 routes
- [`admin/delivery-charge`](#admin-delivery-charge) — 8 routes
- [`admin/delivery-type`](#admin-delivery-type) — 2 routes
- [`admin/deliveryman`](#admin-deliveryman) — 7 routes
- [`admin/department`](#admin-department) — 1 routes
- [`admin/departments`](#admin-departments) — 5 routes
- [`admin/designation`](#admin-designation) — 1 routes
- [`admin/designations`](#admin-designations) — 5 routes
- [`admin/expense`](#admin-expense) — 9 routes
- [`admin/fraud`](#admin-fraud) — 6 routes
- [`admin/front-web`](#admin-front-web) — 42 routes
- [`admin/fund-transfer`](#admin-fund-transfer) — 10 routes
- [`admin/general-settings`](#admin-general-settings) — 2 routes
- [`admin/get-merchant-cod`](#admin-get-merchant-cod) — 1 routes
- [`admin/global-search`](#admin-global-search) — 1 routes
- [`admin/googlemap-settings`](#admin-googlemap-settings) — 2 routes
- [`admin/hub`](#admin-hub) — 21 routes
- [`admin/hub-payment`](#admin-hub-payment) — 5 routes
- [`admin/hubs`](#admin-hubs) — 7 routes
- [`admin/income`](#admin-income) — 11 routes
- [`admin/integrations`](#admin-integrations) — 3 routes
- [`admin/liquid-fragile`](#admin-liquid-fragile) — 4 routes
- [`admin/log-activity-view`](#admin-log-activity-view) — 1 routes
- [`admin/logs`](#admin-logs) — 1 routes
- [`admin/merchant`](#admin-merchant) — 39 routes
- [`admin/ndr`](#admin-ndr) — 7 routes
- [`admin/news-offer`](#admin-news-offer) — 6 routes
- [`admin/notification-settings`](#admin-notification-settings) — 2 routes
- [`admin/online-payment-list`](#admin-online-payment-list) — 1 routes
- [`admin/operational-area`](#admin-operational-area) — 1 routes
- [`admin/operational-areas`](#admin-operational-areas) — 5 routes
- [`admin/packaging`](#admin-packaging) — 7 routes
- [`admin/paid`](#admin-paid) — 1 routes
- [`admin/parcel`](#admin-parcel) — 78 routes
- [`admin/parcel-reports-print-page`](#admin-parcel-reports-print-page) — 1 routes
- [`admin/parcel-wise-profit-print-page`](#admin-parcel-wise-profit-print-page) — 1 routes
- [`admin/parcels`](#admin-parcels) — 3 routes
- [`admin/payment`](#admin-payment) — 12 routes
- [`admin/payment_get_cod`](#admin-payment-get-cod) — 1 routes
- [`admin/payout`](#admin-payout) — 25 routes
- [`admin/pickup-request`](#admin-pickup-request) — 2 routes
- [`admin/profile`](#admin-profile) — 5 routes
- [`admin/push-notification`](#admin-push-notification) — 5 routes
- [`admin/reports`](#admin-reports) — 14 routes
- [`admin/reports-tracking-parcels`](#admin-reports-tracking-parcels) — 1 routes
- [`admin/request`](#admin-request) — 6 routes
- [`admin/role`](#admin-role) — 1 routes
- [`admin/roles`](#admin-roles) — 5 routes
- [`admin/salary`](#admin-salary) — 13 routes
- [`admin/salarys`](#admin-salarys) — 4 routes
- [`admin/settings`](#admin-settings) — 8 routes
- [`admin/sms-send-settings`](#admin-sms-send-settings) — 2 routes
- [`admin/sms-settings`](#admin-sms-settings) — 7 routes
- [`admin/social-login-settings`](#admin-social-login-settings) — 2 routes
- [`admin/subscribe`](#admin-subscribe) — 1 routes
- [`admin/subscription`](#admin-subscription) — 1 routes
- [`admin/supplier-companies`](#admin-supplier-companies) — 5 routes
- [`admin/supplier-company`](#admin-supplier-company) — 1 routes
- [`admin/support`](#admin-support) — 9 routes
- [`admin/tms`](#admin-tms) — 3 routes
- [`admin/todo`](#admin-todo) — 7 routes
- [`admin/transertohub-selected-hub`](#admin-transertohub-selected-hub) — 1 routes
- [`admin/user`](#admin-user) — 1 routes
- [`admin/users`](#admin-users) — 8 routes
- [`admin/wallet-request`](#admin-wallet-request) — 5 routes
- [`admin/wms`](#admin-wms) — 57 routes
- [`admin/zatca`](#admin-zatca) — 7 routes
- [`api/delivery`](#api-delivery) — 5 routes
- [`api/olivery`](#api-olivery) — 2 routes
- [`api/panda`](#api-panda) — 2 routes
- [`api/user`](#api-user) — 1 routes
- [`api/v10`](#api-v10) — 139 routes
- [`api/zajel`](#api-zajel) — 1 routes
- [`bkash`](#bkash) — 2 routes
- [`blog-details`](#blog-details) — 1 routes
- [`cancel`](#cancel) — 1 routes
- [`category`](#category) — 6 routes
- [`company`](#company) — 5 routes
- [`contact-message-send`](#contact-message-send) — 1 routes
- [`contact-send`](#contact-send) — 1 routes
- [`dashboard`](#dashboard) — 1 routes
- [`dashboard-finance`](#dashboard-finance) — 1 routes
- [`dashboard-influencer`](#dashboard-influencer) — 1 routes
- [`dashboard-sales`](#dashboard-sales) — 1 routes
- [`deliveryMan`](#deliveryman) — 1 routes
- [`ecommerce-product`](#ecommerce-product) — 1 routes
- [`ecommerce-product-checkout`](#ecommerce-product-checkout) — 1 routes
- [`ecommerce-product-single`](#ecommerce-product-single) — 1 routes
- [`env-editor`](#env-editor) — 11 routes
- [`facebook`](#facebook) — 1 routes
- [`fail`](#fail) — 1 routes
- [`faq-list`](#faq-list) — 1 routes
- [`finish`](#finish) — 1 routes
- [`get-blogs`](#get-blogs) — 1 routes
- [`google`](#google) — 1 routes
- [`impersonate`](#impersonate) — 1 routes
- [`influencer-finder`](#influencer-finder) — 1 routes
- [`influencer-profile`](#influencer-profile) — 1 routes
- [`install`](#install) — 1 routes
- [`installing`](#installing) — 1 routes
- [`ipn`](#ipn) — 1 routes
- [`localization`](#localization) — 1 routes
- [`login`](#login) — 4 routes
- [`logout`](#logout) — 1 routes
- [`merchant/accounts`](#merchant-accounts) — 10 routes
- [`merchant/apply`](#merchant-apply) — 3 routes
- [`merchant/dashboard`](#merchant-dashboard) — 1 routes
- [`merchant/exports`](#merchant-exports) — 2 routes
- [`merchant/fraud`](#merchant-fraud) — 8 routes
- [`merchant/invoice`](#merchant-invoice) — 4 routes
- [`merchant/m_parcel`](#merchant-m-parcel) — 3 routes
- [`merchant/my-wallet`](#merchant-my-wallet) — 4 routes
- [`merchant/news-offer`](#merchant-news-offer) — 1 routes
- [`merchant/online-payment`](#merchant-online-payment) — 7 routes
- [`merchant/online-payment-received-list`](#merchant-online-payment-received-list) — 1 routes
- [`merchant/otp-verification`](#merchant-otp-verification) — 1 routes
- [`merchant/otp-verification-form`](#merchant-otp-verification-form) — 1 routes
- [`merchant/parcel`](#merchant-parcel) — 21 routes
- [`merchant/parcel-bank`](#merchant-parcel-bank) — 1 routes
- [`merchant/parcel-reports-print-page`](#merchant-parcel-reports-print-page) — 1 routes
- [`merchant/payment`](#merchant-payment) — 1 routes
- [`merchant/payment-request`](#merchant-payment-request) — 6 routes
- [`merchant/pickup-request`](#merchant-pickup-request) — 2 routes
- [`merchant/profile`](#merchant-profile) — 5 routes
- [`merchant/reports`](#merchant-reports) — 5 routes
- [`merchant/resend-otp`](#merchant-resend-otp) — 1 routes
- [`merchant/settings`](#merchant-settings) — 4 routes
- [`merchant/shops`](#merchant-shops) — 6 routes
- [`merchant/sign-up`](#merchant-sign-up) — 1 routes
- [`merchant/sign-up-store`](#merchant-sign-up-store) — 1 routes
- [`merchant/support`](#merchant-support) — 8 routes
- [`merchant/zatca`](#merchant-zatca) — 7 routes
- [`online-payment`](#online-payment) — 1 routes
- [`password`](#password) — 6 routes
- [`pay-via-ajax`](#pay-via-ajax) — 1 routes
- [`payment-cancelled`](#payment-cancelled) — 1 routes
- [`payment-completed`](#payment-completed) — 1 routes
- [`privacy-and-policy`](#privacy-and-policy) — 1 routes
- [`register`](#register) — 2 routes
- [`sanctum`](#sanctum) — 1 routes
- [`search-charts`](#search-charts) — 1 routes
- [`service-details`](#service-details) — 1 routes
- [`set-locale`](#set-locale) — 1 routes
- [`shipment-location`](#shipment-location) — 2 routes
- [`skrill`](#skrill) — 1 routes
- [`skrill-make-payment`](#skrill-make-payment) — 1 routes
- [`store-token`](#store-token) — 1 routes
- [`subscribe-store`](#subscribe-store) — 1 routes
- [`subscription`](#subscription) — 4 routes
- [`success`](#success) — 1 routes
- [`super-admin/company`](#super-admin-company) — 8 routes
- [`super-admin/plan`](#super-admin-plan) — 7 routes
- [`super-admin/subscription`](#super-admin-subscription) — 1 routes
- [`tenancy`](#tenancy) — 1 routes
- [`terms-of-condition`](#terms-of-condition) — 1 routes
- [`tracking`](#tracking) — 1 routes

## `/`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `//` | `home` | `FrontendController@index` | <sub>web|XSS|IsInstalled</sub> |

## `_debugbar`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/_debugbar/assets/javascript` | `debugbar.assets.js` | `AssetController@js` | <sub>DebugbarEnabled|Closure</sub> |
| GET | `/_debugbar/assets/stylesheets` | `debugbar.assets.css` | `AssetController@css` | <sub>DebugbarEnabled|Closure</sub> |
| DELETE | `/_debugbar/cache/{key}/{tags?}` | `debugbar.cache.delete` | `CacheController@delete` | <sub>DebugbarEnabled|Closure</sub> |
| GET | `/_debugbar/clockwork/{id}` | `debugbar.clockwork` | `OpenHandlerController@clockwork` | <sub>DebugbarEnabled|Closure</sub> |
| GET | `/_debugbar/open` | `debugbar.openhandler` | `OpenHandlerController@handle` | <sub>DebugbarEnabled|Closure</sub> |
| POST | `/_debugbar/queries/explain` | `debugbar.queries.explain` | `QueriesController@explain` | <sub>DebugbarEnabled|Closure</sub> |

## `_ignition`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/_ignition/execute-solution` | `ignition.executeSolution` | `Spatie\LaravelIgnition\Http\Controllers\ExecuteSolutionController` | <sub>RunnableSolutionsEnabled</sub> |
| GET | `/_ignition/health-check` | `ignition.healthCheck` | `Spatie\LaravelIgnition\Http\Controllers\HealthCheckController` | <sub>RunnableSolutionsEnabled</sub> |
| POST | `/_ignition/update-config` | `ignition.updateConfig` | `Spatie\LaravelIgnition\Http\Controllers\UpdateConfigController` | <sub>RunnableSolutionsEnabled</sub> |

## `aamarpay-fail`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/aamarpay-fail` | `aamarpay.payment.fail` | `AamarpayController@fail` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `aamarpay-payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/aamarpay-payment` | `aamarpay.payment` | `AamarpayController@payment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `aamarpay-success`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/aamarpay-success` | `aamarpay.payment.success` | `AamarpayController@success` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `about-us`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/about-us` | `aboutus.index` | `FrontendController@aboutUs` | <sub>web|XSS|IsInstalled</sub> |

## `account-delete`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/account-delete` | `account_delete` | `FrontendController@account_delete` | <sub>web|XSS|IsInstalled</sub> |

## `admin/abnormal`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/abnormal/settings` | `abnormal.settings` | `AbnormalShipmentController@settings` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |
| PUT | `/admin/abnormal/settings` | `abnormal.settings.update` | `AbnormalShipmentController@updateSettings` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |
| POST | `/admin/abnormal/{abnormal}/action` | `abnormal.action` | `AbnormalShipmentController@takeAction` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |
| PUT | `/admin/abnormal/{abnormal}/assign` | `abnormal.assign` | `AbnormalShipmentController@assign` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |
| PUT | `/admin/abnormal/{abnormal}/resolve` | `abnormal.resolve` | `AbnormalShipmentController@resolve` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |
| GET | `/admin/abnormal/{abnormal}` | `abnormal.show` | `AbnormalShipmentController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |
| GET | `/admin/abnormal` | `abnormal.index` | `AbnormalShipmentController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:abnormal_manage</sub> |

## `admin/account-heads`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/account-heads` | `account.heads.index` | `AccountHeadsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_heads_read</sub> |

## `admin/accounts`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/accounts/create` | `accounts.create` | `AccountController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_create</sub> |
| POST | `/admin/accounts/current-balance` | `accounts.current-balance` | `AccountController@currentBalance` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/admin/accounts/delete/{id}` | `accounts.delete` | `AccountController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_delete</sub> |
| GET | `/admin/accounts/edit/{id}` | `accounts.edit` | `AccountController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_update</sub> |
| GET | `/admin/accounts/filter` | `accounts.filter` | `AccountController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_read</sub> |
| GET | `/admin/accounts/index` | `accounts.index` | `AccountController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_read</sub> |
| POST | `/admin/accounts/store` | `accounts.store` | `AccountController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_create</sub> |
| PUT | `/admin/accounts/update/{id}` | `accounts.update` | `AccountController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:account_update</sub> |
| GET | `/admin/accounts/view/{id}` | `accounts.view` | `AccountController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/addons`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/addons/activation` | `addons.activation` | `AddonController@activation` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/addons/create` | `addons.create` | `AddonController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/addons/{addon}/edit` | `addons.edit` | `AddonController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/admin/addons/{addon}` | `addons.destroy` | `AddonController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/addons/{addon}` | `addons.show` | `AddonController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT,PATCH | `/admin/addons/{addon}` | `addons.update` | `AddonController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/addons` | `addons.index` | `AddonController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/addons` | `addons.store` | `AddonController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/areas`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/areas/create` | `area.create` | `AreaController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/admin/areas/delete/{id}` | `area.delete` | `AreaController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/areas/edit/{id}` | `area.edit` | `AreaController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/areas/store` | `area.store` | `AreaController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/admin/areas/update/{id}` | `area.update` | `AreaController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/areas` | `area.index` | `AreaController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/asset-category`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/asset-category/create` | `asset-category.create` | `AssetcategoryController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_create</sub> |
| DELETE | `/admin/asset-category/delete/{id}` | `asset-category.delete` | `AssetcategoryController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_delete</sub> |
| GET | `/admin/asset-category/edit/{id}` | `asset-category.edit` | `AssetcategoryController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_update</sub> |
| GET | `/admin/asset-category/index` | `asset-category.index` | `AssetcategoryController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_read</sub> |
| POST | `/admin/asset-category/store` | `asset-category.store` | `AssetcategoryController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_create</sub> |
| PUT | `/admin/asset-category/update` | `asset-category.update` | `AssetcategoryController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_update</sub> |
| GET | `/admin/asset-category/view/{id}` | `asset-category.view` | `AssetcategoryController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:asset_category_read</sub> |

## `admin/assets`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/assets/create` | `asset.create` | `AssetController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_create</sub> |
| DELETE | `/admin/assets/delete/{id}` | `asset.delete` | `AssetController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_delete</sub> |
| GET | `/admin/assets/edit/{id}` | `asset.edit` | `AssetController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_update</sub> |
| GET | `/admin/assets/index` | `asset.index` | `AssetController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_read</sub> |
| POST | `/admin/assets/store` | `asset.store` | `AssetController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_create</sub> |
| PUT | `/admin/assets/update` | `asset.update` | `AssetController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_update</sub> |
| GET | `/admin/assets/view/{id}` | `asset.view` | `AssetController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:assets_read</sub> |

## `admin/assign-pickup`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/assign-pickup/bulk` | `parcel.assign-pickup-bulk` | `ParcelController@AssignPickupBulk` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/assign-pickup/parcel/search` | `assign-pickup.parcel.search` | `ParcelController@AssignPickupParcelSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/assign-return-to-merchant`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/assign-return-to-merchant/parcel/search` | `assign-return-to-merchant.parcel.search` | `ParcelController@AssignReturnToMerchantParcelSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/bank-transaction`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/bank-transaction/filter/print` | `bank.transaction.filter.print` | `BankTransactionController@bankTransactionPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/bank-transaction/filter` | `bank-transaction.filter` | `BankTransactionController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:bank_transaction_read</sub> |
| GET | `/admin/bank-transaction/specific/search` | `bank.transaction.specific.search` | `BankTransactionController@bankTransactionSpecificSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/bank-transaction` | `bank-transaction.index` | `BankTransactionController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:bank_transaction_read</sub> |

## `admin/bulk_action`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/bulk_action` | `parcel.bulk_action` | `ParcelBulkActionController@parcel_bulk_action` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |

## `admin/cities`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/cities/create` | `city.create` | `CityController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/admin/cities/delete/{id}` | `city.delete` | `CityController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/cities/edit/{id}` | `city.edit` | `CityController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/cities/store` | `city.store` | `CityController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/admin/cities/update/{id}` | `city.update` | `CityController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/cities` | `city.index` | `CityController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/countries`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/countries/create` | `country.create` | `CountryController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/admin/countries/delete/{id}` | `country.delete` | `CountryController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/countries/edit/{id}` | `country.edit` | `CountryController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/countries/store` | `country.store` | `CountryController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/admin/countries/update/{id}` | `country.update` | `CountryController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/countries` | `country.index` | `CountryController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/currency`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/currency/create` | `currency.create` | `CurrencyController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:currency_create</sub> |
| DELETE | `/admin/currency/delete/{id}` | `currency.delete` | `CurrencyController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:currency_delete</sub> |
| GET | `/admin/currency/edit/{id}` | `currency.edit` | `CurrencyController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:currency_update</sub> |
| POST | `/admin/currency/store` | `currency.store` | `CurrencyController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:currency_create</sub> |
| PUT | `/admin/currency/update` | `currency.update` | `CurrencyController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:currency_update</sub> |
| GET | `/admin/currency` | `currency.index` | `CurrencyController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:currency_read</sub> |

## `admin/database-backup`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/database-backup/download` | `database.backup.download` | `DatabaseBackupController@databaseBackup` | <sub>web|XSS|IsInstalled|auth|hasPermission:database_backup_read</sub> |
| GET | `/admin/database-backup` | `database.backup.index` | `DatabaseBackupController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:database_backup_read</sub> |

## `admin/delivery-category`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/delivery-category/create` | `delivery-category.create` | `DeliverycategoryController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_category_create</sub> |
| DELETE | `/admin/delivery-category/delete/{id}` | `delivery-category.delete` | `DeliverycategoryController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_category_delete</sub> |
| GET | `/admin/delivery-category/edit/{id}` | `delivery-category.edit` | `DeliverycategoryController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_category_update</sub> |
| GET | `/admin/delivery-category/index` | `delivery-category.index` | `DeliverycategoryController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_category_read</sub> |
| POST | `/admin/delivery-category/store` | `delivery-category.store` | `DeliverycategoryController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_category_create</sub> |
| PUT | `/admin/delivery-category/update` | `delivery-category.update` | `DeliverycategoryController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_category_update</sub> |
| GET | `/admin/delivery-category/view/{id}` | `delivery-category.view` | `DeliverycategoryController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/delivery-charge`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/delivery-charge/create` | `delivery-charge.create` | `DeliveryChargeController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_create</sub> |
| DELETE | `/admin/delivery-charge/delete/{id}` | `delivery-charge.delete` | `DeliveryChargeController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_delete</sub> |
| GET | `/admin/delivery-charge/edit/{id}` | `delivery-charge.edit` | `DeliveryChargeController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_update</sub> |
| GET | `/admin/delivery-charge/filter` | `delivery-charge.filter` | `DeliveryChargeController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_read</sub> |
| GET | `/admin/delivery-charge/index` | `delivery-charge.index` | `DeliveryChargeController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_read</sub> |
| POST | `/admin/delivery-charge/store` | `delivery-charge.store` | `DeliveryChargeController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_create</sub> |
| PUT | `/admin/delivery-charge/update` | `delivery-charge.update` | `DeliveryChargeController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_charge_update</sub> |
| GET | `/admin/delivery-charge/view/{id}` | `delivery-charge.view` | `DeliveryChargeController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/delivery-type`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/delivery-type/index` | `delivery-type.index` | `DeliveryTypeController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_type_read</sub> |
| POST | `/admin/delivery-type/status` | `delivery-type.status` | `DeliveryTypeController@status` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_type_status_change</sub> |

## `admin/deliveryman`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/deliveryman/create` | `deliveryman.create` | `DeliveryManController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_create</sub> |
| DELETE | `/admin/deliveryman/delete/{id}` | `deliveryman.delete` | `DeliveryManController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_delete</sub> |
| GET | `/admin/deliveryman/edit/{id}` | `deliveryman.edit` | `DeliveryManController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_update</sub> |
| GET | `/admin/deliveryman/filter` | `deliveryman.filter` | `DeliveryManController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_read</sub> |
| POST | `/admin/deliveryman/store` | `deliveryman.store` | `DeliveryManController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_create</sub> |
| PUT | `/admin/deliveryman/update` | `deliveryman.update` | `DeliveryManController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_update</sub> |
| GET | `/admin/deliveryman` | `deliveryman.index` | `DeliveryManController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_read</sub> |

## `admin/department`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/department/delete/{id}` | `department.delete` | `DepartmentController@destroy` | <sub>web|XSS|IsInstalled|auth|hasPermission:department_delete</sub> |

## `admin/departments`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/departments/create` | `departments.create` | `DepartmentController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:department_create</sub> |
| GET | `/admin/departments/edit/{id}` | `departments.edit` | `DepartmentController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:department_update</sub> |
| POST | `/admin/departments/store` | `departments.store` | `DepartmentController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:department_create</sub> |
| PUT | `/admin/departments/update` | `departments.update` | `DepartmentController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:department_update</sub> |
| GET | `/admin/departments` | `departments.index` | `DepartmentController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:department_read</sub> |

## `admin/designation`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/designation/delete/{id}` | `designation.delete` | `DesignationController@destroy` | <sub>web|XSS|IsInstalled|auth|hasPermission:designation_delete</sub> |

## `admin/designations`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/designations/create` | `designations.create` | `DesignationController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:designation_create</sub> |
| GET | `/admin/designations/edit/{id}` | `designations.edit` | `DesignationController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:designation_update</sub> |
| POST | `/admin/designations/store` | `designations.store` | `DesignationController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:designation_create</sub> |
| PUT | `/admin/designations/update` | `designations.update` | `DesignationController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:designation_update</sub> |
| GET | `/admin/designations` | `designations.index` | `DesignationController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:designation_read</sub> |

## `admin/expense`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/expense/create` | `expense.create` | `ExpenseController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_create</sub> |
| DELETE | `/admin/expense/delete/{id}` | `expense.delete` | `ExpenseController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_delete</sub> |
| GET | `/admin/expense/edit/{id}` | `expense.edit` | `ExpenseController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_update</sub> |
| GET | `/admin/expense/filter` | `expense.filter` | `ExpenseController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_read</sub> |
| POST | `/admin/expense/search-account/{id}` | `expense.search-account` | `ExpenseController@searchAccount` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/expense/store` | `expense.store` | `ExpenseController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_create</sub> |
| PUT | `/admin/expense/update/{id}` | `expense.update` | `ExpenseController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_update</sub> |
| POST | `/admin/expense/users` | `expense.users` | `ExpenseController@ExpenseUsers` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/expense` | `expense.index` | `ExpenseController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:expense_read</sub> |

## `admin/fraud`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/fraud/create` | `fraud.create` | `FraudController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fraud_create</sub> |
| DELETE | `/admin/fraud/delete/{id}` | `fraud.delete` | `FraudController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fraud_delete</sub> |
| GET | `/admin/fraud/edit/{id}` | `fraud.edit` | `FraudController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fraud_update</sub> |
| POST | `/admin/fraud/store` | `fraud.store` | `FraudController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fraud_create</sub> |
| PUT | `/admin/fraud/update` | `fraud.update` | `FraudController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fraud_update</sub> |
| GET | `/admin/fraud` | `fraud.index` | `FraudController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fraud_read</sub> |

## `admin/front-web`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/front-web/blogs/create` | `blogs.create` | `BlogController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:blogs_create</sub> |
| DELETE | `/admin/front-web/blogs/delete/{id}` | `blogs.delete` | `BlogController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:blogs_delete</sub> |
| GET | `/admin/front-web/blogs/edit/{id}` | `blogs.edit` | `BlogController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:blogs_update</sub> |
| POST | `/admin/front-web/blogs/store` | `blogs.store` | `BlogController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:blogs_create</sub> |
| PUT | `/admin/front-web/blogs/update/{id}` | `blogs.update` | `BlogController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:blogs_update</sub> |
| GET | `/admin/front-web/blogs` | `blogs.index` | `BlogController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:blogs_read</sub> |
| GET | `/admin/front-web/faq/create` | `faq.create` | `FaqController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:faq_create</sub> |
| DELETE | `/admin/front-web/faq/delete/{id}` | `faq.delete` | `FaqController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:faq_delete</sub> |
| GET | `/admin/front-web/faq/edit/{id}` | `faq.edit` | `FaqController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:faq_update</sub> |
| POST | `/admin/front-web/faq/store` | `faq.store` | `FaqController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:faq_create</sub> |
| PUT | `/admin/front-web/faq/update/{id}` | `faq.update` | `FaqController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:faq_update</sub> |
| GET | `/admin/front-web/faq` | `faq.index` | `FaqController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:faq_read</sub> |
| GET | `/admin/front-web/pages/edit/{id}` | `pages.edit` | `PageController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:pages_update</sub> |
| PUT | `/admin/front-web/pages/update/{id}` | `pages.update` | `PageController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:pages_update</sub> |
| GET | `/admin/front-web/pages` | `pages.index` | `PageController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:pages_read</sub> |
| GET | `/admin/front-web/partner/create` | `partner.create` | `PartnerController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:partner_create</sub> |
| DELETE | `/admin/front-web/partner/delete/{id}` | `partner.delete` | `PartnerController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:partner_delete</sub> |
| GET | `/admin/front-web/partner/edit/{id}` | `partner.edit` | `PartnerController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:partner_update</sub> |
| POST | `/admin/front-web/partner/store` | `partner.store` | `PartnerController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:partner_create</sub> |
| PUT | `/admin/front-web/partner/update/{id}` | `partner.update` | `PartnerController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:partner_update</sub> |
| GET | `/admin/front-web/partner` | `partner.index` | `PartnerController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:partner_read</sub> |
| GET | `/admin/front-web/section/edit/{id}` | `section.edit` | `SectionController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:section_update</sub> |
| PUT | `/admin/front-web/section/update/{id}` | `section.update` | `SectionController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:section_update</sub> |
| GET | `/admin/front-web/section` | `section.index` | `SectionController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:section_read</sub> |
| GET | `/admin/front-web/service/create` | `service.create` | `ServiceController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:service_create</sub> |
| DELETE | `/admin/front-web/service/delete/{id}` | `service.delete` | `ServiceController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:service_delete</sub> |
| GET | `/admin/front-web/service/edit/{id}` | `service.edit` | `ServiceController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:service_update</sub> |
| POST | `/admin/front-web/service/store` | `service.store` | `ServiceController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:service_create</sub> |
| PUT | `/admin/front-web/service/update/{id}` | `service.update` | `ServiceController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:service_update</sub> |
| GET | `/admin/front-web/service` | `service.index` | `ServiceController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:service_read</sub> |
| GET | `/admin/front-web/social-link/create` | `social.link.create` | `SocialLinkController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:social_link_create</sub> |
| DELETE | `/admin/front-web/social-link/delete/{id}` | `social.link.delete` | `SocialLinkController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:social_link_delete</sub> |
| GET | `/admin/front-web/social-link/edit/{id}` | `social.link.edit` | `SocialLinkController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:social_link_update</sub> |
| POST | `/admin/front-web/social-link/store` | `social.link.store` | `SocialLinkController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:social_link_create</sub> |
| PUT | `/admin/front-web/social-link/update/{id}` | `social.link.update` | `SocialLinkController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:social_link_update</sub> |
| GET | `/admin/front-web/social-link` | `social.link.index` | `SocialLinkController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:social_link_read</sub> |
| GET | `/admin/front-web/why-courier/create` | `why.courier.create` | `WhyCourierController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:why_courier_create</sub> |
| DELETE | `/admin/front-web/why-courier/delete/{id}` | `why.courier.delete` | `WhyCourierController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:why_courier_delete</sub> |
| GET | `/admin/front-web/why-courier/edit/{id}` | `why.courier.edit` | `WhyCourierController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:why_courier_update</sub> |
| POST | `/admin/front-web/why-courier/store` | `why.courier.store` | `WhyCourierController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:why_courier_create</sub> |
| PUT | `/admin/front-web/why-courier/update/{id}` | `why.courier.update` | `WhyCourierController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:why_courier_update</sub> |
| GET | `/admin/front-web/why-courier` | `why.courier.index` | `WhyCourierController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:why_courier_read</sub> |

## `admin/fund-transfer`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/fund-transfer/create` | `fund-transfer.create` | `FundTransferController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_create</sub> |
| DELETE | `/admin/fund-transfer/delete/{id}` | `fund-transfer.delete` | `FundTransferController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_delete</sub> |
| GET | `/admin/fund-transfer/edit/{id}` | `fund-transfer.edit` | `FundTransferController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_update</sub> |
| GET | `/admin/fund-transfer/filter` | `fund.transfer.filter` | `FundTransferController@fundTransferFilter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_read</sub> |
| GET | `/admin/fund-transfer/index` | `fund-transfer.index` | `FundTransferController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_read</sub> |
| GET | `/admin/fund-transfer/search/flter/print` | `fund.transfer.search.filter.print` | `FundTransferController@fundTransferSearchFilterPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_read</sub> |
| GET | `/admin/fund-transfer/specific/search` | `fund.transfer.specific.search` | `FundTransferController@fundTransferSpecificSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_read</sub> |
| POST | `/admin/fund-transfer/store` | `fund-transfer.store` | `FundTransferController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_create</sub> |
| PUT | `/admin/fund-transfer/update/{id}` | `fund-transfer.update` | `FundTransferController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:fund_transfer_update</sub> |
| GET | `/admin/fund-transfer/view/{id}` | `fund-transfer.view` | `FundTransferController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/general-settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/general-settings/index` | `general-settings.index` | `GeneralSettingsController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:general_settings_read</sub> |
| PUT | `/admin/general-settings/update` | `general-settings.update` | `GeneralSettingsController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:general_settings_update</sub> |

## `admin/get-merchant-cod`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/get-merchant-cod` | `get.merchant.cod` | `ParcelController@getMerchantCod` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/global-search`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/global-search` | `global.search` | `GlobalSearchController@search` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/googlemap-settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/googlemap-settings/index` | `googlemap-settings.index` | `GoogleMapSettingsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/admin/googlemap-settings/update` | `googlemap-settings.update` | `GoogleMapSettingsController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/hub`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/hub/cash-received-deliveryman/create` | `cash.received.deliveryman.create` | `ReceivedFromDeliverymanController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:cash_received_from_delivery_man_create</sub> |
| DELETE | `/admin/hub/cash-received-deliveryman/delete/{id}` | `cash.received.deliveryman.delete` | `ReceivedFromDeliverymanController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:cash_received_from_delivery_man_delete</sub> |
| GET | `/admin/hub/cash-received-deliveryman/edit/{id}` | `cash.received.deliveryman.edit` | `ReceivedFromDeliverymanController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:cash_received_from_delivery_man_update</sub> |
| POST | `/admin/hub/cash-received-deliveryman/store` | `cash.received.deliveryman.store` | `ReceivedFromDeliverymanController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:cash_received_from_delivery_man_create</sub> |
| PUT | `/admin/hub/cash-received-deliveryman/update` | `cash.received.deliveryman.update` | `ReceivedFromDeliverymanController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:cash_received_from_delivery_man_update</sub> |
| GET | `/admin/hub/cash-received-deliveryman` | `cash.received.deliveryman.index` | `ReceivedFromDeliverymanController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:cash_received_from_delivery_man_read</sub> |
| DELETE | `/admin/hub/delete/{id}` | `hub.delete` | `HubController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_delete</sub> |
| GET | `/admin/hub/incharge/{hubID}/assigned/{id}` | `hub-incharge.assigned` | `HubInChargeController@assigned` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_assigned</sub> |
| GET | `/admin/hub/incharge/{hubID}/create` | `hub-incharge.create` | `HubInChargeController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_create</sub> |
| DELETE | `/admin/hub/incharge/{hubID}/delete/{id}` | `hub-incharge.destroy` | `HubInChargeController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_delete</sub> |
| GET | `/admin/hub/incharge/{hubID}/edit/{id}` | `hub-incharge.edit` | `HubInChargeController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_update</sub> |
| GET | `/admin/hub/incharge/{hubID}/index` | `hub-incharge.index` | `HubInChargeController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_read</sub> |
| POST | `/admin/hub/incharge/{hubID}/store` | `hub-incharge.store` | `HubInChargeController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_create</sub> |
| PUT | `/admin/hub/incharge/{hubID}/update/{id}` | `hub-incharge.update` | `HubInChargeController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_incharge_update</sub> |
| GET | `/admin/hub/payment-request/create` | `hub-panel.payment-request.create` | `HubPaymentRequestController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_request_create</sub> |
| DELETE | `/admin/hub/payment-request/delete/{id}` | `hub-panel.payment-request.delete` | `HubPaymentRequestController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_request_delete</sub> |
| GET | `/admin/hub/payment-request/edit/{id}` | `hub-panel.payment-request.edit` | `HubPaymentRequestController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_request_update</sub> |
| GET | `/admin/hub/payment-request/index` | `hub-panel.payment-request.index` | `HubPaymentRequestController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_request_read</sub> |
| POST | `/admin/hub/payment-request/store` | `hub-panel.payment-request.store` | `HubPaymentRequestController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_request_create</sub> |
| PUT | `/admin/hub/payment-request/update/{id}` | `hub-panel.payment-request.update` | `HubPaymentRequestController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_request_update</sub> |
| GET | `/admin/hub/view/{id}` | `hub.view` | `HubController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_view</sub> |

## `admin/hub-payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/hub-payment/cancel-process/{id}` | `hub-payment.cancel-process` | `HubPaymentController@cancelProcess` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_process</sub> |
| GET | `/admin/hub-payment/cancel-reject/{id}` | `hub-payment.cancel-reject` | `HubPaymentController@cancelReject` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_reject</sub> |
| GET | `/admin/hub-payment/process/{id}` | `hub-payment.process` | `HubPaymentController@process` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_process</sub> |
| PUT | `/admin/hub-payment/processed` | `hub-payment.processed` | `HubPaymentController@processed` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_process</sub> |
| GET | `/admin/hub-payment/reject/{id}` | `hub-payment.reject` | `HubPaymentController@reject` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_reject</sub> |

## `admin/hubs`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/hubs/create` | `hubs.create` | `HubController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_create</sub> |
| GET | `/admin/hubs/edit/{id}` | `hubs.edit` | `HubController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_update</sub> |
| GET | `/admin/hubs/filter` | `hubs.filter` | `HubController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_read</sub> |
| POST | `/admin/hubs/quick-store` | `hubs.quick-store` | `HubController@quickStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_create</sub> |
| POST | `/admin/hubs/store` | `hubs.store` | `HubController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_create</sub> |
| PUT | `/admin/hubs/update` | `hubs.update` | `HubController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_update</sub> |
| GET | `/admin/hubs` | `hubs.index` | `HubController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_read</sub> |

## `admin/income`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/income/balance-check` | `income.balance.check` | `IncomeController@balanceCheck` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/income/create` | `income.create` | `IncomeController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_create</sub> |
| DELETE | `/admin/income/delete/{id}` | `income.delete` | `IncomeController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_delete</sub> |
| GET | `/admin/income/edit/{id}` | `income.edit` | `IncomeController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_update</sub> |
| GET | `/admin/income/filter` | `income.filter` | `IncomeController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_read</sub> |
| POST | `/admin/income/hub-user-accounts` | `income.hub-user-accounts` | `IncomeController@hubUserAccounts` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/income/search-account/{id}` | `income.search-account` | `IncomeController@searchAccount` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/income/store` | `income.store` | `IncomeController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_create</sub> |
| PUT | `/admin/income/update/{id}` | `income.update` | `IncomeController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_update</sub> |
| POST | `/admin/income/users` | `income.users` | `IncomeController@IncomeUsers` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/income` | `income.index` | `IncomeController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:income_read</sub> |

## `admin/integrations`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/integrations/{platform}/edit` | `integrations.edit` | `IntegrationsController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:integrations_update</sub> |
| PUT | `/admin/integrations/{platform}` | `integrations.update` | `IntegrationsController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:integrations_update</sub> |
| GET | `/admin/integrations` | `integrations.index` | `IntegrationsController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:integrations_read</sub> |

## `admin/liquid-fragile`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/liquid-fragile/edit` | `liquid.fragile.edit` | `LiquidFragileController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:liquid_fragile_update</sub> |
| GET | `/admin/liquid-fragile/index` | `liquid-fragile.index` | `LiquidFragileController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:liquid_fragile_read</sub> |
| POST | `/admin/liquid-fragile/status` | `liquid-fragile.status` | `LiquidFragileController@status` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:liquid_status_change</sub> |
| PUT | `/admin/liquid-fragile/update` | `liquid.fragile.update` | `LiquidFragileController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:liquid_fragile_update</sub> |

## `admin/log-activity-view`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/log-activity-view/{id}` | `log-activity-view` | `ActiveLogController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/logs`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/logs` | `logs.index` | `ActiveLogController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:log_read</sub> |

## `admin/merchant`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/merchant/account` | `merchant-manage.merchant.account` | `MerchantmanagePaymentController@merchantAccount` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/merchant/create` | `merchant.create` | `MerchantController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_create</sub> |
| DELETE | `/admin/merchant/delete/{id}` | `merchant.delete` | `MerchantController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delete</sub> |
| POST | `/admin/merchant/delivery-charge/info` | `merchant.deliveryCharge.deliveryChargeInfo` | `MerchantDeliveryChargeController@deliveryChargeInfo` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/merchant/edit/{id}` | `merchant.edit` | `MerchantController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_update</sub> |
| POST | `/admin/merchant/impersonate/{id}` | `merchant.impersonate` | `MerchantController@impersonate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_update</sub> |
| GET | `/admin/merchant/index` | `merchant.index` | `MerchantController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_read</sub> |
| GET | `/admin/merchant/invoice-generate/{id}` | `merchant.invoice.generate` | `MerchantController@invoiceGenerate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_view</sub> |
| POST | `/admin/merchant/paymentinfo/bank/store` | `merchant.paymentinfo.bank.store` | `MerchantPaymentAccountController@bankStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_create</sub> |
| PUT | `/admin/merchant/paymentinfo/bank/update` | `merchant.payment.bank.update` | `MerchantPaymentAccountController@bankUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_update</sub> |
| DELETE | `/admin/merchant/paymentinfo/delete/{id}` | `merchant.payment.delete` | `MerchantPaymentAccountController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_delete</sub> |
| POST | `/admin/merchant/paymentinfo/mobile/store` | `merchant.paymentinfo.mobile.store` | `MerchantPaymentAccountController@mobileStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_create</sub> |
| PUT | `/admin/merchant/paymentinfo/mobile/update` | `merchant.payment.mobile.update` | `MerchantPaymentAccountController@mobileUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_update</sub> |
| POST | `/admin/merchant/paymentmethod/change` | `merchant.paymentmethod.change` | `MerchantPaymentAccountController@paymentChange` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/merchant/search` | `merchant-manage.merchant-search` | `MerchantmanagePaymentController@merchantSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/merchant/shops/create/{id}` | `merchant.shops.create` | `MerchantShopsController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_shop_create</sub> |
| GET | `/admin/merchant/shops/default/{merchant_id}/{id}` | `merchant.shops.default` | `MerchantShopsController@defaultShop` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/admin/merchant/shops/delete/{id}` | `merchant.shops.delete` | `MerchantShopsController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_shop_delete</sub> |
| GET | `/admin/merchant/shops/edit/{id}` | `merchant.shops.edit` | `MerchantShopsController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_shop_update</sub> |
| POST | `/admin/merchant/shops/store` | `merchant.shops.store` | `MerchantShopsController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_shop_create</sub> |
| PUT | `/admin/merchant/shops/update` | `merchant.shops.update` | `MerchantShopsController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_shop_update</sub> |
| POST | `/admin/merchant/store` | `merchant.store` | `MerchantController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_create</sub> |
| PUT | `/admin/merchant/update/{id}` | `merchant.update` | `MerchantController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_update</sub> |
| GET | `/admin/merchant/view/{id}` | `merchant.view` | `MerchantController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_view</sub> |
| GET | `/admin/merchant/{id}/payment/add` | `merchant.payment.add` | `MerchantPaymentAccountController@paymentAdd` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_create</sub> |
| GET | `/admin/merchant/{id}/payment/index` | `merchant.paymentaccount.index` | `MerchantPaymentAccountController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_read</sub> |
| GET | `/admin/merchant/{id}/shops/index` | `merchant.shops.index` | `MerchantShopsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_shop_read</sub> |
| GET | `/admin/merchant/{merchant_id}/invoice/csv/{invoice_id}` | `merchant.invoice.csv` | `MerchantInvoiceController@InvoiceCSV` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_read</sub> |
| GET | `/admin/merchant/{merchant_id}/invoice/pdf/{invoice_id}` | `merchant.invoice.pdf` | `MerchantInvoiceController@InvoicePdf` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_read</sub> |
| GET | `/admin/merchant/{merchant_id}/invoice/status/update` | `merchant.invoice.status.update` | `MerchantInvoiceController@StatusUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_status_update</sub> |
| GET | `/admin/merchant/{merchant_id}/invoice/{invoice_id}` | `merchant.invoice.details` | `MerchantInvoiceController@InvoiceDetails` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_read</sub> |
| GET | `/admin/merchant/{merchant_id}/invoice` | `merchant.invoice.index` | `MerchantInvoiceController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_read</sub> |
| GET | `/admin/merchant/{merchant}/delivery-charge/create` | `merchant.deliveryCharge.create` | `MerchantDeliveryChargeController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delivery_charge_create</sub> |
| DELETE | `/admin/merchant/{merchant}/delivery-charge/delete/{id}` | `merchant.deliveryCharge.delete` | `MerchantDeliveryChargeController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delivery_charge_delete</sub> |
| GET | `/admin/merchant/{merchant}/delivery-charge/edit/{id}` | `merchant.deliveryCharge.edit` | `MerchantDeliveryChargeController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delivery_charge_update</sub> |
| GET | `/admin/merchant/{merchant}/delivery-charge/index` | `merchant.deliveryCharge.index` | `MerchantDeliveryChargeController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delivery_charge_read</sub> |
| POST | `/admin/merchant/{merchant}/delivery-charge/store` | `merchant.deliveryCharge.store` | `MerchantDeliveryChargeController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delivery_charge_create</sub> |
| PUT | `/admin/merchant/{merchant}/delivery-charge/update/{id}` | `merchant.deliveryCharge.update` | `MerchantDeliveryChargeController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_delivery_charge_update</sub> |
| GET | `/admin/merchant/{mid}/payment/edit/{id}` | `merchant.payment.edit` | `MerchantPaymentAccountController@paymentEdit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_payment_update</sub> |

## `admin/ndr`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/ndr/create/{parcel}` | `ndr.create` | `NdrController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |
| GET | `/admin/ndr/export` | `ndr.export` | `NdrController@export` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |
| PUT | `/admin/ndr/{ndr}/action` | `ndr.action` | `NdrController@updateAction` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |
| PUT | `/admin/ndr/{ndr}/resolve` | `ndr.resolve` | `NdrController@resolve` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |
| GET | `/admin/ndr/{ndr}` | `ndr.show` | `NdrController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |
| GET | `/admin/ndr` | `ndr.index` | `NdrController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |
| POST | `/admin/ndr` | `ndr.store` | `NdrController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:ndr_manage</sub> |

## `admin/news-offer`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/news-offer/create` | `news-offer.create` | `NewsOfferController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:news_offer_create</sub> |
| DELETE | `/admin/news-offer/delete/{id}` | `news-offer.delete` | `NewsOfferController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:news_offer_delete</sub> |
| GET | `/admin/news-offer/edit/{id}` | `news-offer.edit` | `NewsOfferController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:news_offer_update</sub> |
| POST | `/admin/news-offer/store` | `news-offer.store` | `NewsOfferController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:news_offer_create</sub> |
| PUT | `/admin/news-offer/update/{id}` | `news-offer.update` | `NewsOfferController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:news_offer_update</sub> |
| GET | `/admin/news-offer` | `news-offer.index` | `NewsOfferController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:news_offer_read</sub> |

## `admin/notification-settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/notification-settings/index` | `notification-settings.index` | `NotificationSettingsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:notification_settings_read</sub> |
| PUT | `/admin/notification-settings/update` | `notification-settings.update` | `NotificationSettingsController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:notification_settings_update</sub> |

## `admin/online-payment-list`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/online-payment-list` | `online.payment.list` | `PayoutSetupController@onlinePaymentList` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:online_payment_read</sub> |

## `admin/operational-area`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/operational-area/delete/{id}` | `operational_area.delete` | `OperationalAreaController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:operational_area_delete</sub> |

## `admin/operational-areas`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/operational-areas/create` | `operational_areas.create` | `OperationalAreaController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:operational_area_create</sub> |
| GET | `/admin/operational-areas/edit/{id}` | `operational_areas.edit` | `OperationalAreaController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:operational_area_update</sub> |
| POST | `/admin/operational-areas/store` | `operational_areas.store` | `OperationalAreaController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:operational_area_create</sub> |
| PUT | `/admin/operational-areas/update` | `operational_areas.update` | `OperationalAreaController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:operational_area_update</sub> |
| GET | `/admin/operational-areas` | `operational_areas.index` | `OperationalAreaController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:operational_area_read</sub> |

## `admin/packaging`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/packaging/create` | `packaging.create` | `PackagingController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:packaging_create</sub> |
| DELETE | `/admin/packaging/delete/{id}` | `packaging.delete` | `PackagingController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:packaging_delete</sub> |
| GET | `/admin/packaging/edit/{id}` | `packaging.edit` | `PackagingController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:packaging_update</sub> |
| GET | `/admin/packaging/index` | `packaging.index` | `PackagingController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:packaging_read</sub> |
| POST | `/admin/packaging/store` | `packaging.store` | `PackagingController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:packaging_create</sub> |
| PUT | `/admin/packaging/update` | `packaging.update` | `PackagingController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:packaging_update</sub> |
| GET | `/admin/packaging/view/{id}` |  | `PackagingController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/paid`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/paid/invoice` | `paid.invoice.index` | `MerchantInvoiceController@PaidInvoice` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/parcel`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/parcel/assign-return-to-merchant-bulk` | `parcel.assign-return-to-merchant-bulk` | `ParcelController@AssignReturnToMerchantBulk` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| GET | `/admin/parcel/bulkassign/print` | `parcel.parcel-bulkassign-print` | `ParcelController@ParcelBulkAssignPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/cancel-shipment/{id}` | `parcel.cancel-shipment` | `ParcelController@cancelShipment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/clone-store` | `parcel.clone-store` | `ParcelController@duplicateStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/clone/{id}` | `parcel.clone` | `ParcelController@duplicate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/create` | `parcel.create` | `ParcelController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_create</sub> |
| DELETE | `/admin/parcel/delete/{id}` | `parcel.delete` | `ParcelController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_delete</sub> |
| POST | `/admin/parcel/delivered/cancel` | `parcel.delivered-cancel` | `ParcelController@parcelDeliveredCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| GET | `/admin/parcel/delivered/logs/info/{id}` | `parcel.deliveredInfo` | `ParcelController@deliveredInfo` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/delivered` | `parcel.delivered` | `ParcelController@parcelDelivered` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/delivery-category` | `parcel.deliveryCategory.deliveryWeight` | `ParcelController@deliveryWeight` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/delivery-charge` | `parcel.deliveryCharge.get` | `ParcelController@deliveryCharge` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/delivery-man-assign-multiple-parcel` | `parcel.delivery-man-assign-multiple-parcel` | `ParcelController@deliveryManAssignMultipleParcel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/delivery-man-assign` | `parcel.delivery-man-assign` | `ParcelController@deliverymanAssign` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/delivery-man/assign/cancel` | `parcel.delivery-man-assign-cancel` | `ParcelController@deliverymanAssignCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/delivery-re-scheule/cancel` | `parcel.delivery-re-schedule-cancel` | `ParcelController@deliveryReScheduleCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/delivery-reschedule` | `parcel.delivery.reschedule` | `ParcelController@deliveryReschedule` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| GET | `/admin/parcel/deliveryMan/show` | `parcel.parcelDeliveryMan` | `ParcelController@parcelDeliveryMan` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/deliveryman/search` | `parcel.deliveryman.search` | `ParcelController@deliverymanSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/details/{id}/3pl` | `parcel.3pl_details` | `ParcelController@ThirdPartyLogistics` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/details/{id}` | `parcel.details` | `ParcelController@details` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/edit/{id}` | `parcel.edit` | `ParcelController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_update</sub> |
| GET | `/admin/parcel/export` | `parcel.parcel-export` | `ParcelController@exportShipments` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/file-export` | `parcel.file-export` | `ParcelController@parcelExport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/file-import` | `parcel.file-import` | `ParcelController@parcelImport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_create</sub> |
| GET | `/admin/parcel/filter` | `parcel.filter` | `ParcelController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/get-areas` | `parcel.getAreas` | `ParcelController@getAreasByCity` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/hub` | `parcel.hub.get` | `ParcelController@getHub` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/import-parcel` | `parcel.parcel-import` | `ParcelController@parcelImportExport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_create</sub> |
| POST | `/admin/parcel/import/merchant` | `parcel.import.merchant.get` | `ParcelController@getImportMerchant` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/index` | `parcel.index` | `ParcelController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| POST | `/admin/parcel/inline-update` | `parcel.inline.update` | `ParcelController@inlineupdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/logs/{id}` | `parcel.logs` | `ParcelController@logs` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/merchant-products` | `parcel.merchantProducts` | `ParcelController@merchantProducts` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_create</sub> |
| POST | `/admin/parcel/merchant/shops` | `parcel.merchant.shops` | `ParcelController@merchantShops` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/merchant` | `parcel.merchant.get` | `ParcelController@getMerchant` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/multiple/print/label` | `parcel.multiple.print-label` | `ParcelController@parcelMultiplePrintLabel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/partial-delivered/cancel` | `parcel.partial-delivered-cancel` | `ParcelController@parcelPartialDeliveredCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/partial-delivered` | `parcel.partial-delivered` | `ParcelController@parcelPartialDelivered` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/pickup-man/assigned/cancel` | `parcel.pickup.man-assigned-cancel` | `ParcelController@PickupManAssignedCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/pickup-man/assigned` | `parcel.pickup.man-assigned` | `ParcelController@PickupManAssigned` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/pickup-reschedule/cancel` | `parcel.pickup.re-schedule-cancel` | `ParcelController@PickupReScheduleCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/pickup/re-schedule` | `parcel.pickup.re.schedule` | `ParcelController@PickupReSchedule` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/pickup/received/cancel` | `parcel.pickup.man-received-cancel` | `ParcelController@receivedBypickupmanCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/pickup/received` | `parcel.received.by.pickup` | `ParcelController@receivedBypickupman` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| GET | `/admin/parcel/print/{id}/label` | `parcel.print-label` | `ParcelController@parcelPrintLabel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/print/{id}` | `parcel.print` | `ParcelController@parcelPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| POST | `/admin/parcel/priority/update` | `parcel.priority.status` | `ParcelController@priorityUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/received-by-hub/cancel` | `parcel.received-by-hub-cancel` | `ParcelController@receivedByHubCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/received-by-hub` | `parcel.received-by.hub` | `ParcelController@receivedByHub` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/received-by-multiple-hub` | `parcel.received-by-mulbiple-hub` | `ParcelController@parcelReceivedByMultipleHub` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/received-warehouse-hub-selected` | `parcel.received.warehouse.hub.select` | `ParcelController@warehouseHubSelected` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/received-warehouse/cancel` | `parcel.received-warehouse-cancel` | `ParcelController@receivedWarehouseCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/received-warehouse` | `parcel.received.warehouse` | `ParcelController@receivedWarehouse` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/recived-by-hub/search` | `parcel.received-by-hub-search` | `ParcelController@parcelRecivedByHubSearch` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/return-assign-re-schedule-to-merchant/cancel` | `parcel.return-assign-re-schedule-to-merchant-cancel` | `ParcelController@returnAssignToMerchantRescheduleCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-assign-to-merchant-reschedule` | `parcel.return-assign-to-merchant.reschedule` | `ParcelController@returnAssignToMerchantReschedule` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-assign-to-merchant/cancel` | `parcel.return-assign-to-merchant-cancel` | `ParcelController@returnAssignToMerchantCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-assign-to-merchant` | `parcel.return-assign-to-merchant` | `ParcelController@returnAssignToMerchant` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-received-by-merchant/cancel` | `parcel.return-received-by-merchant-cancel` | `ParcelController@returnReceivedByMerchantCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-received-by-merchant` | `parcel.return-received-by-merchant` | `ParcelController@returnReceivedByMerchant` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-to-qourier-cancel` | `parcel.return-to-courier-cancel` | `ParcelController@returntoQourierCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/return-to-qourier` | `parcel.return-to-qourier` | `ParcelController@returntoQourier` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/search-delivery-man-assing-multiple-parcel` | `parcel.search-delivery-man-assing-multiple-parcel` | `ParcelController@searchDeliveryManAssingMultipleParcel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/search-expense` | `parcel.search-expense` | `ParcelController@searchExpense` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/search-income` | `parcel.search-income` | `ParcelController@searchIncome` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcel/search` | `parcel.search` | `ParcelController@search` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/specific/search` | `parcel.specific.search` | `ParcelController@ParcelSearchs` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcel/status-update/{id}/{status_id}` | `parcel.status-update` | `ParcelController@statusUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/store` | `parcel.store` | `ParcelController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_create</sub> |
| GET | `/admin/parcel/tracking-json/{id}` | `parcel.tracking_json` | `ParcelController@trackingJson` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| GET | `/admin/parcel/tracking-offcanvas/{id}` | `parcel.tracking_offcanvas` | `ParcelController@trackingOffcanvas` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_read</sub> |
| POST | `/admin/parcel/transfer-hub` | `parcel.transferHub` | `ParcelController@transferHub` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/transfer-to-hub-multiple-parcel` | `parcel.transfer-to-hub-multiple-parcel` | `ParcelController@transferToHubMultipleParcel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/transfer-to-hub/cancel` | `parcel.transfer-to-hub-cancel` | `ParcelController@transfertoHubCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| POST | `/admin/parcel/transfer-to-hub` | `parcel.transfer-to-hub` | `ParcelController@transfertohub` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_update</sub> |
| PUT | `/admin/parcel/update/{id}` | `parcel.update` | `ParcelController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_update</sub> |
| POST | `/admin/parcel/{parcel}/add-ndr` | `parcel.add_ndr` | `ParcelController@addNdr` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/parcel-reports-print-page`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/parcel-reports-print-page/{array}` | `parcel.reports.print.page` | `ReportsController@parcelReportsPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_reports</sub> |

## `admin/parcel-wise-profit-print-page`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/parcel-wise-profit-print-page/{array}` | `parcel.wise.profit.print.page` | `ReportsController@parcelWiseProfitPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_wise_profit</sub> |

## `admin/parcels`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/parcels/bulk/apply` | `parcel.bulk_action_apply` | `ParcelBulkActionController@apply` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/parcels/bulk/check` | `parcel.check_bulk_action` | `ParcelBulkActionController@check` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/parcels/{parcel}/details` | `admin.parcels.details` | `ParcelController@details` | <sub>web</sub> |

## `admin/payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/payment/cancel-process/{id}` | `merchantmanage.payment.cancel-process` | `MerchantmanagePaymentController@cancelProcess` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_process</sub> |
| GET | `/admin/payment/cancel-reject/{id}` | `merchantmanage.payment.cancel-reject` | `MerchantmanagePaymentController@cancelReject` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_reject</sub> |
| GET | `/admin/payment/create` | `merchant-manage.payment.create` | `MerchantmanagePaymentController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_create</sub> |
| DELETE | `/admin/payment/delete/{id}` | `merchantmanage.payment.delete` | `MerchantmanagePaymentController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_delete</sub> |
| GET | `/admin/payment/edit/{id}` | `merchatmanage.payment.edit` | `MerchantmanagePaymentController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_update</sub> |
| GET | `/admin/payment/index` | `merchant.manage.payment.index` | `MerchantmanagePaymentController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_read</sub> |
| GET | `/admin/payment/merchant/filter` | `merchantmanage.payment.filter` | `MerchantmanagePaymentController@merchantpaymentFilter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payment/process/{id}` | `merchantmanage.payment.process` | `MerchantmanagePaymentController@process` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_process</sub> |
| PUT | `/admin/payment/processed` | `merchantmanage.payment.processed` | `MerchantmanagePaymentController@processed` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_process</sub> |
| GET | `/admin/payment/reject/{id}` | `merchantmanage.payment.reject` | `MerchantmanagePaymentController@reject` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_reject</sub> |
| POST | `/admin/payment/store` | `merchantmanage.payment.store` | `MerchantmanagePaymentController@paymentStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_create</sub> |
| PUT | `/admin/payment/update` | `merchantmanage.payment.update` | `MerchantmanagePaymentController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_update</sub> |

## `admin/payment_get_cod`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/payment_get_cod` | `merchant-manage.payment.payment_get_cod` | `MerchantmanagePaymentController@payment_get_cod` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:payment_create</sub> |

## `admin/payout`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/payout/aamarpay-fail` | `payout.aamarpay.payment.fail` | `AdminAamarpayController@fail` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/aamarpay-payment` | `payout.aamarpay.payment` | `AdminAamarpayController@payment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/aamarpay-success` | `payout.aamarpay.payment.success` | `AdminAamarpayController@success` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/aamarpay` | `payout.aamarpay.index` | `AdminAamarpayController@aamarpayIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/bkash/execute` | `payout.bkash.execute` | `AdminBkashController@bkashExecute` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/bkash/redirect` | `payout.bkash.redirect` | `AdminBkashController@bkashRedirect` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/cancel` | `payout.` | `AdminSslCommerzController@cancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/fail` | `payout.` | `AdminSslCommerzController@fail` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/ipn` | `payout.` | `AdminSslCommerzController@ipn` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/merchant/payout` | `payout.merchant.payout` | `PayoutController@merchantPayout` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/online-payment/bkash` | `payout.bkash.index` | `AdminBkashController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/pay-via-ajax` | `payout.pay.via.ajax` | `AdminSslCommerzController@payViaAjax` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/payment-cancelled` | `payout.` | `AdminSkrillController@PaymentCancelled` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/payment-completed` | `payout.skrill.payment.completed` | `AdminSkrillController@paymentCompleted` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/paypal-index` | `payout.paypal.index` | `PayoutController@paypalIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/paypal-payment` | `payout.paypal` | `PayoutController@paypalpayment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/razorpay/payment` | `payout.merchant.razorpay.post` | `PayoutController@razorpayPost` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/razorpay` | `payout.merchant.razorpay` | `PayoutController@razorpay` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/skrill-make-payment` | `payout.skrill.make.payment` | `AdminSkrillController@makePayment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/skrill` | `payout.skrill.index` | `AdminSkrillController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/sslcommerz` | `payout.sslcommerz.index` | `AdminSslCommerzController@sslcommerzIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/stripe/post` | `payout.merchant.stripe.post` | `PayoutController@stripePost` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout/stripe` | `payout.merchant.stripe` | `PayoutController@stripe` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/payout/success` | `payout.` | `AdminSslCommerzController@success` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/payout` | `payout.index` | `PayoutController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/pickup-request`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/pickup-request/express` | `pickup.request.express` | `PickupRequestController@express` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:pickup_request_express</sub> |
| GET | `/admin/pickup-request/regular` | `pickup.request.regular` | `PickupRequestController@regular` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:pickup_request_regular</sub> |

## `admin/profile`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/profile/change-password/{id}` | `password.change` | `ProfileController@changePassword` | <sub>web|XSS|IsInstalled|auth</sub> |
| PUT | `/admin/profile/update-password/{id}` | `profile.password.update` | `ProfileController@updatePassword` | <sub>web|XSS|IsInstalled|auth</sub> |
| GET | `/admin/profile/update/{id}` | `profile.edit` | `ProfileController@create` | <sub>web|XSS|IsInstalled|auth</sub> |
| PUT | `/admin/profile/update/{id}` | `profile.update` | `ProfileController@update` | <sub>web|XSS|IsInstalled|auth</sub> |
| GET | `/admin/profile/{id}` | `profile.index` | `ProfileController@view` | <sub>web|XSS|IsInstalled|auth</sub> |

## `admin/push-notification`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/push-notification/create` | `push-notification.create` | `PushNotificationController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:push_notification_create</sub> |
| DELETE | `/admin/push-notification/delete/{id}` | `push-notification.delete` | `PushNotificationController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:push_notification_delete</sub> |
| POST | `/admin/push-notification/store` | `push-notification.store` | `PushNotificationController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:push_notification_create</sub> |
| POST | `/admin/push-notification/users` | `push-notification.users` | `PushNotificationController@Users` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/push-notification` | `push-notification.index` | `PushNotificationController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:push_notification_read</sub> |

## `admin/reports`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/reports/merchant-hub-deliveryman` | `merchant.hub.deliveryman.reports` | `ReportsController@MerchantHubDeliverymanReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_hub_deliveryman</sub> |
| GET | `/admin/reports/merchnat-hub-delivery-reports-print-page` | `merchant.hub.deliveryman.reports.print-page` | `ReportsController@MerchantHubDeliveryReportsPrintPage` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_hub_deliveryman</sub> |
| GET | `/admin/reports/mhd-pdf` | `merchant.hub.deliveryman.pdf` | `ReportsController@mhdPDF` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/admin/reports/mhd-reports` | `reports.mhd.reports` | `ReportsController@MHDreports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:merchant_hub_deliveryman</sub> |
| GET | `/admin/reports/parcel-filter-reports` | `parcel.filter.reports` | `ReportsController@parcelSReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_reports</sub> |
| GET | `/admin/reports/parcel-filter-total-summery` | `parcel.filter.total.summery` | `TotalSummeryReportController@parcelTotalSummeryFilter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_total_summery</sub> |
| GET | `/admin/reports/parcel-finance-reports` | `parcel.finance.reports` | `ReportsController@parcelFinanceReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_total_summery</sub> |
| GET | `/admin/reports/parcel-reports` | `parcel.reports` | `ReportsController@parcelReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_status_reports</sub> |
| GET | `/admin/reports/parcel-total-summery` | `parcel.total.summery.index` | `TotalSummeryReportController@parcelTotalSummery` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_total_summery</sub> |
| GET | `/admin/reports/parcel-wise-profit-reports` | `parcel.wise.profit.reports` | `ReportsController@ParcelWiseProfitReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_wise_profit</sub> |
| GET | `/admin/reports/parcel-wise-reports` | `parcel.wise.profit.index` | `ReportsController@parcelWiseReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_wise_profit</sub> |
| GET | `/admin/reports/reports-salary-reports` | `reports.salary.reports` | `ReportsController@ReportssalaryReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_reports</sub> |
| GET | `/admin/reports/salary-report-print` | `salary.reports.print.page` | `ReportsController@SalaryReportPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_reports</sub> |
| GET | `/admin/reports/salary-reports` | `salary.reports` | `ReportsController@salaryReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_reports</sub> |

## `admin/reports-tracking-parcels`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/reports-tracking-parcels` | `reports-tracking-parcels` | `ReportsController@reportsTrackingParcels` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:parcel_wise_profit</sub> |

## `admin/request`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/request/hub/payment/create` | `hub.hub-payment.create` | `HubPaymentController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_create</sub> |
| DELETE | `/admin/request/hub/payment/delete/{id}` | `hub.hub-payment.delete` | `HubPaymentController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_delete</sub> |
| GET | `/admin/request/hub/payment/edit/{id}` | `hub.hub-payment.edit` | `HubPaymentController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_update</sub> |
| GET | `/admin/request/hub/payment/index` | `hub.hub-payment.index` | `HubPaymentController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_read</sub> |
| POST | `/admin/request/hub/payment/store` | `hub.hub-payment.store` | `HubPaymentController@paymentStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_create</sub> |
| PUT | `/admin/request/hub/payment/update/{id}` | `hub.hub-payment.update` | `HubPaymentController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:hub_payment_update</sub> |

## `admin/role`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/role/delete/{id}` | `role.delete` | `RoleController@destroy` | <sub>web|XSS|IsInstalled|auth|hasPermission:role_delete</sub> |

## `admin/roles`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/roles/create` | `roles.create` | `RoleController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:role_create</sub> |
| GET | `/admin/roles/edit/{id}` | `roles.edit` | `RoleController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:role_update</sub> |
| POST | `/admin/roles/store` | `roles.store` | `RoleController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:role_create</sub> |
| PUT | `/admin/roles/update` | `roles.update` | `RoleController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:role_update</sub> |
| GET | `/admin/roles` | `roles.index` | `RoleController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:role_read</sub> |

## `admin/salary`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/salary/delete/{id}` | `salary.delete` | `SalaryController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_delete</sub> |
| GET | `/admin/salary/pay-slip/{id}` | `salary.pay.slip` | `SalaryController@paySlip` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_read</sub> |
| POST | `/admin/salary/salary-auto-generate` | `salary.auto.generate` | `SalaryGenerateController@salaryAutoGenerate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_create</sub> |
| GET | `/admin/salary/salary-generate/create` | `salary.generate.create` | `SalaryGenerateController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_create</sub> |
| DELETE | `/admin/salary/salary-generate/delete/{id}` | `salary-generate.delete` | `SalaryGenerateController@salaryGenerateDelete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_delete</sub> |
| GET | `/admin/salary/salary-generate/edit/{id}` | `salary.generate.edit` | `SalaryGenerateController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_update</sub> |
| POST | `/admin/salary/salary-generate/store` | `salary.generate.store` | `SalaryGenerateController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_create</sub> |
| PUT | `/admin/salary/salary-generate/update` | `salary.generate.update` | `SalaryGenerateController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_update</sub> |
| GET | `/admin/salary/salary-generate` | `salary.generate.index` | `SalaryGenerateController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_generate_read</sub> |
| POST | `/admin/salary/search-account` | `salary.account.search` | `SalaryController@salaryGet` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/salary/store` | `salary.store` | `SalaryController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_create</sub> |
| PUT | `/admin/salary/update` | `salary.update` | `SalaryController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_update</sub> |
| POST | `/admin/salary/users` | `salary.users` | `SalaryController@Users` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/salarys`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/salarys/create` | `salary.create` | `SalaryController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_create</sub> |
| GET | `/admin/salarys/edit/{id}` | `salary.edit` | `SalaryController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_update</sub> |
| GET | `/admin/salarys/filter` | `salary.filter` | `SalaryController@salaryFilter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_read</sub> |
| GET | `/admin/salarys` | `salary.index` | `SalaryController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:salary_read</sub> |

## `admin/settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/settings/invoice-generate-menually/index` | `invoice.generate.menually.index` | `MerchantInvoiceController@InvoiceGenerateMenuallyIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_generate_menually</sub> |
| GET | `/admin/settings/invoice-generate-menually` | `invoice.generate.menually` | `MerchantInvoiceController@InvoiceGenerateMenually` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:invoice_generate_menually</sub> |
| PUT | `/admin/settings/label-templates/merchant/{id}` | `label-templates.update-merchant` | `LabelTemplateController@updateMerchantOverride` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:label_template_manage</sub> |
| GET | `/admin/settings/label-templates/preview/{template}` | `label-templates.preview` | `LabelTemplateController@preview` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:label_template_manage</sub> |
| GET | `/admin/settings/label-templates` | `label-templates.index` | `LabelTemplateController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:label_template_manage</sub> |
| PUT | `/admin/settings/label-templates` | `label-templates.update-default` | `LabelTemplateController@updateDefault` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:label_template_manage</sub> |
| PUT | `/admin/settings/pay-out/setup/update/{paymentmethod}` | `payout.setup.settings.update` | `PayoutSetupController@PayoutSetupUpdate` | <sub>web|XSS|IsInstalled|auth|hasPermission:payout_setup_settings_update</sub> |
| GET | `/admin/settings/pay-out/setup` | `payout.setup.settings.index` | `PayoutSetupController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:payout_setup_settings_read</sub> |

## `admin/sms-send-settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/sms-send-settings/index` | `sms-send-settings.index` | `SmsSendSettingsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:sms_send_settings_read</sub> |
| POST | `/admin/sms-send-settings/status` | `sms-send-settings.status` | `SmsSendSettingsController@status` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:sms_send_settings_status_change</sub> |

## `admin/sms-settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/sms-settings/create` | `sms-settings.create` | `SmsSettingsController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:sms_settings_create</sub> |
| DELETE | `/admin/sms-settings/delete/{id}` | `sms-settings.delete` | `SmsSettingsController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:sms_settings_delete</sub> |
| GET | `/admin/sms-settings/edit/{id}` | `sms-settings.edit` | `SmsSettingsController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:sms_settings_update</sub> |
| GET | `/admin/sms-settings/index` | `sms-settings.index` | `SmsSettingsController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:sms_settings_read</sub> |
| POST | `/admin/sms-settings/status` | `sms-settings.status` | `SmsSettingsController@status` | <sub>web|XSS|IsInstalled|auth|hasPermission:sms_settings_status_change</sub> |
| POST | `/admin/sms-settings/store` | `sms-settings.store` | `SmsSettingsController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:sms_settings_create</sub> |
| PUT | `/admin/sms-settings/update/{id}` | `sms-settings.update` | `SmsSettingsController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:sms_settings_update</sub> |

## `admin/social-login-settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| PUT | `/admin/social-login-settings/update/{social}` | `social.login.settings.update` | `SocialLoginController@socialLoginSettingsUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:social_login_settings_update</sub> |
| GET | `/admin/social-login-settings` | `social.login.settings.index` | `SocialLoginController@socialLoginSettingsIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:social_login_settings_read</sub> |

## `admin/subscribe`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/subscribe` | `subscribe.index` | `SalaryGenerateController@subscribe` | <sub>web|XSS|IsInstalled|auth</sub> |

## `admin/subscription`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/subscription/history` | `admin.subscription.history` | `PlanController@subscriptionHistory` | <sub>web|XSS|IsInstalled|auth</sub> |

## `admin/supplier-companies`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/supplier-companies/create` | `supplier_companies.create` | `SupplierCompanyController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:supplier_company_create</sub> |
| GET | `/admin/supplier-companies/edit/{id}` | `supplier_companies.edit` | `SupplierCompanyController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:supplier_company_update</sub> |
| POST | `/admin/supplier-companies/store` | `supplier_companies.store` | `SupplierCompanyController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:supplier_company_create</sub> |
| PUT | `/admin/supplier-companies/update` | `supplier_companies.update` | `SupplierCompanyController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:supplier_company_update</sub> |
| GET | `/admin/supplier-companies` | `supplier_companies.index` | `SupplierCompanyController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:supplier_company_read</sub> |

## `admin/supplier-company`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/supplier-company/delete/{id}` | `supplier_company.delete` | `SupplierCompanyController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:supplier_company_delete</sub> |

## `admin/support`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/support/create` | `support.add` | `SupportController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_create</sub> |
| DELETE | `/admin/support/delete/{id}` | `support.delete` | `SupportController@destroy` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_delete</sub> |
| GET | `/admin/support/edit/{id}` | `support.edit` | `SupportController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_update</sub> |
| GET | `/admin/support/index` | `support.index` | `SupportController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_read</sub> |
| POST | `/admin/support/reply` | `support.reply` | `SupportController@supportReply` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_reply</sub> |
| GET | `/admin/support/status-update/{id}` | `support.status.update` | `SupportController@statusUpdate` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_status_update</sub> |
| POST | `/admin/support/store` | `support.store` | `SupportController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_create</sub> |
| PUT | `/admin/support/update` | `support.update` | `SupportController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:support_update</sub> |
| GET | `/admin/support/view/{id}` | `support.view` | `SupportController@view` | <sub>web|XSS|IsInstalled|auth</sub> |

## `admin/tms`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/tms/driver/{driver_id}/export` | `tms.runsheet` | `TMSController@print_runsheet` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_read</sub> |
| GET | `/admin/tms/runsheet/bulk` | `tms.runsheet.bulk` | `TMSController@print_runsheet_bulk` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_read</sub> |
| GET | `/admin/tms` | `tms` | `TMSController@tms` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:delivery_man_read</sub> |

## `admin/todo`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/todo/completed` | `todo.completed` | `TodoController@todoComplete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:todo_update</sub> |
| DELETE | `/admin/todo/delete/{id}` | `todo.delete` | `TodoController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:todo_delete</sub> |
| POST | `/admin/todo/momal` | `todo.modal` | `TodoController@todoModal` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/admin/todo/processing` | `todo.processing` | `TodoController@todoProcessing` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:todo_update</sub> |
| POST | `/admin/todo/todo_add` | `todo.store` | `TodoController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:todo_create</sub> |
| GET | `/admin/todo/todo_list` | `todo.index` | `TodoController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:todo_read</sub> |
| PUT | `/admin/todo/update` | `todo.update` | `TodoController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:todo_update</sub> |

## `admin/transertohub-selected-hub`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/transertohub-selected-hub` | `transertohub.selected.hub` | `ParcelController@transfertohubSelectedHub` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `admin/user`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/admin/user/delete/{id}` | `user.delete` | `UserController@destroy` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_delete</sub> |

## `admin/users`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/users/create` | `users.create` | `UserController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_create</sub> |
| GET | `/admin/users/edit/{id}` | `users.edit` | `UserController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_update</sub> |
| GET | `/admin/users/filter` | `users.filter` | `UserController@filter` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_read</sub> |
| PUT | `/admin/users/permissions/update` | `users.permissions.update` | `UserController@permissionsUpdate` | <sub>web|XSS|IsInstalled|auth|hasPermission:permission_update</sub> |
| GET | `/admin/users/permissions/{id}` | `users.permission` | `UserController@permission` | <sub>web|XSS|IsInstalled|auth|hasPermission:permission_update</sub> |
| POST | `/admin/users/store` | `users.store` | `UserController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_create</sub> |
| PUT | `/admin/users/update` | `users.update` | `UserController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_update</sub> |
| GET | `/admin/users` | `users.index` | `UserController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:user_read</sub> |

## `admin/wallet-request`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| PUT | `/admin/wallet-request/approve/{id}` | `wallet.request.approve` | `WalletController@approve` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wallet_request_approve</sub> |
| DELETE | `/admin/wallet-request/delete/{id}` | `wallet.request.delete` | `WalletController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wallet_request_delete</sub> |
| POST | `/admin/wallet-request/recharge` | `wallet.request.recharge` | `WalletController@adminstore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wallet_request_create</sub> |
| PUT | `/admin/wallet-request/reject/{id}` | `wallet.request.reject` | `WalletController@reject` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wallet_request_reject</sub> |
| GET | `/admin/wallet-request` | `wallet.request.index` | `WalletController@requestIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wallet_request_read</sub> |

## `admin/wms`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/wms/adjustments/create` | `wms.adjustments.create` | `WmsAdjustmentController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/adjustments/lookup-qty` | `wms.adjustments.lookup-qty` | `WmsAdjustmentController@lookupQty` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/adjustments/{adjustment}` | `wms.adjustments.show` | `WmsAdjustmentController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/adjustments/{id}/approve` | `wms.adjustments.approve` | `WmsAdjustmentController@approve` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/adjustments/{id}/reject` | `wms.adjustments.reject` | `WmsAdjustmentController@reject` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/adjustments` | `wms.adjustments.index` | `WmsAdjustmentController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/adjustments` | `wms.adjustments.store` | `WmsAdjustmentController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/cycle-counts/create` | `wms.cycle-counts.create` | `WmsCycleCountController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/cycle-counts/{cycle_count}/edit` | `wms.cycle-counts.edit` | `WmsCycleCountController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| DELETE | `/admin/wms/cycle-counts/{cycle_count}` | `wms.cycle-counts.destroy` | `WmsCycleCountController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/cycle-counts/{cycle_count}` | `wms.cycle-counts.show` | `WmsCycleCountController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT,PATCH | `/admin/wms/cycle-counts/{cycle_count}` | `wms.cycle-counts.update` | `WmsCycleCountController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/cycle-counts` | `wms.cycle-counts.index` | `WmsCycleCountController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/cycle-counts` | `wms.cycle-counts.store` | `WmsCycleCountController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/damage/create` | `wms.damage.create` | `WmsDamageController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/damage/{damage}` | `wms.damage.show` | `WmsDamageController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/damage` | `wms.damage.index` | `WmsDamageController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/damage` | `wms.damage.store` | `WmsDamageController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/dashboard` | `wms.dashboard.alias` | `WmsDashboardController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/fulfillment/{id}/dispatch` | `wms.fulfillment.dispatch` | `WmsFulfillmentController@dispatchOrder` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/fulfillment/{id}/pack` | `wms.fulfillment.pack` | `WmsFulfillmentController@confirmPack` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/fulfillment/{id}/pick` | `wms.fulfillment.pick` | `WmsFulfillmentController@confirmPick` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/fulfillment/{id}/picking` | `wms.fulfillment.picking` | `WmsFulfillmentController@picking` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/fulfillment/{id}` | `wms.fulfillment.show` | `WmsFulfillmentController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/fulfillment` | `wms.fulfillment.index` | `WmsFulfillmentController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/grn/create` | `wms.grn.create` | `WmsGrnController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/grn/{grn}/complete` | `wms.grn.complete` | `WmsGrnController@complete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/grn/{grn}/edit` | `wms.grn.edit` | `WmsGrnController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| DELETE | `/admin/wms/grn/{grn}` | `wms.grn.destroy` | `WmsGrnController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/grn/{grn}` | `wms.grn.show` | `WmsGrnController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT,PATCH | `/admin/wms/grn/{grn}` | `wms.grn.update` | `WmsGrnController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/grn` | `wms.grn.index` | `WmsGrnController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/grn` | `wms.grn.store` | `WmsGrnController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/locations/create` | `wms.locations.create` | `WmsLocationController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/locations/map` | `wms.locations.map` | `WmsLocationController@map` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/locations/{location}/edit` | `wms.locations.edit` | `WmsLocationController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| DELETE | `/admin/wms/locations/{location}` | `wms.locations.destroy` | `WmsLocationController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/locations/{location}` | `wms.locations.show` | `WmsLocationController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT,PATCH | `/admin/wms/locations/{location}` | `wms.locations.update` | `WmsLocationController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/locations` | `wms.locations.index` | `WmsLocationController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/locations` | `wms.locations.store` | `WmsLocationController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/outbound/create` | `wms.outbound.create` | `WmsOutboundController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT | `/admin/wms/outbound/{outbound}/complete` | `wms.outbound.complete` | `WmsOutboundController@complete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/outbound/{outbound}` | `wms.outbound.show` | `WmsOutboundController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/outbound` | `wms.outbound.index` | `WmsOutboundController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/outbound` | `wms.outbound.store` | `WmsOutboundController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/products/create` | `wms.products.create` | `WmsProductController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/products/{product}/barcode` | `wms.products.barcode` | `WmsProductController@barcode` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/products/{product}/edit` | `wms.products.edit` | `WmsProductController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| DELETE | `/admin/wms/products/{product}` | `wms.products.destroy` | `WmsProductController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/products/{product}` | `wms.products.show` | `WmsProductController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| PUT,PATCH | `/admin/wms/products/{product}` | `wms.products.update` | `WmsProductController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/products` | `wms.products.index` | `WmsProductController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| POST | `/admin/wms/products` | `wms.products.store` | `WmsProductController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/stock/export` | `wms.stock.export` | `WmsStockController@export` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms/stock` | `wms.stock.index` | `WmsStockController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |
| GET | `/admin/wms` | `wms.dashboard` | `WmsDashboardController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:wms_manage</sub> |

## `admin/zatca`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/zatca/invoices/{id}/pdf` | `zatca.invoices.pdf` | `InvoiceController@pdf` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |
| GET | `/admin/zatca/invoices/{id}/qr` | `zatca.invoices.qr` | `InvoiceController@qr` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |
| POST | `/admin/zatca/invoices/{id}/regenerate` | `zatca.invoices.regenerate` | `InvoiceController@regenerate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |
| GET | `/admin/zatca/invoices/{id}` | `zatca.invoices.show` | `InvoiceController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |
| GET | `/admin/zatca/invoices` | `zatca.invoices.index` | `InvoiceController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |
| GET | `/admin/zatca/settings` | `zatca.settings.index` | `SettingsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |
| PUT | `/admin/zatca/settings` | `zatca.settings.update` | `SettingsController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:zatca_manage</sub> |

## `api/delivery`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/api/delivery/agent-create` |  | `DeliveryPandaController@createAgentShipment` | <sub>api</sub> |
| POST | `/api/delivery/create` |  | `DeliveryPandaController@createShipment` | <sub>api</sub> |
| POST | `/api/delivery/customer-to-customer` |  | `DeliveryPandaController@createCustomerToCustomerShipment` | <sub>api</sub> |
| GET | `/api/delivery/test` |  | `DeliveryPandaController@test` | <sub>api</sub> |
| POST | `/api/delivery/track` |  | `DeliveryPandaController@trackShipment` | <sub>api</sub> |

## `api/olivery`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/olivery/webhook` |  | `WebhookController@webhook` | <sub>api</sub> |
| POST | `/api/olivery/webhook` |  | `WebhookController@webhook` | <sub>api</sub> |

## `api/panda`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/panda/schudule_tracking` |  | `DeliveryPandaController@schudule_tracking` | <sub>api</sub> |
| GET | `/api/panda/schudule_tracking_temp` |  | `DeliveryPandaController@schudule_tracking_temp` | <sub>api</sub> |

## `api/user`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/user` |  | `Closure` | <sub>api|auth:sanctum</sub> |

## `api/v10`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/api/v10/account-transaction/filter` |  | `AccountTransactionController@filter` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/account-transaction/index` |  | `AccountTransactionController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/admin/dashboard/timeseries` |  | `AdminDashboardController@timeseries` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/dashboard` |  | `AdminDashboardController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/drivers/{id}` |  | `AdminDriverController@show` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/drivers` |  | `AdminDriverController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| DELETE | `/api/v10/admin/fraud/{id}` |  | `AdminFraudController@destroy` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/fraud` |  | `AdminFraudController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/fraud` |  | `AdminFraudController@store` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/hubs/{id}` |  | `AdminHubController@show` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/hubs` |  | `AdminHubController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/login` |  | `AdminAuthController@login` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/admin/logout` |  | `AdminAuthController@logout` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/merchants/{id}/toggle-active` |  | `AdminMerchantController@toggleActive` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/merchants/{id}` |  | `AdminMerchantController@show` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/merchants` |  | `AdminMerchantController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/parcels/{id}/assign-driver` |  | `AdminParcelController@assignDriver` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/parcels/{id}/logs` |  | `AdminParcelController@logs` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/parcels/{id}/status` |  | `AdminParcelController@forceStatus` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/parcels/{id}` |  | `AdminParcelController@show` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/parcels` |  | `AdminParcelController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/payment-requests/{id}/approve` |  | `AdminPaymentRequestController@approve` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/payment-requests/{id}/reject` |  | `AdminPaymentRequestController@reject` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/payment-requests` |  | `AdminPaymentRequestController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/profile` |  | `AdminAuthController@profile` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/support/{id}/close` |  | `AdminSupportController@close` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| POST | `/api/v10/admin/support/{id}/reply` |  | `AdminSupportController@reply` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/support/{id}` |  | `AdminSupportController@show` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/admin/support` |  | `AdminSupportController@index` | <sub>api|CheckApiKey|auth:sanctum|CheckAdminRole</sub> |
| GET | `/api/v10/all-currencies` |  | `GeneralSettingCotroller@currencies` | <sub>api|CheckApiKey</sub> |
| GET | `/api/v10/analytics` |  | `AnalyticsController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/contact-us` |  | `ParcelController@ContactUs` | <sub>api</sub> |
| GET | `/api/v10/customer/installation` |  | `InstallerController@customerInstallation` | <sub>api</sub> |
| GET | `/api/v10/dashboard/available-parcels` |  | `DashboardController@availableParcels` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/dashboard/balance-details` |  | `DashboardController@balanceDetails` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/dashboard/filter` |  | `DashboardController@filter` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/dashboard` |  | `DashboardController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/delivery-charges` |  | `ParcelController@DeliveryCharges` | <sub>api</sub> |
| GET | `/api/v10/deliveryman/dashboard` |  | `DeliverymanController@dashboard` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/income-expense` |  | `DeliveryManIncomeExpenseController@deliverymanIncomeExpense` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/deliveryman/login` |  | `AuthController@deliveryManLogin` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/deliveryman/parcel-delivered` |  | `DeliverymanController@parcelDelivered` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/deliveryman/parcel-location-update` |  | `DeliverymanController@parcelLocationUpdate` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/deliveryman/parcel-not-delivered` |  | `DeliverymanController@parcelNotDelivered` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/parcel-payment-logs` |  | `DeliverymanController@parcelPaymentLogs` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/deliveryman/parcel-status-update` |  | `DeliverymanController@parcelStatusUpdate` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/parcel-status` |  | `DeliverymanController@parcelStatus` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/deliveryman/parcel/delivered-by-tracking/{id}` |  | `DeliveryManParcelController@parcelByTrackDelivered` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/deliveryman/parcel/delivered/{id}` |  | `DeliveryManParcelController@parcelDelivered` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/parcel/details/{id}` |  | `DeliveryManParcelController@details` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/parcel/index` |  | `DeliveryManParcelController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/deliveryman/parcel/partial-delivered/{id}` |  | `DeliveryManParcelController@parcelPartialDelivered` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/payment-logs` |  | `DeliverymanController@paymentLogs` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/deliveryman/profile` |  | `DeliverymanController@profile` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/external/salla/parcel` |  | `SallaParcelController@store` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/external/woocommerce/parcel` |  | `WooCommerceParcelController@store` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/external/zid/parcel` |  | `ZidParcelController@store` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/fcm-subscribe` |  | `PushNotificationController@fcmSubscribe` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/fcm-unsubscribe` |  | `PushNotificationController@fcmUnsubscribe` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/fraud/check` |  | `FraudController@check` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| DELETE | `/api/v10/fraud/delete/{id}` |  | `FraudController@destroy` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/fraud/edit/{id}` |  | `FraudController@edit` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/fraud/index` |  | `FraudController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/fraud/store` |  | `FraudController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| PUT | `/api/v10/fraud/update/{id}` |  | `FraudController@update` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/general-settings` |  | `GeneralSettingCotroller@index` | <sub>api|CheckApiKey</sub> |
| GET | `/api/v10/hub` |  | `HubController@index` | <sub>api|CheckApiKey</sub> |
| GET | `/api/v10/invoice-details/{id}` |  | `InvoiceController@invoiceDetails` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/invoice-list/index` |  | `InvoiceController@invoiceLists` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/ndr/parcel/{parcelId}` |  | `NdrApiController@byParcel` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/ndr/stats` |  | `NdrApiController@stats` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/ndr/{id}/notify` |  | `NdrApiController@notifyCustomer` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/ndr/{id}` |  | `NdrApiController@show` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/ndr` |  | `NdrApiController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/ndr` |  | `NdrApiController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/news-offer/index` |  | `NewsOfferController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/otp-verification` |  | `AuthController@otpVerification` | <sub>api|CheckApiKey</sub> |
| GET | `/api/v10/parcel/all/status` |  | `ParcelController@parcelAllStatus` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/create` |  | `ParcelController@create` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| DELETE | `/api/v10/parcel/delete/{id}` |  | `ParcelController@destroy` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/details/{id}` |  | `ParcelController@details` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/edit/{id}` |  | `ParcelController@edit` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/filter` |  | `ParcelController@filter` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/index` |  | `ParcelController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/logs/{id}` |  | `ParcelController@logs` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/parcel/store` |  | `ParcelController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/tracking/{tracking_id}` |  | `ParcelController@parcelTrackingLogs` | <sub>api</sub> |
| PUT | `/api/v10/parcel/update/{id}` |  | `ParcelController@update` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/parcel/{id}/status/{statusId}` |  | `ParcelController@statusUpdate` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/password/email` |  | `AuthController@sendPasswordResetLinkEmail` | <sub>api|CheckApiKey|throttle:5,1</sub> |
| POST | `/api/v10/password/reset` |  | `AuthController@resetPassword` | <sub>api|CheckApiKey</sub> |
| DELETE | `/api/v10/payment-account/delete/{id}` |  | `PaymentAccountController@delete` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/payment-account/edit/{id}` |  | `PaymentAccountController@edit` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/payment-account/store` |  | `PaymentAccountController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| PUT | `/api/v10/payment-account/update` |  | `PaymentAccountController@update` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/payment-accounts/index` |  | `PaymentAccountController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/payment-request/create` |  | `PaymentRequestController@create` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| DELETE | `/api/v10/payment-request/delete/{id}` |  | `PaymentRequestController@delete` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/payment-request/edit/{id}` |  | `PaymentRequestController@edit` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/payment-request/index` |  | `PaymentRequestController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/payment-request/store` |  | `PaymentRequestController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| PUT | `/api/v10/payment-request/update/{id}` |  | `PaymentRequestController@update` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/profile/update` |  | `AuthController@profileUpdate` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/profile` |  | `AuthController@profile` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/refresh` |  | `AuthController@refresh` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/register` |  | `AuthController@register` | <sub>api|CheckApiKey</sub> |
| GET | `/api/v10/rejection_reasons` |  | `ParcelController@rejection_reasons` | <sub>api</sub> |
| POST | `/api/v10/resend-otp` |  | `AuthController@resendOTP` | <sub>api|CheckApiKey</sub> |
| GET | `/api/v10/settings/cod-charges` |  | `SettingsController@codCharges` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/settings/delivery-charges` |  | `SettingsController@deliveryCharges` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| DELETE | `/api/v10/shops/delete/{id}` |  | `ShopsController@delete` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/shops/edit/{id}` |  | `ShopsController@edit` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/shops/index` |  | `ShopsController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/shops/store` |  | `ShopsController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| PUT | `/api/v10/shops/update/{id}` |  | `ShopsController@update` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/sign-out` |  | `AuthController@logout` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/signin` |  | `AuthController@signin` | <sub>api|CheckApiKey</sub> |
| POST | `/api/v10/statement-reports` |  | `ReportController@TotalSummeryStatementReports` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/statements/filter` |  | `StatementsController@filter` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/statements/index` |  | `StatementsController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/status-wise/parcel/list/{status}` |  | `ParcelController@statusWiseParcelList` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/subscribe` |  | `ParcelController@subscribe` | <sub>api</sub> |
| GET | `/api/v10/support/create` |  | `SupportController@create` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| DELETE | `/api/v10/support/delete/{id}` |  | `SupportController@destroy` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/support/edit/{id}` |  | `SupportController@edit` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/support/index` |  | `SupportController@index` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/support/reply` |  | `SupportController@supportReply` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/support/store` |  | `SupportController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| PUT | `/api/v10/support/update/{id}` |  | `SupportController@update` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/support/view/{id}` |  | `SupportController@view` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| PUT | `/api/v10/update-password` |  | `AuthController@updatePassword` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/wms/adjustments` |  | `WmsAdjustmentApiController@store` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/wms/fulfillment/my-tasks` |  | `WmsFulfillmentApiController@myTasks` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/wms/fulfillment/{id}/pack` |  | `WmsFulfillmentApiController@confirmPack` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/wms/fulfillment/{id}/pick` |  | `WmsFulfillmentApiController@confirmPick` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/wms/grn/{grn}/complete` |  | `WmsGrnApiController@complete` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| POST | `/api/v10/wms/grn/{grn}/scan` |  | `WmsGrnApiController@scanItem` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/wms/products/lookup` |  | `WmsProductApiController@lookup` | <sub>api|CheckApiKey|auth:sanctum</sub> |
| GET | `/api/v10/wms/stock/{productId}` |  | `WmsStockApiController@show` | <sub>api|CheckApiKey|auth:sanctum</sub> |

## `api/zajel`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/api/zajel/webhook` |  | `ZajelWebhookController@handle` | <sub>api</sub> |

## `bkash`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/bkash/execute` | `bkash.execute` | `BkashController@bkashExecute` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/bkash/redirect` | `bkash.redirect` | `BkashController@bkashRedirect` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `blog-details`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/blog-details/{id}` | `blog.details` | `FrontendController@blogDetails` | <sub>web|XSS|IsInstalled</sub> |

## `cancel`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/cancel` |  | `SslCommerzPaymentController@cancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `category`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/category/create` | `category.create` | `CategoryController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:category_create</sub> |
| DELETE | `/category/delete/{id}` | `category.delete` | `CategoryController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:category_delete</sub> |
| GET | `/category/edit/{id}` | `category.edit` | `CategoryController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:category_update</sub> |
| GET | `/category/index` | `category.index` | `CategoryController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:category_read</sub> |
| POST | `/category/store` | `category.store` | `CategoryController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:category_create</sub> |
| PUT | `/category/update` | `category.update` | `CategoryController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck|hasPermission:category_update</sub> |

## `company`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/company/otp-verification-form` | `company.otp-verification-form` | `CompanyController@otpVerificationForm` | <sub>web|XSS|IsInstalled</sub> |
| POST | `/company/otp-verification` | `company.otp-verification` | `CompanyController@otpVerification` | <sub>web|XSS|IsInstalled</sub> |
| POST | `/company/resend-otp` | `company.resend-otp` | `CompanyController@resendOTP` | <sub>web|XSS|IsInstalled</sub> |
| POST | `/company/sign-up/store` | `company.sign-up.store` | `CompanyController@signUpStore` | <sub>web|XSS|IsInstalled</sub> |
| GET | `/company/sign-up` | `company.sign-up` | `CompanyController@signUp` | <sub>web|XSS|IsInstalled</sub> |

## `contact-message-send`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/contact-message-send` | `contact.message.send` | `FrontendController@contactMessageSend` | <sub>web|XSS|IsInstalled</sub> |

## `contact-send`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/contact-send` | `contact.send.page` | `FrontendController@contactSendPage` | <sub>web|XSS|IsInstalled</sub> |

## `dashboard`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/dashboard` | `dashboard.index` | `DashbordController@index` | <sub>web|XSS|IsInstalled|auth</sub> |

## `dashboard-finance`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/dashboard-finance` | `dashboard.finance` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `dashboard-influencer`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/dashboard-influencer` | `dashboard.influencer` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `dashboard-sales`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/dashboard-sales` | `dashboard.sales` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `deliveryMan`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/deliveryMan/parcel/map/{id}/{lat}/{long}/{status}` |  | `MapParcelController@parcelMap` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `ecommerce-product`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/ecommerce-product` | `ecommerce.product` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `ecommerce-product-checkout`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/ecommerce-product-checkout` | `ecommerce.product.checkout` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `ecommerce-product-single`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/ecommerce-product-single` | `ecommerce.product.single` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `env-editor`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/env-editor/clear-cache` | `env-editor.clearConfigCache` | `EnvController@clearConfigCache` | <sub>web</sub> |
| POST | `/env-editor/files/create-backup` | `env-editor.createBackup` | `EnvController@createBackup` | <sub>web</sub> |
| DELETE | `/env-editor/files/destroy-backup/{filename?}` | `env-editor.destroyBackup` | `EnvController@destroyBackup` | <sub>web</sub> |
| GET | `/env-editor/files/download/{filename?}` | `env-editor.download` | `EnvController@download` | <sub>web</sub> |
| POST | `/env-editor/files/restore-backup/{filename?}` | `env-editor.restoreBackup` | `EnvController@restoreBackup` | <sub>web</sub> |
| POST | `/env-editor/files/upload` | `env-editor.upload` | `EnvController@upload` | <sub>web</sub> |
| GET | `/env-editor/files` | `env-editor.getBackups` | `EnvController@getBackupFiles` | <sub>web</sub> |
| DELETE | `/env-editor/key` |  | `EnvController@deleteKey` | <sub>web</sub> |
| PATCH | `/env-editor/key` |  | `EnvController@editKey` | <sub>web</sub> |
| POST | `/env-editor/key` | `env-editor.key` | `EnvController@addKey` | <sub>web</sub> |
| GET | `/env-editor` | `env-editor.index` | `EnvController@index` | <sub>web</sub> |

## `facebook`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/facebook/login` |  | `SocialLoginController@authFacebookLogin` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `fail`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/fail` |  | `SslCommerzPaymentController@fail` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `faq-list`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/faq-list` | `get.faq.index` | `FrontendController@faq` | <sub>web|XSS|IsInstalled</sub> |

## `finish`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/finish` | `final` | `InstallerController@finish` | <sub>web|XSS</sub> |

## `get-blogs`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/get-blogs` | `get.blogs` | `FrontendController@blogs` | <sub>web|XSS|IsInstalled</sub> |

## `google`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/google/login` |  | `SocialLoginController@authGoogleLogin` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `impersonate`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/impersonate/stop` | `merchant.impersonate.stop` | `MerchantController@stopImpersonate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `influencer-finder`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/influencer-finder` | `influencer.finder` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `influencer-profile`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/influencer-profile` | `influencer.profile` | `Closure` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |

## `install`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/install` |  | `InstallerController@index` | <sub>web|XSS|IsNotInstalled</sub> |

## `installing`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/installing` | `installing` | `InstallerController@installing` | <sub>web|XSS</sub> |

## `ipn`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/ipn` |  | `SslCommerzPaymentController@ipn` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `localization`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/localization/{language}` | `setlocalization` | `LocalizationController@setLocalization` | <sub>web|XSS|IsInstalled</sub> |

## `login`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/login/{slug}` | `login.branded` | `LoginController@showLoginForm` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|guest</sub> |
| GET | `/login/{social}` | `social.login` | `SocialLoginController@socialRedirect` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |
| GET | `/login` | `login` | `LoginController@showLoginForm` | <sub>web|XSS|IsInstalled|guest</sub> |
| POST | `/login` |  | `LoginController@login` | <sub>web|XSS|IsInstalled|guest</sub> |

## `logout`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/logout` | `logout` | `LoginController@logout` | <sub>web|XSS|IsInstalled</sub> |

## `merchant/accounts`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/accounts/account-transaction-filter` | `merchant.accounts.account-transaction.filter` | `AccountTransactionController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/accounts/account-transaction` | `merchant.accounts.account-transaction.index` | `AccountTransactionController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/merchant/accounts/payment-account/delete/{id}` | `payment.account.delete` | `PaymentAccountController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/accounts/payment-account/edit/{id}` | `payment.account.edit` | `PaymentAccountController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/accounts/payment-account/store` | `payment.account.store` | `PaymentAccountController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/accounts/payment-account/update` | `payment.account.update` | `PaymentAccountController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/accounts/payment-accounts/create` | `payment.account.create` | `PaymentAccountController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/accounts/payment-accounts` | `merchant.accounts.payment-account.index` | `PaymentAccountController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/accounts/statements-filter` | `merchant.accounts.statements.filter` | `StatementsController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/accounts/statements` | `merchant.accounts.statements.index` | `StatementsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/apply`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/apply/success` | `merchant.apply.success` | `MerchantController@applySuccess` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |
| GET | `/merchant/apply` | `merchant.apply` | `MerchantController@apply` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |
| POST | `/merchant/apply` | `merchant.apply.store` | `MerchantController@applyStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `merchant/dashboard`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/dashboard/filter` | `merchant-panel.dashboard.filter` | `DashbordController@merchantDashboardFilter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/exports`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/exports/shipment-template-single` | `exports.shipment-template.single` | `ShipmentExportController@downloadSingle` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/exports/shipment-template` | `exports.shipment-template` | `ShipmentExportController@download` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/fraud`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/fraud/check` | `merchant-panel.fraud.check` | `FraudController@check` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/fraud/create` | `merchant-panel.fraud.create` | `FraudController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/merchant/fraud/delete/{id}` | `merchant-panel.fraud.delete` | `FraudController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/fraud/edit/{id}` | `merchant-panel.fraud.edit` | `FraudController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/fraud/filter` | `merchant-panel.fraud.filter` | `FraudController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/fraud/store` | `merchant-panel.fraud.store` | `FraudController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/fraud/update` | `merchant-panel.fraud.update` | `FraudController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/fraud` | `merchant-panel.fraud.index` | `FraudController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/invoice`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/invoice/csv/{merchant_id}/{invoice_id}` | `merchant.panel.invoice.csv` | `MerchantInvoiceController@InvoiceCSV` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/invoice/pdf/{merchant_id}/{invoice_id}` | `merchant.panel.invoice.pdf` | `MerchantInvoiceController@InvoicePdf` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/invoice/{invoice_id}` | `merchant.panel.invoice.details` | `InvoiceController@InvoiceDetails` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/invoice` | `merchant.panel.invoice.index` | `InvoiceController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/m_parcel`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/m_parcel/file-import-confirm` | `merchant-panel.parcel.import.confirm` | `MerchantParcelController@m_parcelImportConfirm` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/m_parcel/file-import` | `merchant-panel.m_parcel.file-import` | `MerchantParcelController@parcelImportExport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/m_parcel/file-import` | `merchant-panel.m_parcel.file-import.post` | `MerchantParcelController@m_parcelImport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/my-wallet`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/my-wallet/recharge-add` | `merchant-panel.my.wallet.recharge.add` | `WalletController@rechargeAdd` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/my-wallet/recharge-status` | `merchant-panel.my.wallet.recharge.status` | `WalletController@rechargeStatus` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/my-wallet/recharge` | `merchant-panel.my.wallet.recharge` | `WalletController@recharge` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/my-wallet` | `merchant-panel.my.wallet.index` | `WalletController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/news-offer`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/news-offer/index` | `merchant-panel.news-offer.index` | `NewsOfferController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/online-payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/online-payment/aamarpay` | `online.payment.aamarpay.index` | `OnlinePaymentController@aamarpayIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/online-payment/paypal-index` | `online.payment.paypal.index` | `OnlinePaymentController@paypalIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/online-payment/paypal-payment` | `online.payment.paypal` | `OnlinePaymentController@paypalpayment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/online-payment/sslcommerz` | `online.payment.sslcommerz.index` | `OnlinePaymentController@sslcommerzIndex` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/online-payment/stripe/post` | `online.payment.stripe.post` | `OnlinePaymentController@stripePost` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/online-payment/stripe` | `online.payment.stripe` | `OnlinePaymentController@stripe` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/online-payment` | `online.payment.index` | `OnlinePaymentController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/online-payment-received-list`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/online-payment-received-list` | `merchant.online.payment.list` | `MerchantOnlinePaymentSetupController@onlinePaymentReceivedList` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/otp-verification`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/otp-verification` | `merchant.otp-verification` | `MerchantController@otpVerification` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `merchant/otp-verification-form`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/otp-verification-form` | `merchant.otp-verification-form` | `MerchantController@otpVerificationForm` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `merchant/parcel`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/parcel/clone-store` | `merchant-parcel.clone-store` | `MerchantParcelController@duplicateStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/clone/{id}` | `merchant-parcel.clone` | `MerchantParcelController@duplicate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/create` | `merchant-panel.parcel.create` | `MerchantParcelController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/merchant/parcel/delete/{id}` | `merchant-panel.parcel.delete` | `MerchantParcelController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/parcel/delivery-category` | `merchant-panel.parcel.deliveryCategory.deliveryWeight` | `MerchantParcelController@deliveryWeight` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/parcel/delivery-charge` | `merchant-panel.parcel.deliveryCharge.get` | `MerchantParcelController@deliveryCharge` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/details/{id}` | `merchant-panel.parcel.details` | `MerchantParcelController@details` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/edit/{id}` | `merchant-panel.parcel.edit` | `MerchantParcelController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/file-export` | `merchant-panel.parcel.file-export` | `MerchantParcelController@parcelExport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/parcel/file-import` | `merchant-panel.parcel.file-import` | `MerchantParcelController@parcelImport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/filter` | `merchant-panel.parcel.filter` | `MerchantParcelController@filter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/get-areas` | `merchant-panel.parcel.getAreas` | `MerchantParcelController@getAreasByCity` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/import-parcel` | `merchant-panel.parcel.parcel-import` | `MerchantParcelController@parcelImportExport` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/index` | `merchant-panel.parcel.index` | `MerchantParcelController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/logs/{id}` | `merchant-panel.parcel.logs` | `MerchantParcelController@logs` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/parcel/merchant/shops` | `merchant-panel.parcel.merchant.shops` | `MerchantParcelController@merchantShops` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/parcel/merchant` | `merchant-panel.parcel.merchant.get` | `MerchantParcelController@getMerchant` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/my-products` | `merchant-panel.parcel.myProducts` | `MerchantParcelController@myProducts` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/parcel/status-update/{id}/{status_id}` | `merchant-panel.parcel.status-update` | `MerchantParcelController@statusUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/parcel/store` | `merchant-panel.parcel.store` | `MerchantParcelController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/parcel/update/{id}` | `merchant-panel.parcel.update` | `MerchantParcelController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/parcel-bank`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/parcel-bank/index` | `merchant-panel.parcel-bank.index` | `MerchantParcelController@parcelBank` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/parcel-reports-print-page`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/parcel-reports-print-page/{array}` | `merchant-panel.parcel.reports.print.page` | `MerchantReportsController@parcelReportsPrint` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/payment/received` | `online.payment.received` | `OnlinePaymentController@merchantPaymentReceived` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/payment-request`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/payment-request/create` | `merchant-panel.payment-request.create` | `PaymentRequestController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/merchant/payment-request/delete/{id}` | `merchant-panel.payment-request.delete` | `PaymentRequestController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/payment-request/edit/{id}` | `merchant-panel.payment-request.edit` | `PaymentRequestController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/payment-request/index` | `merchant-panel.payment-request.index` | `PaymentRequestController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/payment-request/store` | `merchant-panel.payment-request.store` | `PaymentRequestController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/payment-request/update` | `merchant-panel.payment-request.update` | `PaymentRequestController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/pickup-request`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/pickup-request/express` | `merchant.panel.pickup.request.express.store` | `PickupRequestController@expressStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/pickup-request/regular` | `merchant.panel.pickup.request.regular.store` | `PickupRequestController@regularStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/profile`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/profile/change-password/{id}` | `merchant-password.change` | `MerchantProfileController@changePassword` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/profile/update-password/{id}` | `merchant-profile.password.update` | `MerchantProfileController@updatePassword` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/profile/update/{id}` | `merchant-profile.edit` | `MerchantProfileController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/profile/update/{id}` | `merchant-profile.update` | `MerchantProfileController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/profile/{id}` | `merchant-profile.index` | `MerchantProfileController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/reports`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/reports/parcel-filter-reports` | `merchant-panel.parcel.filter.reports` | `MerchantReportsController@parcelSReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/reports/parcel-finance-reports` | `merchant-panel.parcel.finance.reports` | `ReportsController@parcelFinanceReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/reports/parcel-reports` | `merchant-panel.parcel.reports` | `MerchantReportsController@parcelReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/reports/total-summery-filter` | `merchant.parcel.filter.total.summery` | `ReportsController@TotalSummeryReportsFilter` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/reports/total-summery` | `merchant.total.summery` | `ReportsController@TotalSummeryReports` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/resend-otp`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/resend-otp` | `merchant.resend-otp` | `MerchantController@resendOTP` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `merchant/settings`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/settings/cod-charges` | `merchant.cod-charges.index` | `SettingsController@CODcharges` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/settings/delivery-charges` | `merchant.delivery-charges.index` | `SettingsController@deliveryCharges` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/settings/online-payment-setup/update/{paymentmethod}` | `merchant.online.payment.setup.update` | `MerchantOnlinePaymentSetupController@paymentReceivedSetupUpdate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/settings/online-payment-setup` | `merchant.online.payment.setup.index` | `MerchantOnlinePaymentSetupController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/shops`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/shops/create` | `merchant-panel.shops.create` | `ShopsController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/merchant/shops/delete/{id}` | `merchant-panel.shops.delete` | `ShopsController@delete` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/shops/edit/{id}` | `merchant-panel.shops.edit` | `ShopsController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/shops/index` | `merchant-panel.shops.index` | `ShopsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/shops/store` | `merchant-panel.shops.store` | `ShopsController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/shops/update/{id}` | `merchant-panel.shops.update` | `ShopsController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/sign-up`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/sign-up` | `merchant.sign-up` | `MerchantController@signUp` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `merchant/sign-up-store`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/merchant/sign-up-store` | `merchant.sign-up-store` | `MerchantController@signUpStore` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `merchant/support`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/support/create` | `merchant-panel.support.add` | `SupportController@create` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| DELETE | `/merchant/support/delete/{id}` | `merchant-panel.support.delete` | `SupportController@destroy` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/support/edit/{id}` | `merchant-panel.support.edit` | `SupportController@edit` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/support/index` | `merchant-panel.support.index` | `SupportController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/support/reply` | `merchant-panel.support.reply` | `SupportController@supportReply` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/support/store` | `merchant-panel.support.store` | `SupportController@store` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/support/update/{id}` | `merchant-panel.support.update` | `SupportController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/support/view/{id}` | `merchant-panel.support.view` | `SupportController@view` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `merchant/zatca`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/merchant/zatca/invoices/{id}/pdf` | `merchant.panel.zatca.invoices.pdf` | `InvoiceController@pdf` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/zatca/invoices/{id}/qr` | `merchant.panel.zatca.invoices.qr` | `InvoiceController@qr` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| POST | `/merchant/zatca/invoices/{id}/regenerate` | `merchant.panel.zatca.invoices.regenerate` | `InvoiceController@regenerate` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/zatca/invoices/{id}` | `merchant.panel.zatca.invoices.show` | `InvoiceController@show` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/zatca/invoices` | `merchant.panel.zatca.invoices.index` | `InvoiceController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| GET | `/merchant/zatca/settings` | `merchant.panel.zatca.settings.index` | `SettingsController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |
| PUT | `/merchant/zatca/settings` | `merchant.panel.zatca.settings.update` | `SettingsController@update` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `online-payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/online-payment/bkash` | `online.payment.bkash.index` | `BkashController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `password`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/password/confirm` | `password.confirm` | `ConfirmPasswordController@showConfirmForm` | <sub>web|XSS|IsInstalled|auth</sub> |
| POST | `/password/confirm` |  | `ConfirmPasswordController@confirm` | <sub>web|XSS|IsInstalled|auth</sub> |
| POST | `/password/email` | `password.email` | `ForgotPasswordController@sendResetLinkEmail` | <sub>web|XSS|IsInstalled</sub> |
| GET | `/password/reset/{token}` | `password.reset` | `ResetPasswordController@showResetForm` | <sub>web|XSS|IsInstalled</sub> |
| GET | `/password/reset` | `password.request` | `ForgotPasswordController@showLinkRequestForm` | <sub>web|XSS|IsInstalled</sub> |
| POST | `/password/reset` | `password.update` | `ResetPasswordController@reset` | <sub>web|XSS|IsInstalled</sub> |

## `pay-via-ajax`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/pay-via-ajax` |  | `SslCommerzPaymentController@payViaAjax` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `payment-cancelled`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/payment-cancelled` |  | `SkrillController@PaymentCancelled` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `payment-completed`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/payment-completed` | `skrill.payment.completed` | `SkrillController@paymentCompleted` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `privacy-and-policy`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/privacy-and-policy` | `privacy.policy.index` | `FrontendController@privacyPolicy` | <sub>web|XSS|IsInstalled</sub> |

## `register`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/register` | `register` | `RegisterController@showRegistrationForm` | <sub>web|XSS|IsInstalled|guest</sub> |
| POST | `/register` |  | `RegisterController@register` | <sub>web|XSS|IsInstalled|guest</sub> |

## `sanctum`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/sanctum/csrf-cookie` | `sanctum.csrf-cookie` | `CsrfCookieController@show` | <sub>web</sub> |

## `search-charts`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/search-charts` | `search-charts` | `DashbordController@searchCharts` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `service-details`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/service-details/{id}` | `service.details` | `FrontendController@serviceDetails` | <sub>web|XSS|IsInstalled</sub> |

## `set-locale`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/set-locale/{locale}` | `setLocale` | `App\Http\Controllers\Frontend\FrontendController` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `shipment-location`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/shipment-location/{shipment_id}` | `shipment.location` | `FrontendController@shipmentLocation` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |
| PUT | `/shipment-location/{shipment_id}` | `shipment.updateLocation` | `FrontendController@updateLocation` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware</sub> |

## `skrill`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/skrill` | `skrill.index` | `SkrillController@index` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `skrill-make-payment`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/skrill-make-payment` | `skrill.make.payment` | `SkrillController@makePayment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `store-token`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/store-token` | `notification-store.token` | `WebNotificationController@store` | <sub>web|XSS|IsInstalled|auth</sub> |

## `subscribe-store`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/subscribe-store` | `subscribe.store` | `FrontendController@subscribe` | <sub>web|XSS|IsInstalled</sub> |

## `subscription`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET,POST,PUT,PATCH,DELETE,OPTIONS | `/subscription/cancel` | `subscription.cancel` | `PlanController@StripePaymentCancel` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |
| GET | `/subscription/payment` | `subscription.payment` | `PlanController@subscriptionPayment` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |
| GET,POST,PUT,PATCH,DELETE,OPTIONS | `/subscription/success` | `subscription.success` | `PlanController@StripePaymentSuccess` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth</sub> |
| GET | `/subscription` | `subscription.index` | `PlanController@subscription` | <sub>web|XSS|IsInstalled|auth</sub> |

## `success`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/success` |  | `SslCommerzPaymentController@success` | <sub>web|XSS|IsInstalled|PreventAccessFromCentralDomains|InitializeTenancyByDomain|CompanyActivationMiddleware|auth|subscriptionCheck</sub> |

## `super-admin/company`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/super-admin/company/create` | `company.create` | `CompanyController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_create</sub> |
| DELETE | `/super-admin/company/delete/{id}` | `company.delete` | `CompanyController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_delete</sub> |
| GET | `/super-admin/company/edit/{id}` | `company.edit` | `CompanyController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_update</sub> |
| POST | `/super-admin/company/store` | `company.store` | `CompanyController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_create</sub> |
| POST | `/super-admin/company/subscription/switch/store` | `company.subscription.switch.store` | `CompanyController@switchSubscriptionStore` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_subscribe</sub> |
| GET | `/super-admin/company/subscription/switch/{id}` | `company.subscription.switch` | `CompanyController@switchSubscription` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_subscribe</sub> |
| PUT | `/super-admin/company/update` | `company.update` | `CompanyController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_update</sub> |
| GET | `/super-admin/company` | `company.index` | `CompanyController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:company_read</sub> |

## `super-admin/plan`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/super-admin/plan/create` | `plan.create` | `PlanController@create` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_create</sub> |
| DELETE | `/super-admin/plan/delete/{id}` | `plan.delete` | `PlanController@delete` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_delete</sub> |
| GET | `/super-admin/plan/edit/{id}` | `plan.edit` | `PlanController@edit` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_update</sub> |
| GET | `/super-admin/plan/modules/{plan_id}` | `plan.modules.view` | `PlanController@modulesView` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_read</sub> |
| POST | `/super-admin/plan/store` | `plan.store` | `PlanController@store` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_create</sub> |
| PUT | `/super-admin/plan/update` | `plan.update` | `PlanController@update` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_update</sub> |
| GET | `/super-admin/plan` | `plan.index` | `PlanController@index` | <sub>web|XSS|IsInstalled|auth|hasPermission:plans_read</sub> |

## `super-admin/subscription`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/super-admin/subscription/history` | `subscription.history` | `PlanController@subscriptionHistory` | <sub>web|XSS|IsInstalled|auth</sub> |

## `tenancy`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/tenancy/assets/{path?}` | `stancl.tenancy.asset` | `TenantAssetsController@asset` | <sub>InitializeTenancyByDomain</sub> |

## `terms-of-condition`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/terms-of-condition` | `termsof.condition.index` | `FrontendController@termsOfCondition` | <sub>web|XSS|IsInstalled</sub> |

## `tracking`

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/tracking` | `tracking.index` | `FrontendController@tracking` | <sub>web|XSS|IsInstalled</sub> |

