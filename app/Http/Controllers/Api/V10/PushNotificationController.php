<?php

namespace App\Http\Controllers\Api\V10;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\PushNotificationService;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    protected $pushNotificationService;
    public function __construct(PushNotificationService $pushNotificationService )
    {
        $this->pushNotificationService = $pushNotificationService;
    }

    public function fcmSubscribe(Request $request)
    {
        $validation = Validator::make($request->all(),  [
            'device_token' => 'required',
            'topic' => 'nullable',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validation->errors(),
            ], 422);
        }

        $this->ensureTopic($request);
        return $this->pushNotificationService->fcmSubscribe($request);
    }

    public function fcmUnsubscribe(Request $request)
    {
        $validation = Validator::make($request->all(),  [
            'device_token' => 'required',
            'topic' => 'nullable',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validation->errors(),
            ], 422);
        }

        $this->ensureTopic($request);
        return $this->pushNotificationService->fcmUnsubscribe($request);
    }

    /**
     * If the caller didn't send an explicit topic, derive one from the
     * authenticated user (email, or `user_<id>` fallback). Keeps mobile
     * clients from having to know or guess the tenant's topic naming.
     */
    private function ensureTopic(Request $request): void
    {
        if (!blank($request->input('topic'))) {
            return;
        }
        $user = $request->user();
        $topic = $user->email ?? ($user ? 'user_' . $user->id : null);
        if ($topic !== null) {
            $request->merge(['topic' => $topic]);
        }
    }

}
