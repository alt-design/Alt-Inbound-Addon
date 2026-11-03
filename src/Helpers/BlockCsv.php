<?php

namespace AltDesign\AltInbound\Helpers;

use Illuminate\Validation\ValidationException;

class BlockCsv
{
    public const HEADERS = ['id', 'ip'];

    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r", "\n"];

    private const BYTE_ORDER_MARK = "\xEF\xBB\xBF";

    private const ROWS_NAMED_IN_ERRORS = 20;

    /**
     * Read the IP addresses from an import file, in standard form and without duplicates.
     *
     * The file must have an ip column, as the export does. Nothing is returned unless every row holds a single IP address.
     *
     * @return list<string>
     *
     * @throws ValidationException When the file has no ip column or any row does not hold a single IP address.
     */
    public static function read(string $path): array
    {
        $handle = fopen($path, 'r');

        try {
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
        $names = array_map(
            fn ($name) => strtolower(trim(self::withoutByteOrderMark((string) $name))),
            $header ?: [],
        );

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

        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $row++;

            if ($cells === [null]) {
                continue;
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

    private static function withoutByteOrderMark(string $value): string
    {
        return str_starts_with($value, self::BYTE_ORDER_MARK) ? substr($value, strlen(self::BYTE_ORDER_MARK)) : $value;
    }
}
