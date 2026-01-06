<?php

namespace SiteAlerts\Menu;

use SiteAlerts\Utils\MenuUtils;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MenuRenderer
 *
 * Responsible for rendering all registered admin menu items.
 * Decides whether an item should be added as a top-level menu
 * or as a submenu based on its parentId property.
 *
 * @package SiteAlerts\Menu
 * @version 1.0.0
 */
class MenuRenderer
{
    /**
     * Render all visible menu items.
     *
     * @param MenuItem[] $menuItems Array of MenuItem objects
     * @return void
     */
    public function renderAll(array $menuItems): void
    {
        foreach ($menuItems as $item) {
            if (!$item->isVisible()) {
                continue;
            }

            if ($item->parentId) {
                $this->addSubmenu($item);
            } else {
                $this->addMenu($item);
            }
        }
    }

    /**
     * Register a top-level menu item.
     *
     * @param MenuItem $item
     * @return void
     */
    private function addMenu(MenuItem $item): void
    {
        $id = MenuUtils::getSlug($item->id);

        add_menu_page(
            $item->title,
            $item->title,
            $item->capability,
            $id,
            $item->callback,
            $item->icon,
            $item->position
        );
    }

    /**
     * Register a submenu item.
     *
     * @param MenuItem $item
     * @return void
     */
    private function addSubmenu(MenuItem $item): void
    {
        $id       = MenuUtils::getSlug($item->id);
        $parentId = MenuUtils::getSlug($item->parentId);

        add_submenu_page(
            $parentId,
            $item->title,
            $item->title,
            $item->capability,
            $id,
            $item->callback
        );
    }

}
