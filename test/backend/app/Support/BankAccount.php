<?php

namespace App\Support;

class BankAccount
{
    /**
     * Cayman-style bank identifier used inside the IBAN BBAN.
     */
    public const BANK_CODE = 'CCBK';

    /**
     * Generate a 12-digit account number (never leading zero).
     */
    public static function generateNumber(): string
    {
        $first = random_int(1, 9);
        $rest = '';
        for ($i = 0; $i < 11; $i++) {
            $rest .= random_int(0, 9);
        }

        return $first . $rest;
    }

    /**
     * Build a valid IBAN (KY) for an account number.
     */
    public static function toIban(string $accountNumber): string
    {
        $bban = self::BANK_CODE . str_pad(preg_replace('/\D/', '', $accountNumber), 16, '0', STR_PAD_LEFT);

        // Rearrange: BBAN + country + "00", then substitute letters with digits.
        $rearranged = $bban . 'KY' . '00';
        $numeric = '';
        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord(strtoupper($char)) - 55) : $char;
        }

        $check = 98 - (self::mod97($numeric));

        return 'KY' . str_pad((string) $check, 2, '0', STR_PAD_LEFT) . $bban;
    }

    /**
     * Format an account number in groups of four for display.
     */
    public static function format(string $accountNumber): string
    {
        return trim(chunk_split($accountNumber, 4, ' '));
    }

    /**
     * Format an IBAN in groups of four for display.
     */
    public static function formatIban(string $iban): string
    {
        return trim(chunk_split($iban, 4, ' '));
    }

    /**
     * Modulo 97 without big-integer support.
     */
    private static function mod97(string $numeric): int
    {
        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder;
    }
}
