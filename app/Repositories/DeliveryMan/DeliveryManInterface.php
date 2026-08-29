<?php
namespace App\Repositories\DeliveryMan;

interface DeliveryManInterface {

    public function all();

    /**
     * Every deliveryman for the tenant, unpaginated — for dropdowns and
     * selectors. all() paginates for the list page, which silently truncated
     * every "assign a driver" control to the first 10.
     */
    public function selectable();
    public function hubs();
    public function get($id);
    public function filter($request);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
    public function deliverymanEarn($type);
    public function totalCOD($type);
    public function paymentLogs();
    public function parcelPaymentLogs();
    public function shipments($date);
}
