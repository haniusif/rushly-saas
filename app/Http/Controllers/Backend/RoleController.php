<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\SuperAdminPermission;
use App\Repositories\Role\RoleInterface;
use Brian2694\Toastr\Facades\Toastr;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Arr;

class RoleController extends Controller
{
    protected $repo;

    public function __construct(RoleInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index()
    {
        $roles = $this->repo->all();

        return \Inertia\Inertia::render('Admin/Roles/Index', [
            'roles' => [
                'data' => collect($roles->items())->map(fn ($r) => [
                    'id'          => $r->id,
                    'name'        => $r->name,
                    'slug'        => $r->slug,
                    'permissions' => is_array($r->permissions) ? count($r->permissions) : 0,
                    'users'       => \App\Models\User::withoutGlobalScopes()->where('role_id', $r->id)->count(),
                    'status'      => (int) $r->status,
                    'urls'        => [
                        'edit'   => route('roles.edit', $r->id),
                        'delete' => route('role.delete', $r->id),
                    ],
                ])->values(),
                'links'        => $roles->linkCollection()->values(),
                'current_page' => $roles->currentPage(),
                'last_page'    => $roles->lastPage(),
                'total'        => $roles->total(),
            ],
            'permissions' => [
                'create' => hasPermission('role_create'),
                'update' => hasPermission('role_update'),
                'delete' => hasPermission('role_delete'),
            ],
            'urls' => ['create' => route('roles.create')],
            't'    => $this->translations() + ['title' => __('role.title') ?: 'Roles'],
        ]);
    }

    public function create()
    {
        return \Inertia\Inertia::render('Admin/Roles/Form', [
            'mode'   => 'create',
            'role'   => null,
            'groups' => $this->permissionGroups(null),
            'urls'   => [
                'submit' => route('roles.store'),
                'index'  => route('roles.index'),
            ],
            't' => $this->translations() + ['title' => __('levels.add') . ' ' . (__('role.title') ?: 'Role')],
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        if($this->repo->store($request)){
            // Flash via ->with() so HandleInertiaRequests exposes it as
            // props.flash for the FlashBanner. Toastr writes to a legacy
            // session key the Inertia frontend never reads.
            return redirect()->route('roles.index')->with('success', __('Role successfully added.'));
        }

        return redirect()->back()->withInput()->with('error', __('Something went wrong.'));
    }

    public function edit($id)
    {
        $role = $this->repo->get($id);
        abort_unless($role, 404);

        return \Inertia\Inertia::render('Admin/Roles/Form', [
            'mode' => 'edit',
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'slug'        => $role->slug,
                'status'      => (int) $role->status,
                'permissions' => is_array($role->permissions) ? array_values($role->permissions) : [],
            ],
            'groups' => $this->permissionGroups($role->slug),
            'urls'   => [
                'submit' => route('roles.update'),
                'index'  => route('roles.index'),
            ],
            't' => $this->translations() + ['title' => __('levels.edit') . ' ' . (__('role.title') ?: 'Role')],
        ]);
    }

    public function update(UpdateRoleRequest $request)
    {
        if($this->repo->update($request->id, $request)){
            return redirect()->route('roles.index')->with('success', __('Role successfully updated.'));
        }

        return redirect()->back()->withInput()->with('error', __('Something went wrong.'));
    }

    public function destroy($id)
    {
        if(env('DEMO')):
            return redirect()->back()->with('error', __('Delete system is disable for the demo mode.'));
        endif;

        if($this->repo->delete($id)):
            return back()->with('success', __('Role successfully deleted.'));
        else:
            return back()->with('error', __('Something went wrong!'));
        endif;
    }

    /**
     * Permission modules for the picker, MERGED by attribute.
     *
     * The permissions table holds 188 rows across only ~70 distinct attribute
     * names — 118 of them are duplicates of a name already present. The Blade
     * form looped the raw rows, so the picker rendered the same module over
     * and over. Merging the keyword maps per attribute collapses that to one
     * row per module, and is also what makes the checkbox set match the
     * catalogue a role is actually built from.
     */
    private function permissionGroups(?string $slug): array
    {
        $merged = [];

        foreach ($this->repo->permissions($slug) as $row) {
            $attr = strtolower((string) $row->attribute);
            $keys = is_array($row->keywords) ? $row->keywords : (json_decode((string) $row->keywords, true) ?: []);
            $merged[$attr] = ($merged[$attr] ?? []) + $keys;
        }

        return collect($merged)->map(fn ($keys, $attr) => [
            'key'   => $attr,
            'label' => __('permissions.' . $attr) === 'permissions.' . $attr
                        ? ucwords(str_replace('_', ' ', $attr))
                        : __('permissions.' . $attr),
            'items' => collect($keys)->map(fn ($perm, $k) => [
                'value' => $perm,
                'label' => __('permissions.' . $k) === 'permissions.' . $k
                            ? ucwords(str_replace('_', ' ', (string) $k))
                            : __('permissions.' . $k),
            ])->values(),
        ])->values()->all();
    }

    private function translations(): array
    {
        return [
            'title_index'     => __('role.title') ?: 'Roles',
            'name'            => __('levels.name') ?: 'Name',
            'slug'            => __('levels.slug') ?: 'Slug',
            'permission'      => __('levels.permission') ?: 'Permissions',
            'status'          => __('levels.status') ?: 'Status',
            'actions'         => __('levels.actions') ?: 'Actions',
            'users'           => 'Users',
            'active'          => __('status.1') ?: 'Active',
            'inactive'        => __('status.0') ?: 'Inactive',
            'add'             => __('levels.add') ?: 'Add',
            'edit'            => __('levels.edit') ?: 'Edit',
            'delete'          => __('levels.delete') ?: 'Delete',
            'save'            => __('levels.save') ?: 'Save',
            'cancel'          => __('levels.cancel') ?: 'Cancel',
            'saving'          => 'Saving…',
            'delete_confirm'  => 'Delete this role?',
            'no_rows'         => 'No roles yet.',
            'modules'         => __('permissions.modules') ?: 'Modules',
            'permissions'     => __('permissions.permissions') ?: 'Permissions',
            'select_all'      => 'Select all',
            'clear_all'       => 'Clear all',
            'selected'        => 'selected',
            'search'          => 'Filter modules…',
            'back'            => 'Back to roles',
            'in_use_warning'  => 'This role is assigned to users; deleting it leaves them without a role.',
        ];
    }

    public function changeStatus(Request $request)
    {
        $result = $this->repo->status($request->id);
        return response()->json(['success'=>$result]);
    }
}
