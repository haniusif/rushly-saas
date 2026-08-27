<?php

namespace App\Http\Controllers\Backend\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Shared plumbing for the countries / cities / areas admin modules.
 *
 * These three are geographic REFERENCE data, and they differ from every other
 * module in the admin in one way that shapes everything here: the tables carry
 * no `company_id`. Every tenant reads the same rows, so an edit made by one
 * tenant is visible to all of them. The pages say so, and deletes are guarded
 * against rows that anything still points at.
 */
trait ManagesReferenceData
{
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

    /**
     * Labels every reference-data screen needs. Callers merge their own on top.
     */
    protected function labels(array $extra = []): array
    {
        return array_merge([
            'add'      => __('levels.add') ?: 'Add',
            'edit'     => __('levels.edit') ?: 'Edit',
            'delete'   => __('levels.delete') ?: 'Delete',
            'actions'  => __('levels.actions') ?: 'Actions',
            'save'     => __('levels.save') ?: 'Save',
            'update'   => __('levels.update') ?: 'Update',
            'cancel'   => __('levels.cancel') ?: 'Cancel',
            'back'     => __('levels.back') ?: 'Back',
            'list'     => __('levels.list') ?: 'List',
            'status'   => __('levels.status') ?: 'Status',
            'active'   => __('levels.active') ?: 'Active',
            'inactive' => __('levels.inactive') ?: 'Inactive',
            'search'   => __('levels.search') ?: 'Search',
            'all'      => __('levels.all') ?: 'All',
            'name'     => __('levels.name') ?: 'Name',
            'position' => __('levels.position') ?: 'Sorting',
            'prev'     => 'Prev',
            'next'     => 'Next',
            'showing_results' => 'Showing :from – :to of :total',
            // Rendered as a standing notice on each of these screens.
            'shared_notice' => 'Shared reference data — these records are used by every tenant, not just this one.',
        ], $extra);
    }

    /**
     * Refuse to delete a row that something still references.
     *
     * There are no database-level foreign keys on these columns, so nothing
     * would stop the delete — it would simply leave parcels and merchants
     * pointing at an id that no longer resolves. Returns a human-readable
     * reason, or null when the row is safe to remove.
     *
     * @param  array<string,string>  $references  table => column
     */
    protected function blockingReferences(array $references, $id): ?string
    {
        $blocking = [];

        foreach ($references as $table => $column) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            $count = DB::table($table)->where($column, $id)->count();
            if ($count > 0) {
                $blocking[] = $count . ' ' . str_replace('_', ' ', $table);
            }
        }

        if (! $blocking) {
            return null;
        }

        return 'Still referenced by ' . implode(', ', $blocking) . '. Deactivate it instead of deleting.';
    }
}
