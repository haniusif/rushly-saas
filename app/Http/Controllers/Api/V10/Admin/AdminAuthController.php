<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Resources\v10\Admin\AdminUserResource;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    use ApiReturnFormatTrait;

    private const ADMIN_TYPES = [
        UserType::ADMIN,
        UserType::SUPER_ADMIN,
        UserType::INCHARGE,
        UserType::HUB,
    ];

    /**
     * Sign in any back-office user (admin / super_admin / incharge / hub).
     * Body: { email, password }
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->responseWithError(__('auth.credentials_msg'), ['message' => $validator->errors()], 422);
        }

        $attempt = Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if (!$attempt) {
            return $this->responseWithError(__('auth.credentials_msg'), [], 401);
        }

        $user = Auth::user();

        if (!in_array((int) $user->user_type, self::ADMIN_TYPES, true)) {
            return $this->responseWithError(__('auth.credentials_msg'), [], 401);
        }

        $token = $user->createToken('admin:' . $user->email)->plainTextToken;

        return $this->responseWithSuccess(
            __('auth.signin_msg'),
            ['token' => $token, 'user' => new AdminUserResource($user)],
            200
        );
    }

    public function profile()
    {
        return $this->responseWithSuccess(
            __('auth.profile_msg'),
            ['user' => new AdminUserResource(auth()->user())],
            200
        );
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->responseWithSuccess(__('auth.token_delete'), [], 200);
    }
}
