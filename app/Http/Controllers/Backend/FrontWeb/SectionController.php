<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Repositories\FrontWeb\Section\SectionInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    protected $repo;
    public function __construct(SectionInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        // Repo paginates a group-by-type query so each landing page section
        // (banner, feature, etc.) appears once regardless of key rows.
        $sections = $this->repo->all();

        return Inertia::render('Admin/FrontWeb/Section/Index', [
            'rows' => collect($sections->items())->map(fn ($s) => [
                'id'    => $s->id,
                'type'  => (string) $s->type,
                'my_type' => (string) $s->my_type,
                'urls'  => [
                    'edit' => route('section.edit', $s->type),
                ],
            ])->values(),
            'pagination'  => paginate_shape($sections),
            'permissions' => ['create' => false, 'update' => hasPermission('section_update'), 'delete' => false],
            'urls'        => [],
            't' => array_merge(front_web_t(__('levels.sections') ?: 'Sections', ''), [
                'type' => __('levels.type'),
            ]),
        ]);
    }

    public function edit($type)
    {
        $section     = $this->repo->getFind($type);
        $section_type = $this->repo->sectionType($type);
        return view('backend.front_web.section.edit', compact('type', 'section', 'section_type'));
    }

    public function update(Request $request, $type)
    {

        if ($this->repo->update($type, $request)) :
            Toastr::success(__('levels.section_updated'), __('message.success'));
            return redirect()->route('section.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
}
