<?php

namespace App\Http\Controllers\Internal;

use App\Actions\Inbox\StoreIncomingMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\StoreMessageRequest;
use App\Models\Inbox;
use Illuminate\Http\JsonResponse;

class StoreMessageController extends Controller
{
    /**
     * Store a message delivered by the SMTP service.
     */
    public function __invoke(StoreMessageRequest $request, StoreIncomingMessage $storeIncomingMessage): JsonResponse
    {
        $message = $storeIncomingMessage->handle(
            Inbox::query()->findOrFail($request->integer('inbox_id')),
            $request->validated(),
            $request->file('raw'),
            $request->file('attachments', []),
        );

        return response()->json(['id' => $message->id], 201);
    }
}
