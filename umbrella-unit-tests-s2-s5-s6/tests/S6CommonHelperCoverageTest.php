<?php

declare(strict_types=1);

namespace UmbrellaTests;

final class S6CommonHelperCoverageTest extends UmbrellaTestCase
{
    /** @test TC_S6_60 */
    public function test_TC_S6_60_htmlchars_encodesQuotesAndTags(): void
    {
        $output = htmlchars('<b>"x"</b>');
        $this->assertSame('&lt;b&gt;&quot;x&quot;&lt;/b&gt;', $output);
    }

    /** @test TC_S6_61 */
    public function test_TC_S6_61_truncateString_appliesEllipsisAtMaxLength(): void
    {
        $output = truncate_string('abcdef', 5, '...', true);
        $this->assertSame('ab...', $output);
    }

    /** @test TC_S6_62 */
    public function test_TC_S6_62_urlSlug_convertsToSeoLowercase(): void
    {
        $output = url_slug(' Xin Chao 2026!! ');
        $this->assertSame('xin-chao-2026', $output);
    }

    /** @test TC_S6_63 */
    public function test_TC_S6_63_formatPrice_regularAndZeroDecimalModes(): void
    {
        $this->assertSame('12.<sup>50</sup>', format_price(12.5));
        $this->assertSame(13.0, format_price(12.6, true));
    }

    /** @test TC_S6_64 */
    public function test_TC_S6_64_getTimezones_containsUtcAndAsiaHoChiMinh(): void
    {
        $list = getTimezones();
        $this->assertArrayHasKey('UTC', $list);
        $this->assertArrayHasKey('Asia/Ho_Chi_Minh', $list);
    }

    /** @test TC_S6_65 */
    public function test_TC_S6_65_isValidDate_recognizesValidAndInvalid(): void
    {
        $this->assertTrue(isValidDate('2026-05-12 10:30:00'));
        $this->assertFalse(isValidDate('2026-02-30 10:30:00'));
    }

    /** @test TC_S6_66 */
    public function test_TC_S6_66_textInitials_returnsExpectedCharacters(): void
    {
        $this->assertSame('NV', textInitials('Nguyen Van A', 2));
        $this->assertSame('N', textInitials('Nguyen', 1));
    }

    /** @test TC_S6_67 */
    public function test_TC_S6_67_randomAndReadableHelpers_returnExpectedFormats(): void
    {
        $this->assertSame(8, strlen(readableRandomString(8)));
        $this->assertSame(10, strlen(generateRandomString(10)));
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}\sK$/', readableNumber(12345));
    }

    /** @test TC_S6_68 */
    public function test_TC_S6_68_readableFileSize_formatsCommonUnits(): void
    {
        $this->assertSame('1kB', readableFileSize(1024, 0));
        $this->assertSame('2kB', readableFileSize(2048, 0));
    }

    /** @test TC_S6_69 */
    public function test_TC_S6_69_numericAndStringValidators_workAsExpected(): void
    {
        $this->assertSame(1, isNumber('01234'));
        $this->assertSame(0, isNumber('12a34'));
        $this->assertSame(1, isVietnameseName('Nguyen Van A'));
        $this->assertSame(0, isVietnameseName('Nguyen@123'));
        $this->assertSame(1, isAddress('12 Tran Hung Dao, Q1'));
        $this->assertSame(0, isAddress('@@@###'));
        $this->assertSame(1, isVietnameseHospital('Benh Vien Bach Mai'));
        $this->assertSame(0, isVietnameseHospital('BV@@@'));
    }

    /** @test TC_S6_70 */
    public function test_TC_S6_70_isValidJson_detectsSyntaxErrors(): void
    {
        $this->assertTrue(isValidJSON('{"ok":true}'));
        $this->assertFalse(isValidJSON('{"ok":true'));
    }

    /** @test TC_S6_71 */
    public function test_TC_S6_71_isZeroDecimalCurrency_handlesKnownCodes(): void
    {
        $this->assertTrue(isZeroDecimalCurrency('VND'));
        $this->assertFalse(isZeroDecimalCurrency('USD'));
    }

    /** @test TC_S6_72 */
    public function test_TC_S6_72_isBirthdayValid_rejectsFutureDate(): void
    {
        $future = date('Y-m-d', strtotime('+1 day'));
        $msg = isBirthdayValid($future);
        $this->assertNotSame('', $msg);
    }

    /** @test TC_S6_73 */
    public function test_TC_S6_73_appointmentDateValidation_checksCalendarAndPastDate(): void
    {
        $this->assertNotSame('', isAppointmentDateValid('2026-02-30'));
        $this->assertNotSame('', isAppointmentDateValid(date('Y-m-d', strtotime('-1 day'))));
    }

    /** @test TC_S6_74 */
    public function test_TC_S6_74_appointmentHourValidation_rejectsOutsideWorkingHours(): void
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $msg = isAppointmentHourValid('20:10', $tomorrow);
        $this->assertStringContainsString('working hours', strtolower($msg));
    }

    /** @test TC_S6_75 */
    public function test_TC_S6_75_appointmentTimeValidation_acceptsBoundarySevenAmTomorrow(): void
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $msg = isAppointmentTimeValid($tomorrow . ' 07:00');
        $this->assertSame('', $msg);
    }
}

