<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\WhyCourier\StoreRequest;
use App\Http\Requests\FrontWeb\WhyCourier\UpdateRequest;
use App\Repositories\FrontWeb\WhyCourier\WhyCourierInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhyCourierController extends Controller
{
    protected $repo;
    public function __construct(WhyCourierInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $whycouriers = $this->repo->get();

        return Inertia::render('Admin/FrontWeb/WhyRushly/Index', [
            'rows' => collect($whycouriers->items())->map(fn ($w) => [
                'id'          => $w->id,
                'title'       => (string) $w->title,
                'image'       => $w->image,
                'position'    => (int) $w->position,
                'status_html' => $w->my_status ?? '',
                'urls'        => [
                    'edit'   => route('why.rushly.edit',   $w->id),
                    'delete' => route('why.rushly.delete', $w->id),
                ],
            ])->values(),
            'pagination'  => paginate_shape($whycouriers),
            'permissions' => front_web_permissions('why_courier'),
            'urls'        => [
                'create' => route('why.rushly.create'),
            ],
            't' => array_merge(front_web_t(__('menus.why_rushly') ?: 'Why Rushly', 'Do you want to delete row ?'), [
                'title' => __('levels.title'),
                'image' => __('levels.image'),
            ]),
        ]);
    }

    public function create()
    {
        return view('backend.front_web.why_courier.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.why_courier_added'), __('message.success'));
            return redirect()->route('why.rushly.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput($request->all());
        endif;
    }

    public function edit($id)
    {
        $whycourier  = $this->repo->getFind($id);
        return view('backend.front_web.why_courier.edit', compact('whycourier'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.why_courier_updated'), __('message.success'));
            return redirect()->route('why.rushly.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput($request->all());
        endif;
    }

    public function delete($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;
        
        if ($this->repo->delete($id)) :
            Toastr::success(__('levels.why_courier_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
