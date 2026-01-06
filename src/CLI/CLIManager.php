<?php

namespace SiteAlerts\CLI;

use SiteAlerts\Abstracts\AbstractSingleton;
use SiteAlerts\CLI\Commands\SeedCommand;
use SiteAlerts\CLI\Commands\TruncateCommand;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CLIManager
 *
 * Manages WP-CLI command registration.
 *
 * @package SiteAlerts\CLI
 * @version 1.0.0
 */
class CLIManager extends AbstractSingleton
{
    /**
     * Command namespace.
     *
     * @var string
     */
    private string $namespace = 'site-alerts';

    /**
     * Registered commands.
     *
     * @var array
     */
    private array $commands = [
        'seed'     => SeedCommand::class,
        'truncate' => TruncateCommand::class,
    ];

    /**
     * Register CLI commands.
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->isCliAvailable()) {
            return;
        }

        /**
         * Filter the registered CLI commands.
         *
         * @param array $commands Array of command name => class mappings.
         * @param string $namespace Command namespace.
         */
        $commands = apply_filters('site_alerts_cli_commands', $this->commands, $this->namespace);

        foreach ($commands as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            \WP_CLI::add_command("{$this->namespace} {$name}", $class);
        }
    }

    /**
     * Add a command.
     *
     * @param string $name Command name.
     * @param string $class Command class.
     * @return self
     */
    public function addCommand(string $name, string $class): self
    {
        $this->commands[$name] = $class;

        return $this;
    }

    /**
     * Remove a command.
     *
     * @param string $name Command name.
     * @return self
     */
    public function removeCommand(string $name): self
    {
        unset($this->commands[$name]);

        return $this;
    }

    /**
     * Get registered commands.
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get the command namespace.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Check if WP-CLI is available.
     *
     * @return bool
     */
    private function isCliAvailable(): bool
    {
        return defined('WP_CLI') && WP_CLI && class_exists('WP_CLI');
    }
}
