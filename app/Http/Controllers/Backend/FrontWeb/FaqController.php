<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Faq\StoreRequest;
use App\Http\Requests\FrontWeb\Faq\UpdateRequest;
use App\Repositories\FrontWeb\Faq\FaqInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
    protected $repo;
    public function __construct(FaqInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $faqs = $this->repo->get();

        return Inertia::render('Admin/FrontWeb/Faq/Index', [
            'rows' => collect($faqs->items())->map(fn ($f) => [
                'id'          => $f->id,
                'question'    => (string) $f->question,
                'answer_html' => (string) $f->answer,
                'position'    => (int) $f->position,
                'status_html' => $f->my_status ?? '',
                'urls'        => [
                    'edit'   => route('faq.edit',   $f->id),
                    'delete' => route('faq.delete', $f->id),
                ],
            ])->values(),
            'pagination'  => paginate_shape($faqs),
            'permissions' => front_web_permissions('faq'),
            'urls'        => [
                'create' => route('faq.create'),
            ],
            't' => front_web_t(__('levels.faq'), 'Do you want to delete faq ?'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/FrontWeb/Faq/Form', $this->formProps(null));
    }

    private function formProps($row): array
    {
        $isEdit = $row !== null;
        return [
            'mode' => $isEdit ? 'edit' : 'create',
            'row'  => [
                'id'       => $isEdit ? $row->id : null,
                'question' => $isEdit ? (string) $row->question : '',
                'answer'   => $isEdit ? (string) $row->answer   : '',
                'position' => $isEdit ? (string) $row->position : '',
                'status'   => $isEdit ? (string) $row->status   : (string) \App\Enums\Status::ACTIVE,
            ],
            'lookups' => [
                'statuses' => collect(trans('status'))->map(fn ($label, $key) => [
                    'value' => (string) $key,
                    'label' => $label,
                ])->values(),
            ],
            'urls' => [
                'submit' => $isEdit ? route('faq.update', $row->id) : route('faq.store'),
                'index'  => route('faq.index'),
            ],
            't' => [
                'title'      => ($isEdit ? __('levels.edit') : __('levels.create')) . ' ' . __('levels.faq'),
                'front_web'  => __('levels.front_web'),
                'faq'        => __('levels.faq'),
                'question'   => __('levels.question'),
                'answer'     => __('levels.answer'),
                'position'   => __('levels.position'),
                'status'     => __('levels.status'),
                'save'       => __('levels.save'),
                'cancel'     => __('levels.cancel'),
                'section'    => ($isEdit ? __('levels.edit') : __('levels.create')) . ' ' . __('levels.faq'),
            ],
        ];
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.faq_added'), __('message.success'));
            return redirect()->route('faq.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput($request->all());
        endif;
    }

    public function edit($id)
    {
        $faq = $this->repo->getFind($id);
        if (! $faq) abort(404);
        return Inertia::render('Admin/FrontWeb/Faq/Form', $this->formProps($faq));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.faq_updated'), __('message.success'));
            return redirect()->route('faq.index');
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
            Toastr::success(__('levels.faq_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
