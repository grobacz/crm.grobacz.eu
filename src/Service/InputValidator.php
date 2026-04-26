<?php

namespace App\Service;

class InputValidator
{
    public const EMAIL_MAX_LENGTH = 255;
    public const PHONE_MAX_LENGTH = 20;
    public const NAME_MAX_LENGTH = 255;
    public const COMPANY_MAX_LENGTH = 255;

    public static function trimString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function normalizeEmail(mixed $value): ?string
    {
        $trimmed = self::trimString($value);

        return $trimmed !== null ? strtolower($trimmed) : null;
    }

    public static function validateEmail(?string $email, bool $required = true): ?string
    {
        if ($email === null || $email === '') {
            return $required ? 'Email is required.' : null;
        }

        if (mb_strlen($email) > self::EMAIL_MAX_LENGTH) {
            return 'Email must be 255 characters or fewer.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Enter a valid email address.';
        }

        return null;
    }

    public static function validatePhone(?string $phone, bool $required = false): ?string
    {
        if ($phone === null || $phone === '') {
            return $required ? 'Phone number is required.' : null;
        }

        if (mb_strlen($phone) > self::PHONE_MAX_LENGTH) {
            return 'Phone number must be 20 characters or fewer.';
        }

        if (!preg_match('/^\+?[\d\s().-]{7,20}$/', $phone)) {
            return 'Use a valid phone format with digits, spaces, parentheses, dots, or dashes.';
        }

        return null;
    }

    public static function validateName(?string $name, bool $required = true): ?string
    {
        if ($name === null || $name === '') {
            return $required ? 'Name is required.' : null;
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            return 'Name must be 255 characters or fewer.';
        }

        return null;
    }

    public static function validateCompany(?string $company): ?string
    {
        if ($company === null || $company === '') {
            return null;
        }

        if (mb_strlen($company) > self::COMPANY_MAX_LENGTH) {
            return 'Company name must be 255 characters or fewer.';
        }

        return null;
    }

    public static function validateStatus(string $status, array $allowed): ?string
    {
        if (!in_array($status, $allowed, true)) {
            $last = array_pop($allowed);
            $list = implode(', ', $allowed) . ' or ' . $last;

            return 'Status must be ' . $list . '.';
        }

        return null;
    }
}
