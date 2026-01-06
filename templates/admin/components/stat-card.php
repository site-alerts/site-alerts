<?php
/**
 * Stat Card Component
 *
 * Displays a statistic with icon, value, and label in a card format.
 * Based on Sneat/Vuexy analytics card design.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 *
 * @var string $iconClass CSS class for the icon (e.g., 'sa-icon--traffic-drop')
 * @var string $value The statistic value to display
 * @var string $label The label describing the statistic
 * @var string $subtitle Optional subtitle for additional context
 * @var string $color Color variant: 'primary', 'success', 'warning', 'danger', 'info' (default: 'primary')
 */

defined('ABSPATH') || exit;

$iconClass = $iconClass ?? 'sa-icon--alert';
$value     = $value ?? '0';
$label     = $label ?? '';
$subtitle  = $subtitle ?? '';
$color     = $color ?? 'primary';
?>

<div class="sa-stat-card">
    <div class="sa-stat-card__body">
        <div class="sa-stat-card__icon sa-stat-card__icon--<?php echo esc_attr($color); ?>">
            <span class="<?php echo esc_attr($iconClass); ?>"></span>
        </div>
        <div class="sa-stat-card__content">
            <p class="sa-stat-card__label"><?php echo esc_html($label); ?></p>
            <h4 class="sa-stat-card__value"><?php echo esc_html($value); ?></h4>
            <?php if (!empty($subtitle)) : ?>
                <p class="sa-stat-card__subtitle"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
