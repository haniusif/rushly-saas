<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Pages\UpdateRequest;
use App\Repositories\FrontWeb\Pages\PagesInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    protected $repo;
    public function __construct(PagesInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        // Repo::all() is actually a LengthAwarePaginator — use ->items() so
        // collect() gets Model objects instead of the paginator's iterator
        // yielding integers under certain conditions.
        $pages = $this->repo->all();

        return Inertia::render('Admin/FrontWeb/Page/Index', [
            'rows' => collect($pages->items())->map(fn ($p) => [
                'id'         => $p->id,
                'title'      => (string) $p->title,
                'slug'       => (string) $p->slug,
                'updated_at' => dateFormat($p->updated_at),
                'urls'       => [
                    'edit' => route('pages.edit', $p->id),
                ],
            ])->values(),
            'pagination'  => paginate_shape($pages),
            'permissions' => ['create' => false, 'update' => hasPermission('pages_update'), 'delete' => false],
            'urls'        => [],
            't' => array_merge(front_web_t(__('levels.pages') ?: 'Pages', ''), [
                'page_title' => __('levels.title'),
                'slug'       => __('levels.slug'),
                'updated'    => __('levels.updated'),
            ]),
        ]);
    }

    public function edit($id)
    {
        $page  = $this->repo->getFind($id);
        return view('backend.front_web.pages.edit', compact('page'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.page_updated'), __('message.success'));
            return redirect()->route('pages.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }
}
