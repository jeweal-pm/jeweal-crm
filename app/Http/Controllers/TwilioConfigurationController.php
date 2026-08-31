<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTwilioConfigurationRequest;
use App\Services\Whatsapp\WhatsappDeliveryService;

class TwilioConfigurationController extends Controller
{
    public function edit(WhatsappDeliveryService $deliveryService)
    {
        return view('administrator.whatsapp.config.edit', [
            'config' => $deliveryService->configuration(),
        ]);
    }

    public function update(
        UpdateTwilioConfigurationRequest $request,
        WhatsappDeliveryService $deliveryService
    ) {
        $config = $deliveryService->configuration();
        $data = $request->validated();

        foreach (['account_sid', 'api_key_sid', 'api_key_secret'] as $credential) {
            if (blank($data[$credential] ?? null)) {
                unset($data[$credential]);
            }
        }

        $candidate = clone $config;
        $candidate->fill($data);
        if ($candidate->is_enabled && ! $candidate->isComplete()) {
            return back()
                ->withInput()
                ->withErrors(['is_enabled' => 'Complete all Twilio credentials before enabling delivery.']);
        }

        $config->update($data);

        return back()->with('success', 'Twilio WhatsApp configuration updated.');
    }
}
