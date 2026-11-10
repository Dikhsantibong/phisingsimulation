<?php

namespace App\Support;

use PharData;
use RuntimeException;
use Throwable;

/**
 * Reads the first worksheet of a .xlsx file into a plain array of rows
 * (header + data, as strings), without any third-party spreadsheet library.
 *
 * .xlsx is a ZIP archive of XML. This reads it via PharData (bundled with
 * PHP, works even when the `zip` extension isn't installed) and only
 * supports what a plain data export (e.g. Tally's own "Export as Excel")
 * produces: a single sheet, shared or inline strings, no formulas. It is
 * not a general-purpose spreadsheet reader.
 */
class MinimalXlsxReader
{
    /**
     * @return array<int, array<int, string|null>>
     */
    public static function read(string $path): array
    {
        // PharData only recognises a ZIP by its file extension, so the
        // uploaded temp file (which has no extension) needs a .zip copy.
        $zipPath = $path.'.zip';
        copy($path, $zipPath);

        try {
            $archive = new PharData($zipPath);
        } catch (Throwable) {
            @unlink($zipPath);

            throw new RuntimeException('Unable to open the .xlsx file.');
        }

        $sharedStrings = self::readSharedStrings($archive);

        try {
            $sheetXml = $archive['xl/worksheets/sheet1.xml']->getContent();
        } catch (Throwable) {
            @unlink($zipPath);

            throw new RuntimeException('The .xlsx file has no readable worksheet.');
        }

        @unlink($zipPath);

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            throw new RuntimeException('The .xlsx worksheet could not be parsed.');
        }

        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            $maxColumnIndex = -1;

            foreach ($row->c as $cell) {
                $columnIndex = self::columnIndex((string) $cell['r']);
                $maxColumnIndex = max($maxColumnIndex, $columnIndex);
                $cells[$columnIndex] = self::cellValue($cell, $sharedStrings);
            }

            $line = [];
            for ($i = 0; $i <= $maxColumnIndex; $i++) {
                $line[] = $cells[$i] ?? null;
            }

            $rows[] = $line;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private static function readSharedStrings(PharData $archive): array
    {
        try {
            $xml = $archive['xl/sharedStrings.xml']->getContent();
        } catch (Throwable) {
            return [];
        }

        $data = simplexml_load_string($xml);

        if ($data === false) {
            return [];
        }

        $strings = [];

        foreach ($data->si as $si) {
            // A rich-text string is split into multiple <r><t> runs; a plain one is a single <t>.
            $strings[] = isset($si->r)
                ? implode('', array_map(fn ($run) => (string) $run->t, iterator_to_array($si->r)))
                : (string) $si->t;
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private static function cellValue(\SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            $index = (int) $cell->v;

            return $sharedStrings[$index] ?? null;
        }

        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : null;
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    /**
     * Convert a cell reference like "AB12" to a zero-based column index.
     */
    private static function columnIndex(string $cellRef): int
    {
        $letters = preg_replace('/\d/', '', $cellRef);
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - ord('A') + 1);
        }

        return $index - 1;
    }
}
