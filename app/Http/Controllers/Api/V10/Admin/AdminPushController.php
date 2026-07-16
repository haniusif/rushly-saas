<?php

namespace App\Http\Controllers\Api\V10\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\PushNotificationService;
use App\Traits\ApiReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPushController extends Controller
{
    use ApiReturnFormatTrait;

    public function __construct(private PushNotificationService $push) {}

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('Invalid payload', ['message' => $validator->errors()], 422);
        }

        $request->merge(['topic' => $this->topicFor($request->user())]);
        return $this->push->fcmSubscribe($request);
    }

    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->responseWithError('Invalid payload', ['message' => $validator->errors()], 422);
        }

        $request->merge(['topic' => $this->topicFor($request->user())]);
        return $this->push->fcmUnsubscribe($request);
    }

    private function topicFor($user): string
    {
        return $user->email ?? ('user_' . $user->id);
    }
}
