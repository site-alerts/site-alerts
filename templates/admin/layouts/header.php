<?php
/**
 * Admin Layout: Header
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Get current theme preference
$theme = get_user_meta(get_current_user_id(), 'sa_admin_theme', true) ?: 'light';

// Set defaults for optional variables
$logoUrl   = $logoUrl ?? '';
$title     = $title ?? esc_html__('Site Alerts', 'site-alerts');
$titleLink = $titleLink ?? '';
$navItems  = $navItems ?? [];
$actions   = $actions ?? [];
$version   = $version ?? '';
?>

<div class="wrap sa-wrap" data-sa-theme="<?php echo esc_attr($theme); ?>">
    <header class="sa-header">
        <div class="sa-header-container">
            <!-- Brand Section (Logo/Title) -->
            <div class="sa-header-brand">
                <?php if (!empty($titleLink)) : ?>
                <a href="<?php echo esc_url($titleLink); ?>" class="sa-header-brand-link">
                    <?php endif; ?>

                    <?php if (!empty($logoUrl)) : ?>
                        <img
                            src="<?php echo esc_url($logoUrl); ?>"
                            alt="<?php echo esc_attr($title); ?>"
                            class="sa-header-logo"
                        />
                    <?php endif; ?>

                    <?php if (!empty($title)) : ?>
                        <span class="sa-header-title"><?php echo esc_html($title); ?></span>
                    <?php endif; ?>

                    <?php if (!empty($version)) : ?>
                        <span class="sa-badge sa-badge-secondary sa-header-version">
                        v<?php echo esc_html($version); ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($titleLink)) : ?>
                </a>
            <?php endif; ?>
            </div>

            <!-- Navigation Section -->
            <?php if (!empty($navItems)) : ?>
                <!-- Desktop Navigation (visible on large screens) -->
                <nav class="sa-header-nav sa-header-nav-desktop">
                    <ul class="sa-header-nav-list">
                        <?php foreach ($navItems as $navItem) :
                            $itemClasses = ['sa-header-nav-item'];
                            if (!empty($navItem['active'])) {
                                $itemClasses[] = 'sa-active';
                            }
                            ?>
                            <li class="<?php echo esc_attr(implode(' ', $itemClasses)); ?>">
                                <a
                                    href="<?php echo esc_url($navItem['url'] ?? '#'); ?>"
                                    class="sa-header-nav-link"
                                >
                                    <?php if (!empty($navItem['icon'])) : ?>
                                        <span class="dashicons <?php echo esc_attr($navItem['icon']); ?>"></span>
                                    <?php endif; ?>

                                    <span class="sa-header-nav-text">
                                        <?php echo esc_html($navItem['label'] ?? ''); ?>
                                    </span>

                                    <?php if (!empty($navItem['badge'])) : ?>
                                        <span class="sa-badge sa-badge-primary sa-badge-sm">
                                            <?php echo esc_html($navItem['badge']); ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>

                <!-- Mobile Navigation (toggle + dropdown) -->
                <div class="sa-header-nav-wrapper">
                    <button
                        type="button"
                        class="sa-header-toggle"
                        aria-label="<?php esc_attr_e('Toggle navigation', 'site-alerts'); ?>"
                        aria-expanded="false"
                        aria-controls="sa-header-nav"
                    >
                        <span class="sa-header-toggle-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </button>

                    <nav class="sa-header-nav" id="sa-header-nav">
                        <ul class="sa-header-nav-list">
                            <?php foreach ($navItems as $navItem) :
                                $itemClasses = ['sa-header-nav-item'];
                                if (!empty($navItem['active'])) {
                                    $itemClasses[] = 'sa-active';
                                }
                                ?>
                                <li class="<?php echo esc_attr(implode(' ', $itemClasses)); ?>">
                                    <a
                                        href="<?php echo esc_url($navItem['url'] ?? '#'); ?>"
                                        class="sa-header-nav-link"
                                    >
                                        <?php if (!empty($navItem['icon'])) : ?>
                                            <span class="dashicons <?php echo esc_attr($navItem['icon']); ?>"></span>
                                        <?php endif; ?>

                                        <span class="sa-header-nav-text">
                                            <?php echo esc_html($navItem['label'] ?? ''); ?>
                                        </span>

                                        <?php if (!empty($navItem['badge'])) : ?>
                                            <span class="sa-badge sa-badge-primary sa-badge-sm">
                                                <?php echo esc_html($navItem['badge']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

            <!-- Actions Section -->
            <?php if (!empty($actions)) : ?>
                <div class="sa-header-actions">
                    <?php foreach ($actions as $action) :
                        $type = $action['type'] ?? 'button';
                        $variant = $action['variant'] ?? 'primary';
                        $label = $action['label'] ?? '';
                        $url = $action['url'] ?? '#';
                        $icon = $action['icon'] ?? '';
                        $attrs = $action['attrs'] ?? [];

                        // Build class string
                        $btnClass = 'sa-btn sa-btn-' . esc_attr($variant);
                        if (!empty($action['class'])) {
                            $btnClass .= ' ' . esc_attr($action['class']);
                        }

                        // Build additional attributes string
                        $attrsStr = '';
                        foreach ($attrs as $attrKey => $attrVal) {
                            $attrsStr .= ' ' . esc_attr($attrKey) . '="' . esc_attr($attrVal) . '"';
                        }
                        ?>
                        <?php if ($type === 'link') : ?>
                        <a
                            href="<?php echo esc_url($url); ?>"
                            class="<?php echo esc_attr($btnClass); ?>"
                            <?php echo $attrsStr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        >
                            <?php if (!empty($icon)) : ?>
                                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                            <?php endif; ?>
                            <span class="sa-btn-text"><?php echo esc_html($label); ?></span>
                        </a>
                    <?php else : ?>
                        <button
                            type="button"
                            class="<?php echo esc_attr($btnClass); ?>"
                            <?php echo $attrsStr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        >
                            <?php if (!empty($icon)) : ?>
                                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                            <?php endif; ?>
                            <span class="sa-btn-text"><?php echo esc_html($label); ?></span>
                        </button>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="sa-content-wrapper">
