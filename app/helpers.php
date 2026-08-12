<?php

if (! function_exists('format_money')) {
    /**
     * Format money values as Syrian Pound (SYP / ل.س.) in whole numbers.
     * Always render numbers with Western Arabic numerals (0-9).
     *
     * Example: 125,000 ل.س.
     *
     * @param int|float $amount
     * @return string
     */
    function format_money($amount): string
    {
        // Avoid PHP NumberFormatter with ar locale which outputs Eastern digits.
        // Use plain number_format() and append currency suffix.
        $formatted = number_format((float) $amount, 0, '.', ',');
        return "{$formatted} ل.س.";
    }
}
