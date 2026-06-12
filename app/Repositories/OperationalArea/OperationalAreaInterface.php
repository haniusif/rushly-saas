<?php

namespace App\Repositories\OperationalArea;

interface OperationalAreaInterface
{
    public function all();
    public function get($id);
    public function store($request);
    public function update($id, $request);
    public function delete($id);
}
