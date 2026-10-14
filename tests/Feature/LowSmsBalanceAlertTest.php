<?php

namespace Tests\Feature;

use App\Mail\LowSmsBalance;
use App\Services\AcumbamailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The professional must hear about a low Acumbamail balance before reminders
 * stop going out — once per drop below the threshold, not every hour.
 */
class LowSmsBalanceAlertTest extends TestCase
{
    private const CREDITS_URL = 'https://acumbamail.com/api/1/getCreditsSMS/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.acumbamail.auth_token' => 'test-token',
            'services.acumbamail.low_sms_threshold' => 50,
        ]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            [
                'name' => 'Psicóloga',
                'email' => 'psicologa@ejemplo.com',
                'password' => bcrypt('secret'),
                'onboarding_completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'A medio registrar',
                'email' => 'sin-onboarding@ejemplo.com',
                'password' => bcrypt('secret'),
                'onboarding_completed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function fakeBalance(int $sms): void
    {
        Http::fake([self::CREDITS_URL => Http::response(['Creditos' => $sms * 100, 'sms_espana' => $sms])]);
    }

    public function test_it_alerts_when_the_balance_reaches_the_threshold(): void
    {
        Mail::fake();
        $this->fakeBalance(50);

        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();

        Mail::assertSent(LowSmsBalance::class, fn ($mail) => $mail->hasTo('psicologa@ejemplo.com') && $mail->sms === 50);
        Mail::assertSentCount(1);
    }

    public function test_it_does_not_alert_above_the_threshold(): void
    {
        Mail::fake();
        $this->fakeBalance(51);

        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_it_does_not_alert_twice_while_the_balance_stays_low(): void
    {
        Mail::fake();
        $this->fakeBalance(40);

        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();
        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();

        Mail::assertSentCount(1);
    }

    public function test_it_alerts_again_after_a_recharge_and_a_new_drop(): void
    {
        Mail::fake();

        $this->fakeBalance(40);
        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();

        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->fakeBalance(700);
        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();

        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->fakeBalance(30);
        $this->artisan('nimbus:check-sms-balance')->assertSuccessful();

        Mail::assertSentCount(2);
    }

    public function test_an_acumbamail_failure_sends_nothing(): void
    {
        Mail::fake();
        Http::fake([self::CREDITS_URL => Http::response('Unauthorized', 401)]);

        $this->artisan('nimbus:check-sms-balance')->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_dry_run_sends_nothing(): void
    {
        Mail::fake();
        $this->fakeBalance(10);

        $this->artisan('nimbus:check-sms-balance --dry-run')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_balance_falls_back_to_credits_when_sms_count_is_missing(): void
    {
        Http::fake([self::CREDITS_URL => Http::response(['Creditos' => 4250])]);

        $balance = app(AcumbamailService::class)->getSmsBalance();

        $this->assertSame(4250.0, $balance['credits']);
        $this->assertSame(42, $balance['sms']);
    }

    public function test_the_sms_test_page_shows_sms_instead_of_raw_credits(): void
    {
        Http::fake([self::CREDITS_URL => Http::response(['Creditos' => 70132, 'sms_espana' => 701])]);

        $this->actingAs(\App\Models\User::first())
            ->get(route('sms.test'))
            ->assertOk()
            ->assertSee('≈ 701', false)
            ->assertSee('70.132 créditos', false);
    }

    public function test_the_email_renders_the_remaining_sms(): void
    {
        $html = (new LowSmsBalance(37, 50))->render();

        $this->assertStringContainsString('37', $html);
        $this->assertStringContainsString('acumbamail.com', $html);
    }
}
