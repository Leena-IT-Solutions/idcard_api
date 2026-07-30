<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a phone number for comparison purposes only.
     * Strips all non-digit characters, then returns the last 10 digits
     * (drops country codes like +91/91). Returns null if fewer than
     * 10 digits remain (not a usable number for matching).
     */
    public static function normalize(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        if (strlen($digits) < 10) {
            return null;
        }

        return substr($digits, -10);
    }
}
