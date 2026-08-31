<?php

namespace App\Http\Controllers;

use App\Models\WhatsappMessage;
use App\Services\Whatsapp\WhatsappDeliveryService;
use Illuminate\Http\Request;

class WhatsappMessageAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', WhatsappMessage::STATUS_WAITING);
        $query = WhatsappMessage::query()->latest();

        if (in_array($status, [
            WhatsappMessage::STATUS_WAITING,
            WhatsappMessage::STATUS_SENT,
            WhatsappMessage::STATUS_FAILED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($query) use ($search) {
                $query->where('recipient_normalized', 'like', "%{$search}%")
                    ->orWhere('source_reference', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return view('administrator.whatsapp.messages.index', [
            'messages' => $query->paginate(25)->appends($request->query()),
            'activeStatus' => $status,
            'search' => $search ?? '',
            'counts' => WhatsappMessage::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }

    public function retry(int $id, WhatsappDeliveryService $deliveryService)
    {
        $message = WhatsappMessage::query()->findOrFail($id);
        abort_if($message->status === WhatsappMessage::STATUS_SENT, 422, 'Sent messages cannot be retried.');

        $config = $deliveryService->configuration();
        $message->update([
            'status' => WhatsappMessage::STATUS_WAITING,
            'wait_reason' => WhatsappMessage::WAIT_MANUAL_RETRY,
            'attempts' => 0,
            'max_attempts' => $config->max_retry_attempts,
            'next_attempt_at' => now(),
            'provider_error_code' => null,
            'provider_error_message' => null,
            'failed_at' => null,
        ]);

        return back()->with('success', 'The message was queued for retry.');
    }

    public function destroy(int $id)
    {
        WhatsappMessage::query()->findOrFail($id)->delete();

        return back()->with('success', 'The WhatsApp record was deleted. The number can be submitted again.');
    }
}
