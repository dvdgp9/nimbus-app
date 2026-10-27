<?php

namespace Tests\Unit;

use App\Exceptions\SmsDeliveryUncertainException;
use App\Services\AcumbamailService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcumbamailDeliverySafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.acumbamail.auth_token' => 'test-token']);
        Http::preventStrayRequests();
    }

    public function test_a_send_timeout_is_never_retried(): void
    {
        $requests = 0;
        Http::fake(function () use (&$requests) {
            $requests++;
            throw new ConnectionException('Response was lost after server acceptance');
        });

        try {
            app(AcumbamailService::class)->sendSMS('600111222', 'Recordatorio');
            $this->fail('Delivery must be classified as uncertain.');
        } catch (SmsDeliveryUncertainException $e) {
            $this->assertSame(1, $requests);
            $this->assertInstanceOf(ConnectionException::class, $e->getPrevious());
        }
    }

    #[DataProvider('uncertainResponses')]
    public function test_an_ambiguous_response_is_not_a_retryable_rejection(mixed $body, int $status): void
    {
        Http::fake(['*' => Http::response($body, $status)]);

        try {
            app(AcumbamailService::class)->sendSMS('600111222', 'Recordatorio');
            $this->fail('Delivery must be classified as uncertain.');
        } catch (SmsDeliveryUncertainException $e) {
            Http::assertSentCount(1);
        }
    }

    public static function uncertainResponses(): array
    {
        return [
            'gateway timeout' => ['Gateway timeout', 504],
            'nginx 404' => ['Not Found', 404],
            'invalid JSON' => ['not-json', 200],
            'empty JSON' => [[], 200],
            'malformed message result' => [['messages' => ['not-an-object']], 200],
            'missing provider id' => [['messages' => [['status' => 0]]], 200],
            'invalid status' => [['messages' => [['status' => 'invalid', 'id' => 4]]], 200],
            'unexplained failure' => [['messages' => [['status' => 2]]], 200],
        ];
    }

    public function test_an_explicit_provider_rejection_can_be_retried_later(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['status' => 2, 'error' => 'Not enough credits']]], 400)]);

        try {
            app(AcumbamailService::class)->sendSMS('600111222', 'Recordatorio');
            $this->fail('The provider explicitly rejected it.');
        } catch (\Exception $e) {
            $this->assertNotInstanceOf(SmsDeliveryUncertainException::class, $e);
            $this->assertStringContainsString('Not enough credits', $e->getMessage());
            Http::assertSentCount(1);
        }
    }

    public function test_read_only_balance_requests_still_retry(): void
    {
        Http::fakeSequence()->push('Gateway error', 503)->push(['Creditos' => 5000, 'sms_espana' => 50]);
        $service = new class extends AcumbamailService
        {
            protected int $retryBackoffMs = 0;
        };

        $this->assertSame(['credits' => 5000.0, 'sms' => 50], $service->getSmsBalance());
        Http::assertSentCount(2);
    }

    public function test_a_report_error_is_not_treated_as_an_empty_report(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Not authorized'], 401)]);
        $this->expectException(\Exception::class);

        app(AcumbamailService::class)->getSmsReport(now()->subDay(), now());
    }
}
