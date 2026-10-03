<?php

namespace App\Http\Controllers\Backend\Wms\Concerns;

/**
 * Helpers shared by every WMS Inertia index page. Keeps each controller's
 * index() short and the paginator/label shapes identical across modules.
 */
trait RendersInertiaIndex
{
    /** Standard pagination meta the React side expects. */
    protected function paginateMeta($p): array
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

    /** Common label keys; merge per-page extras on top. */
    protected function indexLabels(array $extra = []): array
    {
        return array_merge([
            'list'    => __('levels.list') ?: 'List',
            'add'     => __('levels.add') ?: 'Add',
            'edit'    => __('levels.edit') ?: 'Edit',
            'view'    => __('levels.view') ?: 'View',
            'open'    => __('levels.open') ?: 'Open',
            'delete'  => __('levels.delete') ?: 'Delete',
            'actions' => __('levels.actions') ?: 'Actions',
            'filter'  => __('levels.filter') ?: 'Filter',
            'clear'   => __('levels.clear') ?: 'Clear',
            'status'  => __('levels.status') ?: 'Status',
            'all'     => __('levels.all') ?: 'All',
            'no_rows' => __('levels.no_data_found') ?: 'No data found',
            'showing_results' => 'Showing :from – :to of :total',
        ], $extra);
    }

    /**
     * Defensive map for `merchant->all()` / `deliveryman->all()` / `hub->all()`
     * — these may return a paginator or a Collection depending on the repo. We
     * iterate items() either way.
     */
    protected function lookupRows($source, callable $shape): array
    {
        $items = $source instanceof \Illuminate\Pagination\AbstractPaginator
            ? $source->items()
            : $source;
        return collect($items)->map($shape)->values()->all();
    }
}
