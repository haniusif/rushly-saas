<?php

namespace App\Http\Controllers\Backend\FrontWeb;

use App\Http\Controllers\Controller;
use App\Http\Requests\FrontWeb\Blog\StoreRequest;
use App\Http\Requests\FrontWeb\Blog\UpdateRequest;
use App\Repositories\FrontWeb\Blogs\BlogsInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{

    protected $repo;
    public function __construct(BlogsInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $blogs = $this->repo->get();

        return Inertia::render('Admin/FrontWeb/Blog/Index', [
            'rows' => collect($blogs->items())->map(fn ($b) => [
                'id'               => $b->id,
                'title'            => (string) $b->title,
                'description_plain'=> strip_tags((string) $b->description),
                'image'            => $b->image,
                'position'         => (int) $b->position,
                'author'           => optional($b->user)->name,
                'created_at'       => dateFormat($b->created_at),
                'status_html'      => $b->my_status ?? '',
                'urls'             => [
                    'edit'   => route('blogs.edit',   $b->id),
                    'delete' => route('blogs.delete', $b->id),
                ],
            ])->values(),
            'pagination'  => paginate_shape($blogs),
            'permissions' => front_web_permissions('blogs'),
            'urls'        => [
                'create' => route('blogs.create'),
            ],
            't' => array_merge(front_web_t(__('menus.blogs') ?: 'Blogs', 'Do you want to delete blog ?'), [
                'blog_title'   => __('levels.title'),
                'description'  => __('levels.description'),
                'created_by'   => __('levels.created_by'),
                'date'         => __('levels.date'),
            ]),
        ]);
    }

    public function create()
    {
        return view('backend.front_web.blogs.create');
    }

    public function store(StoreRequest $request)
    {
        if ($this->repo->store($request)) :
            Toastr::success(__('levels.blog_added'), __('message.success'));
            return redirect()->route('blogs.index');
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back()->withInput();
        endif;
    }

    public function edit($id)
    {
        $blog  = $this->repo->getFind($id);
        return view('backend.front_web.blogs.edit', compact('blog'));
    }

    public function update(UpdateRequest $request, $id)
    {
        if ($this->repo->update($id, $request)) :
            Toastr::success(__('levels.blog_updated'), __('message.success'));
            return redirect()->route('blogs.index');
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
            Toastr::success(__('levels.blog_deleted'), __('message.success'));
            return redirect()->back();
        else :
            Toastr::error(__('parcel.error_msg'), __('message.error'));
            return redirect()->back();
        endif;
    }
}
