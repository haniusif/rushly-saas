<?php

namespace App\Http\Controllers\Backend;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PushNotification\StorePushNotificationRequest;
use App\Http\Services\PushNotificationService;
use App\Models\User;
use App\Repositories\PushNotification\PushNotificationInterface;
use App\Repositories\Role\RoleInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PushNotificationController extends Controller
{
    protected $repo;
    protected $role;
    public function __construct(PushNotificationInterface $repo, RoleInterface $role, PushNotificationService $pushNotificationService)
    {
        $this->repo = $repo;
        $this->role = $role;
    }

    public function index()
    {
        $paginator = $this->repo->all();

        $rows = collect($paginator->items())->map(function ($n) {
            return [
                'id'             => $n->id,
                'title'          => strip_tags((string) $n->title),
                'description'    => Str::limit(strip_tags((string) $n->description), 140),
                'image_url'      => $this->imageUrl($n),
                'audience_label' => $this->audienceLabel($n),
                'created_at'     => optional($n->created_at)->format('Y-m-d H:i'),
                'urls'           => [
                    'delete' => route('push-notification.delete', $n->id),
                ],
            ];
        })->values();

        return Inertia::render('Admin/PushNotification/Index', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'prev_url'     => $paginator->previousPageUrl(),
                'next_url'     => $paginator->nextPageUrl(),
            ],
            'permissions' => [
                'create' => hasPermission('push_notification_create'),
                'delete' => hasPermission('push_notification_delete'),
            ],
            'urls' => [
                'index'  => route('push-notification.index'),
                'create' => route('push-notification.create'),
            ],
            't' => $this->labels([
                'title'          => __('push-notification.title') ?: 'Push Notification',
                'list'           => __('levels.list') ?: 'List',
                'no_rows'        => 'No notifications yet.',
                'delete_confirm' => 'Delete this notification?',
            ]),
        ]);
    }

    public function create()
    {
        $roles = $this->role->getRole();

        $roleOptions = collect($roles)
            ->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name])
            ->prepend(['value' => 'all', 'label' => __('All Role') ?: 'All roles'])
            ->values();

        return Inertia::render('Admin/PushNotification/Create', [
            'lookups' => [
                'roles' => $roleOptions,
            ],
            'urls' => [
                'store'      => route('push-notification.store'),
                'cancel'     => route('push-notification.index'),
                'user_search' => route('push-notification.users'),
            ],
            't' => $this->labels([
                'title'              => __('push-notification.create_push_notification') ?: 'Create Push Notification',
                'list_title'         => __('push-notification.title') ?: 'Push Notification',
                'add'                => __('levels.add') ?: 'Create',
                'save'               => __('levels.save') ?: 'Save',
                'cancel'             => __('levels.cancel') ?: 'Cancel',
                'placeholder_title'  => __('placeholder.Enter_title') ?: 'Enter title',
                'placeholder_desc'   => __('placeholder.Enter_description') ?: 'Enter description',
                'placeholder_user'   => __('menus.select') ? (__('menus.select') . ' ' . (__('user.title') ?: 'user')) : 'Select user',
                'file_help'          => 'PNG image, up to 5 MB.',
                'user_optional_hint' => 'Optional. Search a specific user to target.',
            ]),
        ]);
    }

    public function store(StorePushNotificationRequest $request)
    {
        if ($this->repo->store($request)) {
            Toastr::success(__('push-notification.added_msg'), __('message.success'));
            return redirect()->route('push-notification.index');
        }
        Toastr::error(__('push-notification.error_msg'), __('message.success'));
        return redirect()->back();
    }

    public function destroy($id)
    {
        if ($this->repo->delete($id)) {
            Toastr::success(__('push-notification.delete_msg'), __('message.success'));
        } else {
            Toastr::error(__('push-notification.error_msg'), __('message.error'));
        }
        return redirect()->back();
    }

    public function Users(Request $request)
    {
        $users = User::companywise()
            ->where('name', 'like', '%' . $request->input('search', '') . '%')
            ->when($request->input('userType') && $request->input('userType') !== 'all', function ($q) use ($request) {
                $q->where('user_type', $request->input('userType'));
            })
            ->limit(15)
            ->get(['id', 'name']);

        return response()->json(
            $users->map(fn ($u) => ['id' => $u->id, 'text' => $u->name])->values()
        );
    }

    private function imageUrl($n): string
    {
        $path = optional($n->upload)->original;
        if (is_string($path) && $path !== '' && file_exists(public_path($path))) {
            return static_asset($path);
        }
        return static_asset('images/default/logo.png');
    }

    private function audienceLabel($n): string
    {
        if ($n->user_id && $n->user) {
            return $n->user->name;
        }
        if ($n->type === 'all') {
            return __('All user') ?: 'All users';
        }
        $key = 'userType.' . $n->type;
        $label = __($key);
        return $label === $key ? (string) $n->type : (string) $label;
    }

    private function labels(array $extra = []): array
    {
        return array_merge([
            'edit'    => __('levels.edit') ?: 'Edit',
            'delete'  => __('levels.delete') ?: 'Delete',
            'actions' => __('levels.actions') ?: 'Actions',
            'add'     => __('levels.add') ?: 'Add',
            'image'   => __('levels.image') ?: 'Image',
            'role'    => __('levels.role') ?: 'Role',
            'user'    => __('user.title') ?: (__('levels.user') ?: 'User'),
            'description' => __('levels.description') ?: 'Description',
            'created_at'  => __('levels.created_at') ?: 'Created',
            'audience'    => 'Audience',
            'showing_results' => 'Showing :from – :to of :total',
            'prev' => 'Prev',
            'next' => 'Next',
            'all_users'  => __('All user') ?: 'All users',
            'back' => __('levels.back') ?: 'Back',
        ], $extra);
    }
}
