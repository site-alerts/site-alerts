<?php

namespace SiteAlerts\Utils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Logger
 *
 * Plugin-specific logging utility with multiple log levels
 * and file-based output.
 *
 * @package SiteAlerts\Utils
 * @version 1.0.0
 */
class Logger
{
    /**
     * Log level constants
     */
    public const LEVEL_DEBUG    = 'debug';
    public const LEVEL_INFO     = 'info';
    public const LEVEL_WARNING  = 'warning';
    public const LEVEL_ERROR    = 'error';
    public const LEVEL_CRITICAL = 'critical';

    /**
     * Log level priorities (lower = more verbose)
     */
    public const LEVEL_PRIORITIES = [
        self::LEVEL_DEBUG    => 0,
        self::LEVEL_INFO     => 1,
        self::LEVEL_WARNING  => 2,
        self::LEVEL_ERROR    => 3,
        self::LEVEL_CRITICAL => 4,
    ];

    /**
     * Log file path
     *
     * @var string
     */
    private static string $logFile;

    /**
     * Minimum log level to record
     *
     * @var string
     */
    private static string $minLevel = self::LEVEL_DEBUG;

    /**
     * Whether logging is enabled
     *
     * @var bool
     */
    private static bool $enabled = true;

    /**
     * Maximum log file size in bytes (5MB)
     *
     * @var int
     */
    private static int $maxFileSize = 5242880;

    /**
     * Number of rotated log files to keep
     *
     * @var int
     */
    private static int $maxFiles = 3;

    /**
     * Initialize the logger.
     *
     * @return void
     */
    public static function init(): void
    {
        self::$logFile = self::getLogPath();
        self::createLogDirectory();

        // Respect WP_DEBUG setting
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            self::$minLevel = self::LEVEL_WARNING;
        }
    }

    /**
     * Set the minimum log level.
     *
     * @param string $level One of the LEVEL_* constants.
     * @return void
     */
    public static function setMinLevel(string $level): void
    {
        if (isset(self::LEVEL_PRIORITIES[$level])) {
            self::$minLevel = $level;
        }
    }

    /**
     * Enable logging.
     *
     * @return void
     */
    public static function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * Disable logging.
     *
     * @return void
     */
    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * Log a debug message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Log a message with a specific level.
     *
     * @param string $level The log level.
     * @param string $message The message to log.
     * @param array $context Additional context data.
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (!self::$enabled || !self::shouldLog($level)) {
            return;
        }

        self::rotateLogs();

        $formattedMessage = self::formatMessage($level, $message, $context);

        self::writeToFile($formattedMessage);

        // Also write to error_log if WP_DEBUG_LOG is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            self::writeToErrorLog($level, $message, $context);
        }
    }

    /**
     * Get the log file path.
     *
     * @return string
     */
    public static function getLogPath(): string
    {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/sa-logs/plugin.log';
    }

    /**
     * Get the log directory path.
     *
     * @return string
     */
    public static function getLogDirectory(): string
    {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/sa-logs';
    }

    /**
     * Clear the log file.
     *
     * @return bool
     */
    public static function clearLog(): bool
    {
        if (empty(self::$logFile)) {
            return true;
        }

        if (file_exists(self::$logFile)) {
            return file_put_contents(self::$logFile, '') !== false;
        }
        return true;
    }

    /**
     * Get log file contents.
     *
     * @param int $lines Number of lines to return (from end).
     * @return array
     */
    public static function getLogContents(int $lines = 100): array
    {
        if (empty(self::$logFile) || !file_exists(self::$logFile)) {
            return [];
        }

        $file = file(self::$logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($file === false) {
            return [];
        }

        return array_slice($file, -$lines);
    }

    /**
     * Rotate log files if needed.
     *
     * @return void
     */
    public static function rotateLogs(): void
    {
        if (empty(self::$logFile) || !file_exists(self::$logFile)) {
            return;
        }

        if (filesize(self::$logFile) < self::$maxFileSize) {
            return;
        }

        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Rotate existing log files
        for ($i = self::$maxFiles - 1; $i >= 1; $i--) {
            $oldFile = self::$logFile . '.' . $i;
            $newFile = self::$logFile . '.' . ($i + 1);

            if (file_exists($oldFile)) {
                if ($i === self::$maxFiles - 1) {
                    wp_delete_file($oldFile);
                } else {
                    $wp_filesystem->move($oldFile, $newFile, true);
                }
            }
        }

        // Rotate current log
        $wp_filesystem->move(self::$logFile, self::$logFile . '.1', true);
    }

    /**
     * Check if a message should be logged based on level.
     *
     * @param string $level The log level.
     * @return bool
     */
    private static function shouldLog(string $level): bool
    {
        $levelPriority = self::LEVEL_PRIORITIES[$level] ?? 0;
        $minPriority   = self::LEVEL_PRIORITIES[self::$minLevel] ?? 0;

        return $levelPriority >= $minPriority;
    }

    /**
     * Format a log message.
     *
     * @param string $level The log level.
     * @param string $message The message.
     * @param array $context Additional context.
     * @return string
     */
    private static function formatMessage(string $level, string $message, array $context): string
    {
        $timestamp  = current_time('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        $formatted = "[{$timestamp}] [{$levelUpper}] {$message}";

        if (!empty($context)) {
            $formatted .= ' | Context: ' . wp_json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        return $formatted . PHP_EOL;
    }

    /**
     * Write a message to the log file.
     *
     * @param string $message The formatted message.
     * @return void
     */
    private static function writeToFile(string $message): void
    {
        if (empty(self::$logFile)) {
            self::init();
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents(self::$logFile, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Write a message to the PHP error log.
     *
     * @param string $level The log level.
     * @param string $message The message.
     * @param array $context Additional context.
     * @return void
     */
    private static function writeToErrorLog(string $level, string $message, array $context): void
    {
        $prefix     = '[Site Alerts]';
        $contextStr = !empty($context) ? ' | ' . wp_json_encode($context) : '';

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log("{$prefix} [{$level}] {$message}{$contextStr}");
    }

    /**
     * Create the log directory with security measures.
     *
     * @return void
     */
    private static function createLogDirectory(): void
    {
        $logDir = self::getLogDirectory();

        if (!is_dir($logDir)) {
            wp_mkdir_p($logDir);

            // Create .htaccess to prevent direct access
            $htaccess = $logDir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, 'deny from all');
            }

            // Create index.php to prevent directory listing
            $index = $logDir . '/index.php';
            if (!file_exists($index)) {
                file_put_contents($index, '<?php // Silence is golden.');
            }
        }
    }
}
