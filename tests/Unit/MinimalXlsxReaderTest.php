<?php

namespace Tests\Unit;

use App\Support\MinimalXlsxReader;
use Tests\TestCase;

class MinimalXlsxReaderTest extends TestCase
{
    public function test_it_reads_header_and_data_rows_from_a_real_xlsx_file(): void
    {
        $rows = MinimalXlsxReader::read(base_path('tests/Fixtures/sample-tally-export.xlsx'));

        $this->assertSame(
            ['Submission ID', 'session_token', 'K1', 'A1', 'B1'],
            $rows[0],
        );
        $this->assertSame(
            ['sub_fixture_1', 'fixture-token-aaa', 'Jawaban Benar Fixture', '4', '2'],
            $rows[1],
        );
    }
}
