<?php

namespace AltDesign\AltInbound\Helpers;

use Illuminate\Validation\ValidationException;

class BlockCsv
{
    public const HEADERS = ['id', 'ip'];

    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r", "\n"];

    private const BYTE_ORDER_MARK = "\xEF\xBB\xBF";

    private const ROWS_NAMED_IN_ERRORS = 20;

    public const MAX_ROWS = 5000;

    /**
     * Read the IP addresses from an import file, in standard form and without duplicates.
     *
     * The file must have an ip column, as the export does, and no more than MAX_ROWS rows. Nothing is returned unless every
     * row holds a single IP address. Rows with nothing in them are skipped.
     *
     * @return list<string>
     *
     * @throws ValidationException When the file has no ip column, has too many rows, or any row does not hold a single IP address.
     */
    public static function read(string $path): array
    {
        $handle = fopen($path, 'r');

        try {
            self::skipByteOrderMark($handle);
            $column = self::ipColumn(fgetcsv($handle, escape: ''));
            [$ips, $badRows] = self::readRows($handle, $column);
        } finally {
            fclose($handle);
        }

        if ($badRows !== []) {
            throw ValidationException::withMessages(['file' => self::badRowsMessage($badRows)]);
        }

        return array_keys($ips);
    }

    /**
     * Neutralise a value that a spreadsheet would otherwise run as a formula.
     */
    public static function cell(?string $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true) ? "'".$value : $value;
    }

    /**
     * @param  array<int, string|null>|false  $header
     *
     * @throws ValidationException
     */
    private static function ipColumn(array|false $header): int
    {
        $names = array_map(fn ($name) => strtolower(trim((string) $name)), $header ?: []);

        $column = array_search('ip', $names, true);

        if ($column === false) {
            throw ValidationException::withMessages([
                'file' => 'The file needs a header row with an ip column, as in the export.',
            ]);
        }

        return $column;
    }

    /**
     * @param  resource  $handle
     * @return array{0: array<string, true>, 1: list<int>}
     */
    private static function readRows($handle, int $column): array
    {
        $ips = [];
        $badRows = [];
        $row = 1;
        $filledRows = 0;

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $row++;

            if (self::isBlank($cells)) {
                continue;
            }

            if (++$filledRows > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'file' => 'The file has more than '.number_format(self::MAX_ROWS).' rows. Split it into smaller files and import each one.',
                ]);
            }

            $ip = Ip::canonical($cells[$column] ?? null);

            if ($ip === null) {
                $badRows[] = $row;

                continue;
            }

            $ips[$ip] = true;
        }

        return [$ips, $badRows];
    }

    /**
     * @param  list<int>  $rows
     */
    private static function badRowsMessage(array $rows): string
    {
        $named = implode(', ', array_slice($rows, 0, self::ROWS_NAMED_IN_ERRORS));
        $others = count($rows) - self::ROWS_NAMED_IN_ERRORS;

        $list = $others > 0 ? "{$named} and {$others} more" : $named;

        return count($rows) === 1
            ? "Nothing was imported. Check row {$list}: it needs a single IP address."
            : "Nothing was imported. Check rows {$list}: each needs a single IP address.";
    }

    /**
     * @param  resource  $handle
     */
    private static function skipByteOrderMark($handle): void
    {
        if (fread($handle, strlen(self::BYTE_ORDER_MARK)) !== self::BYTE_ORDER_MARK) {
            rewind($handle);
        }
    }

    /**
     * @param  array<int, string|null>  $cells
     */
    private static function isBlank(array $cells): bool
    {
        return trim(implode('', array_map(fn ($cell) => (string) $cell, $cells))) === '';
    }
}
