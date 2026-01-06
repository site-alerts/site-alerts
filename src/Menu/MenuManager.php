<?php

namespace SiteAlerts\Menu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MenuManager
 *
 * Handles registration, normalization, sorting, and rendering of admin menu items.
 * Supports both object-oriented usage (MenuItem instances) and array-based usage
 * for a WordPress-like developer experience.
 *
 * @package SiteAlerts\Menu
 * @version 1.1.0
 */
class MenuManager
{
    /**
     * @var MenuItem[]|array[] $menuItems List of registered menu items (objects or arrays)
     */
    private array $menuItems = [];

    /**
     * Initialize menu system by hooking into WordPress admin_menu.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
    }

    /**
     * Register a new menu item.
     *
     * @param MenuItem|array $item
     * @return void
     */
    public function registerMenuItem($item): void
    {
        $this->menuItems[] = $item;
    }

    /**
     * Collect, filter, normalize, sort, and render all menu items.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        $menuItems = $this->getFilteredMenuItems();
        $menuItems = $this->normalizeMenuItems($menuItems);
        $menuItems = $this->sortMenuItems($menuItems);

        (new MenuRenderer())->renderAll($menuItems);
    }

    /**
     * Apply WordPress filters to get the final list of menu items.
     *
     * @return MenuItem[]|array[] Filtered list of menu items (raw)
     */
    private function getFilteredMenuItems(): array
    {
        return apply_filters('site_alerts_menu_items', $this->menuItems);
    }

    /**
     * Normalize raw menu items (arrays or MenuItem objects) to MenuItem objects.
     *
     * Wraps callbacks if they are class names with a 'render' method.
     *
     * @param array $items Raw menu items
     * @return MenuItem[] Normalized MenuItem objects
     */
    private function normalizeMenuItems(array $items): array
    {
        $normalized = array_map(function ($item) {
            if ($item instanceof MenuItem) {
                $item->callback = $this->wrapClassCallback($item->callback);
                return $item;
            }

            if (is_array($item)) {
                return new MenuItem(
                    $item['id'] ?? '',
                    $item['title'] ?? '',
                    $item['capability'] ?? 'manage_options',
                    $item['icon'] ?? '',
                    $item['position'] ?? null,
                    $item['parentId'] ?? null,
                    $this->wrapClassCallback($item['callback'] ?? null),
                    $item['conditions'] ?? []
                );
            }

            return null; // invalid item
        }, $items);

        // Keep only valid MenuItem instances
        return array_filter($normalized, static fn($item) => $item instanceof MenuItem);
    }

    /**
     * Sort menu items by hierarchy and position.
     *
     * Top-level menus appear before submenus.
     *
     * @param MenuItem[] $items Normalized menu items
     * @return MenuItem[] Sorted menu items
     */
    private function sortMenuItems(array $items): array
    {
        usort($items, static function ($a, $b) {
            // top-level comes first
            if ($a->parentId === null && $b->parentId !== null) {
                return -1;
            }
            if ($a->parentId !== null && $b->parentId === null) {
                return 1;
            }

            // then sort by position
            return $a->position <=> $b->position;
        });

        return $items;
    }

    /**
     * Wrap a class name as a callable for menu callbacks.
     *
     * @param callable|string|null $callback The original callback, or a class name.
     *
     * @return callable|null A callable closure or the original callback.
     */
    private function wrapClassCallback($callback)
    {
        if (is_string($callback) && class_exists($callback) && method_exists($callback, 'renderPage')) {
            /** @var class-string $callback */
            return [$callback::instance(), 'renderPage'];
        }

        return $callback;
    }
}
