<?php

namespace SiteAlerts\Components;

use SiteAlerts\Config\PrefixConfig;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AjaxComponent
 *
 * A utility class to simplify AJAX handling in WordPress plugins.
 * Supports authenticated and unauthenticated requests, nonce verification,
 * multiple callbacks per action, and standardized JSON responses.
 *
 * @package SiteAlerts\Components
 * @version 1.0.0
 */
class AjaxComponent
{
    /**
     * Register an AJAX action with automatic nonce verification and error handling.
     *
     * @param string $action Action name (use plugin prefix to avoid conflicts).
     * @param callable $callback Callback function, e.g. [$this, 'method'].
     * @param bool $public Allow unauthenticated access (wp_ajax_nopriv_).
     * @param bool $verifyNonce Automatically verify nonce (default: true).
     * @param string $nonceAction
     * @param string $nonceField
     * @return void
     */
    public static function register(string $action, callable $callback, bool $public = true, bool $verifyNonce = true, string $nonceAction = 'nonce', string $nonceField = 'security'): void
    {
        $wrapped = function () use ($callback, $verifyNonce, $nonceAction, $nonceField) {
            try {
                if ($verifyNonce) {
                    self::verifyNonce($nonceAction, $nonceField);
                }

                $callback();

            } catch (Throwable $e) {
                self::sendError(esc_html__('AJAX error: ', 'site-alerts') . $e->getMessage());
            }

            // Intentionally terminate AJAX execution (required for WordPress AJAX)
            wp_die();
        };

        $action = self::getActionName($action);
        add_action("wp_ajax_{$action}", $wrapped);
        if ($public) {
            add_action("wp_ajax_nopriv_{$action}", $wrapped);
        }
    }

    /**
     * Create a WordPress nonce for a given (unprefixed) nonce identifier.
     *
     * @param string $nonce Raw nonce identifier (unprefixed).
     *
     * @return string Nonce string.
     */
    public static function createNonce(string $nonce = 'nonce'): string
    {
        return wp_create_nonce(self::getNonceAction($nonce));
    }

    /**
     * Verify the WordPress AJAX nonce.
     *
     * @param string $nonceAction Nonce action name (default: 'sa_nonce').
     * @param string $nonceField Field name from $_REQUEST (default: 'security').
     *
     * @return void
     */
    public static function verifyNonce(string $nonceAction = 'nonce', string $nonceField = 'security'): void
    {
        $nonceAction = self::getNonceAction($nonceAction);
        if (!check_ajax_referer($nonceAction, $nonceField, false)) {
            self::sendError(esc_html__('Invalid security token.', 'site-alerts'), 403);
        }
    }

    /**
     * Send a standardized JSON success response.
     *
     * @param array $data Optional data.
     * @param string $message Optional message.
     *
     * @return void
     */
    public static function sendSuccess(array $data = [], string $message = ''): void
    {
        wp_send_json([
            'success' => true,
            'data'    => $data,
            'message' => $message
        ]);
    }

    /**
     * Send a standardized JSON error response.
     *
     * @param string $message Error message.
     * @param int $code HTTP status code (default: 400).
     *
     * @return void
     */
    public static function sendError(string $message, int $code = 400): void
    {
        wp_send_json([
            'success' => false,
            'data'    => null,
            'message' => $message
        ], $code);
    }

    /**
     * Build the fully qualified AJAX action name.
     *
     * Applies the plugin prefix and normalizes the action string
     * to prevent naming conflicts.
     *
     * @param string $action Raw action name.
     *
     * @return string Prefixed AJAX action name.
     */
    public static function getActionName(string $action): string
    {
        return PrefixConfig::ajaxAction(sanitize_key($action));
    }

    /**
     * Build the prefixed nonce action name.
     *
     * Ensures a consistent and namespaced nonce identifier
     * to prevent collisions with other plugins.
     *
     * @param string $nonce Raw nonce identifier.
     *
     * @return string Prefixed nonce action name.
     */
    public static function getNonceAction(string $nonce): string
    {
        return PrefixConfig::nonce(sanitize_key($nonce));
    }
}
