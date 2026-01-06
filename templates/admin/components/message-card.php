<?php
/**
 * Message Card Component
 *
 * Displays an informational message card with title, text, and optional helper text.
 * Used for empty states and status messages in the alerts dashboard.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 *
 * @var string $title Card title (e.g., 'Getting started', 'All clear')
 * @var string $text Main message text
 * @var string $helper Optional helper/tip text displayed below main text
 * @var string $icon Optional icon class (default: 'sa-icon--info')
 * @var string $color Color variant: 'info', 'success', 'warning' (default: 'info')
 */

defined('ABSPATH') || exit;

$title  = $title ?? '';
$text   = $text ?? '';
$helper = $helper ?? '';
$icon   = $icon ?? 'sa-icon--info';
$color  = $color ?? 'info';
?>

<div class="sa-card sa-message-card sa-message-card--<?php echo esc_attr($color); ?>">
    <div class="sa-message-card__body">
        <div class="sa-message-card__icon">
            <span class="<?php echo esc_attr($icon); ?>"></span>
        </div>
        <div class="sa-message-card__content">
            <?php if (!empty($title)) : ?>
                <h5 class="sa-message-card__title"><?php echo esc_html($title); ?></h5>
            <?php endif; ?>
            <?php if (!empty($text)) : ?>
                <p class="sa-message-card__text"><?php echo esc_html($text); ?></p>
            <?php endif; ?>
            <?php if (!empty($helper)) : ?>
                <p class="sa-message-card__helper"><?php echo esc_html($helper); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
