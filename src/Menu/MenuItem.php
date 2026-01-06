<?php

namespace SiteAlerts\Menu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MenuItem
 *
 * Represents a single admin menu item.
 * Each item can define its own capability, icon, position, parent,
 * callback function, and optional visibility conditions.
 *
 * @package SiteAlerts\Menu
 * @version 1.0.0
 */
class MenuItem
{
    /** @var string Unique identifier for the menu item */
    public string $id;

    /** @var string Display title of the menu item */
    public string $title;

    /** @var string Required capability to access this menu item */
    public string $capability;

    /** @var string Dash icon or custom icon class */
    public string $icon;

    /** @var float|null Menu position in WordPress admin */
    public ?float $position;

    /** @var string|null Parent menu ID if this is a submenu */
    public ?string $parentId;

    /** @var callable|null Function to render menu page content */
    public $callback; // Mixed type: callable|null

    /** @var array List of callable conditions to check visibility */
    public array $conditions;

    /**
     * MenuItem constructor.
     *
     * @param string $id
     * @param string $title
     * @param string $capability
     * @param string $icon
     * @param float|null $position
     * @param string|null $parentId
     * @param callable|null $callback
     * @param array $conditions
     */
    public function __construct(
        string    $id,
        string    $title,
        string    $capability = 'manage_options',
        string    $icon = 'dashicons-admin-generic',
        ?float    $position = 20.0,
        ?string   $parentId = null,
        ?callable $callback = null,
        array     $conditions = []
    )
    {
        $this->id         = $id;
        $this->title      = $title;
        $this->capability = $capability;
        $this->icon       = $icon;
        $this->position   = $position;
        $this->parentId   = $parentId;
        $this->callback   = $callback;
        $this->conditions = $conditions;
    }

    /**
     * Check whether the menu item should be visible
     * based on provided conditions.
     *
     * @return bool True if all conditions pass, otherwise false
     */
    public function isVisible(): bool
    {
        foreach ($this->conditions as $condition) {
            if (!is_callable($condition)) {
                continue;
            }

            if (!$condition()) {
                return false;
            }
        }
        return true;
    }
}
