<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppOtpSender
{
    public function send(string $phone, string $code): void
    {
        if (config('services.whatsapp.mode') === 'log' && app()->environment('local', 'testing')) {
            Log::info('Development WhatsApp OTP', ['phone' => $phone, 'code' => $code]);

            return;
        }

        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $token = config('services.whatsapp.token');

        if (! $phoneNumberId || ! $token) {
            throw new RuntimeException('WhatsApp Cloud API credentials are not configured.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->post('https://graph.facebook.com/'.config('services.whatsapp.api_version').'/'.$phoneNumberId.'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => config('services.whatsapp.template'),
                    'language' => ['code' => config('services.whatsapp.language')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => $code]],
                    ]],
                ],
            ]);

        if ($response->failed()) {
            Log::error('WhatsApp OTP delivery failed', ['status' => $response->status()]);
            throw new RuntimeException('OTP WhatsApp gagal dikirim.');
        }
    }
}
