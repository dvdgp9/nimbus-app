<?php

namespace Tests\Unit;

use App\Services\SmsSegmentCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SmsSegmentCalculatorTest extends TestCase
{
    #[DataProvider('messageProvider')]
    public function test_it_calculates_acumbamail_segments(
        string $message,
        string $expectedEncoding,
        int $expectedUnits,
        int $expectedSegments,
    ): void {
        $result = SmsSegmentCalculator::analyse($message);

        $this->assertSame($expectedEncoding, $result['encoding']);
        $this->assertSame($expectedUnits, $result['units']);
        $this->assertSame($expectedSegments, $result['segments']);
    }

    public static function messageProvider(): array
    {
        return [
            'one standard part' => [str_repeat('a', 160), 'standard', 160, 1],
            'two standard parts' => [str_repeat('a', 161), 'standard', 161, 2],
            'standard extension counts twice' => [str_repeat('a', 158).'{}', 'standard', 162, 2],
            'one unicode part' => [str_repeat('a', 69).'á', 'unicode', 70, 1],
            'two unicode parts' => [str_repeat('a', 70).'ñ', 'unicode', 71, 2],
            'unicode concatenation boundary' => [str_repeat('á', 137), 'unicode', 137, 2],
            'three unicode parts' => [str_repeat('á', 138), 'unicode', 138, 3],
            'emoji uses two UTF-16 units' => [str_repeat('a', 69).'🙂', 'unicode', 71, 2],
        ];
    }
}
