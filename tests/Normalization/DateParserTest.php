<?php

namespace Nexus\Tests\Normalization;

use Nexus\Normalization\DateParser;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    public function test_extract_year_from_int(): void
    {
        $this->assertEquals(2023, DateParser::extractYear(2023));
    }

    public function test_extract_year_from_string(): void
    {
        $this->assertEquals(2023, DateParser::extractYear('2023'));
        $this->assertEquals(2023, DateParser::extractYear('2023-05-15'));
        $this->assertEquals(2023, DateParser::extractYear('May 2023'));
    }

    public function test_extract_year_from_dict(): void
    {
        $this->assertEquals(2023, DateParser::extractYear(['year' => 2023]));
        $this->assertEquals(2023, DateParser::extractYear(['Year' => 2023]));
    }

    public function test_extract_year_invalid(): void
    {
        $this->assertNull(DateParser::extractYear(null));
        $this->assertNull(DateParser::extractYear('not a year'));
        $this->assertNull(DateParser::extractYear(1800));
        $this->assertNull(DateParser::extractYear(2200));
    }

    public function test_parse_date_ymd(): void
    {
        $date = DateParser::parseDate('2023-05-15');

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2023', $date->format('Y'));
        $this->assertEquals('05', $date->format('m'));
        $this->assertEquals('15', $date->format('d'));
    }

    public function test_parse_date_with_time(): void
    {
        $date = DateParser::parseDate('2023-05-15T10:30:00');

        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('10', $date->format('H'));
        $this->assertEquals('30', $date->format('i'));
    }

    public function test_parse_date_invalid(): void
    {
        $this->assertNull(DateParser::parseDate(null));
        $this->assertNull(DateParser::parseDate('invalid'));
    }
}
