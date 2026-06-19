<?php

namespace Tests\Unit;

use App\Models\MessageTemplate;
use App\Services\AcumbamailService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsLineBreakTest extends TestCase
{
    public function test_template_parsing_preserves_line_breaks(): void
    {
        $template = new MessageTemplate([
            'channel' => 'sms',
            'body' => "Hola {{patient_first_name}}\n\nConfirma aquí:\n{{confirm_link}}",
        ]);

        $this->assertSame(
            "Hola Clara\n\nConfirma aquí:\nhttps://nimbus.test/confirmar",
            $template->parse([
                'patient_first_name' => 'Clara',
                'confirm_link' => 'https://nimbus.test/confirmar',
            ]),
        );
    }

    public function test_acumbamail_payload_preserves_line_breaks(): void
    {
        config([
            'services.acumbamail.auth_token' => 'test-token',
            'services.acumbamail.sender' => 'Nimbus',
        ]);

        Http::fake([
            'https://acumbamail.com/api/1/sendSMS/' => Http::response([
                'messages' => [[
                    'status' => 0,
                    'id' => 7842,
                ]],
            ]),
        ]);

        $message = "Hola Clara\n\nConfirma aquí:\nhttps://nimbus.test/confirmar";

        (new AcumbamailService())->sendSMS('+34600111222', $message);

        Http::assertSent(function (Request $request) use ($message): bool {
            $messages = json_decode($request['messages'], true, flags: JSON_THROW_ON_ERROR);

            return $messages[0]['body'] === $message;
        });
    }
}
