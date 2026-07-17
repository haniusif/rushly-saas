<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\Http\Requests\FrontWeb\SocialLink\StoreRequest;
use App\Repositories\FrontWeb\SocialLink\SocialLinkInterface;
use Inertia\Inertia;

class SocialLinkController extends Controller
{
    protected $repo;
    public function __construct(SocialLinkInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $socialLinks = $this->repo->get();

        return Inertia::render('Admin/FrontWeb/SocialLink/Index', [
            'rows' => collect($socialLinks->items())->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => (string) $s->name,
                'icon'        => (string) $s->icon,
                'link'        => (string) $s->link,
                'status_html' => $s->my_status ?? '',
                'urls'        => [
                    'edit'   => route('social.link.edit',   $s->id),
                    'delete' => route('social.link.delete', $s->id),
                ],
            ])->values(),
            'pagination'  => paginate_shape($socialLinks),
            'permissions' => front_web_permissions('social_link'),
            'urls'        => [
                'create' => route('social.link.create'),
            ],
            't' => array_merge(front_web_t(__('levels.social_link') ?: 'Social links', 'Do you want to delete social link ?'), [
                'name' => __('levels.name'),
                'icon' => __('levels.icon'),
                'link' => __('levels.link'),
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/FrontWeb/SocialLink/Form', $this->formProps(null));
    }

    private function formProps($row): array
    {
        $isEdit = $row !== null;
        return [
            'mode' => $isEdit ? 'edit' : 'create',
            'row'  => [
                'id'       => $isEdit ? $row->id : null,
                'name'     => $isEdit ? (string) $row->name     : '',
                'icon'     => $isEdit ? (string) $row->icon     : '',
                'link'     => $isEdit ? (string) $row->link     : '',
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
                'submit' => $isEdit ? route('social.link.update', $row->id) : route('social.link.store'),
                'index'  => route('social.link.index'),
            ],
            't' => [
                'title'      => ($isEdit ? __('levels.edit') : __('levels.create')) . ' ' . (__('levels.social_link') ?: 'Social link'),
                'front_web'  => __('levels.front_web'),
                'social_link'=> __('levels.social_link') ?: 'Social links',
                'name'       => __('levels.name'),
                'icon'       => __('levels.icon'),
                'link'       => __('levels.link'),
                'position'   => __('levels.position'),
                'status'     => __('levels.status'),
                'save'       => __('levels.save'),
                'cancel'     => __('levels.cancel'),
                'section'    => ($isEdit ? __('levels.edit') : __('levels.create')) . ' ' . (__('levels.social_link') ?: 'Social link'),
            ],
        ];
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.social_link_added'), __('message.success'));
            return redirect()->route('social.link.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function edit($id)
    {
        $socialLink = $this->repo->getFind($id);
        if (! $socialLink) abort(404);
        return Inertia::render('Admin/FrontWeb/SocialLink/Form', $this->formProps($socialLink));
    }

    public function update(StoreRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.social_link_updated'), __('message.success'));
            return redirect()->route('social.link.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
    
    public function delete($id)
    {
        if(env('DEMO')):
            Toastr::error('Delete system is disable for the demo mode.',__('message.error'));
            return redirect()->back();
        endif;
        
        if ($this->repo->delete($id)) :
            Toastr::success(__('levels.social_link_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
