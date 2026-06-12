<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperationalArea\StoreRequest;
use App\Http\Requests\OperationalArea\UpdateRequest;
use App\Repositories\OperationalArea\OperationalAreaInterface;
use Brian2694\Toastr\Facades\Toastr;

class OperationalAreaController extends Controller
{
    protected $repo;

    public function __construct(OperationalAreaInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $areas = $this->repo->all();
        return view('backend.operational_area.index', compact('areas'));
    }

    public function create()
    {
        return view('backend.operational_area.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success('Operational area added.', __('message.success'));
            return redirect()->route('operational_areas.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }

    public function edit($id)
    {
        $area = $this->repo->get($id);
        if (!$area) {
            Toastr::error('Not found.', __('message.error'));
            return redirect()->route('operational_areas.index');
        }
        return view('backend.operational_area.edit', compact('area'));
    }

    public function update(UpdateRequest $request)
    {
        if ($this->repo->update($request->id, $request)) {
            Toastr::success('Operational area updated.', __('message.success'));
            return redirect()->route('operational_areas.index');
        }
        Toastr::error('Something went wrong.', __('message.error'));
        return redirect()->back()->withInput();
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        Toastr::success('Operational area deleted.', __('message.success'));
        return back();
    }
}
