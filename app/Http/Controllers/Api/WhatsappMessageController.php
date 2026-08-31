<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DuplicateWhatsappRecipientException;
use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsappSendRequest;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\WhatsappSubmissionService;

class WhatsappMessageController extends Controller
{
    public function store(WhatsappSendRequest $request, WhatsappSubmissionService $submissionService)
    {
        try {
            $message = $submissionService->submit(
                $request->validated(),
                (string) $request->attributes->get('client_ip', $request->ip())
            );
        } catch (DuplicateWhatsappRecipientException $exception) {
            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
                'code' => 'recipient_already_exists',
            ], 409);
        }

        $sent = $message->status === WhatsappMessage::STATUS_SENT;

        return response()->json([
            'success' => true,
            'status' => $message->status,
            'message' => $sent ? 'WhatsApp message sent.' : 'WhatsApp message accepted for delivery.',
            'data' => [
                'id' => $message->public_id,
                'status' => $message->status,
                'wait_reason' => $message->wait_reason,
                'next_attempt_at' => optional($message->next_attempt_at)->toIso8601String(),
            ],
        ], $sent ? 200 : 202);
    }
}
