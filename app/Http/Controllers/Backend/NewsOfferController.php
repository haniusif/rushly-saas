<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsOffer\StoreNewsOfferRequest;
use App\Http\Requests\NewsOffer\UpdateNewsOfferRequest;
use App\Repositories\NewsOffer\NewsOfferInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NewsOfferController extends Controller
{
    protected $repo;
    public function __construct(NewsOfferInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(fn ($n) => [
            'id'           => $n->id,
            'title'        => (string) $n->title,
            'description'  => Str::limit(strip_tags((string) $n->description), 140),
            'image_url'    => $n->image,
            'status'       => (int) $n->status,
            'status_label' => trans('status.' . $n->status) ?: ($n->status == 1 ? 'Active' : 'Inactive'),
            'date'         => optional($n->date)->format('Y-m-d') ?: (string) $n->date,
            'urls'         => [
                'edit'   => route('news-offer.edit', $n->id),
                'delete' => route('news-offer.delete', $n->id),
            ],
        ])->values();

        return Inertia::render('Admin/NewsOffer/Index', [
            'rows'        => $rows,
            'pagination'  => $this->paginateMeta($paginator),
            'permissions' => [
                'create' => hasPermission('news_offer_create'),
                'update' => hasPermission('news_offer_update'),
                'delete' => hasPermission('news_offer_delete'),
            ],
            'urls' => [
                'index'  => route('news-offer.index'),
                'create' => route('news-offer.create'),
            ],
            't' => $this->labels([
                'title'          => __('news_offer.title') ?: 'News & Offers',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No news yet.',
                'delete_confirm' => 'Delete this entry?',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/NewsOffer/Form', [
            'mode'    => 'create',
            'entity'  => null,
            'lookups' => [
                'statuses' => $this->statusOptions(),
            ],
            'urls' => [
                'submit' => route('news-offer.store'),
                'cancel' => route('news-offer.index'),
            ],
            't' => $this->labels([
                'title'             => __('news_offer.create_news_offer') ?: 'Create News / Offer',
                'list_title'        => __('news_offer.title') ?: 'News & Offers',
                'placeholder_title' => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_desc'  => __('placeholder.Enter_description') ?: 'Enter description',
                'file_help'         => 'Upload an image to feature with this entry.',
            ]),
        ]);
    }

    public function store(StoreNewsOfferRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success(__('news_offer.added_msg'), __('message.success'));
            return redirect()->route('news-offer.index');
        }
        Toastr::error(__('news_offer.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function edit($id)
    {
        $entity = $this->repo->get($id);
        if (!$entity) abort(404);
        return Inertia::render('Admin/NewsOffer/Form', [
            'mode'   => 'edit',
            'entity' => [
                'id'          => $entity->id,
                'title'       => $entity->title,
                'description' => strip_tags((string) $entity->description),
                'status'      => (int) $entity->status,
                'date'        => optional($entity->date)->format('Y-m-d') ?: (string) $entity->date,
                'image_url'   => $entity->image,
            ],
            'lookups' => ['statuses' => $this->statusOptions()],
            'urls' => [
                'submit' => route('news-offer.update', $entity->id),
                'cancel' => route('news-offer.index'),
            ],
            't' => $this->labels([
                'title'             => __('news_offer.edit_news_offer') ?: 'Edit News / Offer',
                'list_title'        => __('news_offer.title') ?: 'News & Offers',
                'placeholder_title' => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_desc'  => __('placeholder.Enter_description') ?: 'Enter description',
                'file_help'         => 'Upload to replace the existing image.',
            ]),
        ]);
    }

    public function update($id, UpdateNewsOfferRequest $request)
    {
        if ($this->repo->update($id, $request)) {
            Toastr::success(__('news_offer.update_msg'), __('message.success'));
            return redirect()->route('news-offer.index');
        }
        Toastr::error(__('news_offer.error_msg'), __('message.error'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        if ($this->repo->delete($id)) {
            Toastr::success(__('news_offer.delete_msg'), __('message.success'));
        } else {
            Toastr::error(__('news_offer.error_msg'), __('message.error'));
        }
        return redirect()->back();
    }

    private function statusOptions(): array
    {
        $opts = [];
        foreach ((array) trans('status') as $k => $v) {
            $opts[] = ['value' => (string) $k, 'label' => (string) $v];
        }
        return $opts;
    }

    private function paginateMeta($p): array
    {
        return [
            'current_page' => $p->currentPage(),
            'last_page'    => $p->lastPage(),
            'from'         => $p->firstItem(),
            'to'           => $p->lastItem(),
            'total'        => $p->total(),
            'prev_url'     => $p->previousPageUrl(),
            'next_url'     => $p->nextPageUrl(),
        ];
    }

    private function labels(array $extra = []): array
    {
        return array_merge([
            'add'        => __('levels.add') ?: 'Add',
            'edit'       => __('levels.edit') ?: 'Edit',
            'delete'     => __('levels.delete') ?: 'Delete',
            'actions'    => __('levels.actions') ?: 'Actions',
            'image'      => __('levels.file') ?: 'Image',
            'description'=> __('levels.description') ?: 'Description',
            'status'     => __('levels.status') ?: 'Status',
            'date'       => __('levels.date') ?: 'Date',
            'name_field' => __('levels.title') ?: 'Title',
            'save'       => __('levels.save') ?: 'Save',
            'update'     => __('levels.update') ?: 'Update',
            'cancel'     => __('levels.cancel') ?: 'Cancel',
            'back'       => __('levels.back') ?: 'Back',
            'prev'       => 'Prev',
            'next'       => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }
}
