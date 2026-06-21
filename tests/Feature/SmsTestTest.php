<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_the_sms_test_page(): void
    {
        config(['services.acumbamail.auth_token' => 'test-token']);
        Http::fake([
            'https://acumbamail.com/api/1/getCreditsSMS/' => Http::response(['Creditos' => 100]),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('sms.test'))
            ->assertOk()
            ->assertSee('Enviar SMS de prueba');
    }

    public function test_sms_test_sends_a_normalized_number_to_acumbamail(): void
    {
        config([
            'services.acumbamail.auth_token' => 'test-token',
            'services.acumbamail.sender' => 'Nimbus',
        ]);
        Http::fake([
            'https://acumbamail.com/api/1/sendSMS/' => Http::response([
                'messages' => [['status' => '0', 'id' => 7842]],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('sms.send'), [
                'phone' => '34600111222',
                'message' => 'Prueba Nimbus',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Http::assertSent(function ($request): bool {
            $messages = json_decode($request['messages'], true, flags: JSON_THROW_ON_ERROR);

            return $messages[0]['recipient'] === '+34600111222'
                && $messages[0]['body'] === 'Prueba Nimbus';
        });
    }
}
