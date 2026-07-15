<?php

namespace App\Http\Controllers;

use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Services\BrevoWebhookEventHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrevoAudienceWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $secret,
        BrevoWebhookEventHandler $handler,
    ): JsonResponse {
        $setting = AudienceBrevoSetting::current();

        abort_unless(
            $setting->webhook_secret && hash_equals($setting->webhook_secret, $secret),
            404,
        );

        $handler->handle($request->all());

        return response()->json(['ok' => true]);
    }
}
