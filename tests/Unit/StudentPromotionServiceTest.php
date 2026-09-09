<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Services\StudentPromotionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StudentPromotionServiceTest extends TestCase
{
    #[DataProvider('promotionCases')]
    public function test_school_levels_are_promoted_inside_their_own_institution(
        string $institutionCode,
        string $sourceClass,
        string $expectedAction,
        ?string $expectedClass,
        ?string $expectedLevel
    ): void {
        $institution = new Institution([
            'code' => $institutionCode,
            'type' => 'school',
        ]);

        $suggestion = (new StudentPromotionService())->suggestion(
            $institution,
            $sourceClass,
            strtoupper($institutionCode)
        );

        self::assertSame($expectedAction, $suggestion['suggested_action']);
        self::assertSame($expectedClass, $suggestion['target_class']);
        self::assertSame($expectedLevel, $suggestion['target_level']);
    }

    public static function promotionCases(): array
    {
        return [
            'MTs kelas 7 naik ke kelas 8 MTs' => ['mts', '7A', 'promote', '8A', 'MTs'],
            'MTs kelas IX lulus dari MTs' => ['mts', 'IX A', 'graduate', null, null],
            'MA kelas 10 naik ke kelas 11 MA' => ['ma', '10 IPA', 'promote', '11 IPA', 'MA'],
            'MA kelas XII lulus dari MA' => ['ma', 'XII IPS', 'graduate', null, null],
        ];
    }
}
