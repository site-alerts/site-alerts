<?php

namespace SiteAlerts\Utils;

use DateTimeImmutable;
use DateTimeZone;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DateTimeUtils
 *
 * A utility class for handling date and time operations with WordPress timezone support.
 *
 * @package MyStarterPlugin\Utils
 * @version 1.0.0
 */
class DateTimeUtils
{
    /**
     * Common date format constants.
     */
    public const FORMAT_DATE     = 'Y-m-d';
    public const FORMAT_TIME     = 'H:i:s';
    public const FORMAT_DATETIME = 'Y-m-d H:i:s';
    public const FORMAT_ISO8601  = 'c';
    public const FORMAT_KEY      = 'Ymd';

    /**
     * Get today's date in the WordPress timezone.
     *
     * @param string $format Optional. PHP date format. Default 'Y-m-d'.
     * @return string The formatted date string.
     */
    public static function today(string $format = self::FORMAT_DATE): string
    {
        return self::now($format);
    }

    /**
     * Get today's date as a key string (Ymd format).
     *
     * Useful for generating date-based keys or identifiers.
     *
     * @return string The date key (e.g., '20251231').
     */
    public static function todayKey(): string
    {
        return self::today(self::FORMAT_KEY);
    }

    /**
     * Get the current date and time in the WordPress timezone.
     *
     * @param string $format Optional. PHP date format. Default 'Y-m-d H:i:s'.
     * @return string The formatted datetime string.
     */
    public static function now(string $format = self::FORMAT_DATETIME): string
    {
        return current_datetime()->format($format);
    }

    /**
     * Get the current timestamp adjusted for WordPress timezone.
     *
     * @return int Unix timestamp.
     */
    public static function timestamp(): int
    {
        return current_datetime()->getTimestamp();
    }

    /**
     * Get the WordPress timezone as a DateTimeZone object.
     *
     * @return DateTimeZone The WordPress timezone.
     */
    public static function getTimezone(): DateTimeZone
    {
        return wp_timezone();
    }

    /**
     * Get the WordPress timezone string.
     *
     * @return string The timezone string (e.g., 'America/New_York' or 'UTC+5').
     */
    public static function getTimezoneString(): string
    {
        return wp_timezone_string();
    }

    /**
     * Format a date/time value to the WordPress timezone.
     *
     * @param string|int|DateTimeImmutable $date The date to format. Can be a string, timestamp, or DateTimeImmutable.
     * @param string $format Optional. PHP date format. Default 'Y-m-d H:i:s'.
     * @return string|false The formatted date string, or false on failure.
     */
    public static function format($date, string $format = self::FORMAT_DATETIME)
    {
        $datetime = self::createDateTime($date);

        if (!$datetime) {
            return false;
        }

        return $datetime->setTimezone(self::getTimezone())->format($format);
    }

    /**
     * Convert a date from one timezone to another.
     *
     * @param string|int|DateTimeImmutable $date The date to convert.
     * @param string $fromTz The source timezone string.
     * @param string $toTz The target timezone string. Use 'wp' for WordPress timezone.
     * @param string $format Optional. PHP date format. Default 'Y-m-d H:i:s'.
     * @return string|false The converted date string, or false on failure.
     */
    public static function convert($date, string $fromTz, string $toTz, string $format = self::FORMAT_DATETIME)
    {
        try {
            $fromTimezone = new DateTimeZone($fromTz);
            $toTimezone   = $toTz === 'wp' ? self::getTimezone() : new DateTimeZone($toTz);

            $datetime = self::createDateTime($date, $fromTimezone);

            if (!$datetime) {
                return false;
            }

            return $datetime->setTimezone($toTimezone)->format($format);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert a UTC date/time to the WordPress timezone.
     *
     * @param string|int|DateTimeImmutable $date The UTC date to convert.
     * @param string $format Optional. PHP date format. Default 'Y-m-d H:i:s'.
     * @return string|false The converted date string, or false on failure.
     */
    public static function utcToLocal($date, string $format = self::FORMAT_DATETIME)
    {
        return self::convert($date, 'UTC', 'wp', $format);
    }

    /**
     * Convert a WordPress local date/time to UTC.
     *
     * @param string|int|DateTimeImmutable $date The local date to convert.
     * @param string $format Optional. PHP date format. Default 'Y-m-d H:i:s'.
     * @return string|false The converted date string, or false on failure.
     */
    public static function localToUtc($date, string $format = self::FORMAT_DATETIME)
    {
        return self::convert($date, self::getTimezoneString(), 'UTC', $format);
    }

    /**
     * Create a DateTimeImmutable object from various input types.
     *
     * @param string|int|DateTimeImmutable $date The date input.
     * @param DateTimeZone|null $timezone Optional. The timezone to use.
     * @return DateTimeImmutable|false The DateTimeImmutable object, or false on failure.
     */
    private static function createDateTime($date, ?DateTimeZone $timezone = null)
    {
        try {
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }

            if (is_int($date) || (is_string($date) && ctype_digit($date))) {
                $datetime = new DateTimeImmutable('@' . (int)$date);
                return $timezone ? $datetime->setTimezone($timezone) : $datetime;
            }

            if (is_string($date)) {
                return new DateTimeImmutable($date, $timezone);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
