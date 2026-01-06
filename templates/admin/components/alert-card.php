<?php
/**
 * Alert Card Component
 *
 * Displays a collapsible alert with icon, severity badge, title, short message,
 * and expandable details section.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 *
 * @var string $iconClass CSS class for the icon (e.g., 'sa-icon--traffic-drop')
 * @var string $severityClass CSS class for severity badge (e.g., 'sa-badge--warning')
 * @var string $typeLabel Human-readable type label (e.g., 'Traffic Drop')
 * @var string $severity Alert severity: 'info', 'warning', 'critical'
 * @var string $title Alert title
 * @var string $message Alert message/description (legacy, use short_message for new code)
 * @var string $shortMessage Short collapsed message
 * @var array  $expanded Expanded content: ['meaning' => string, 'checks' => array]
 * @var string $alertDate Date of the alert (Y-m-d format)
 */

defined('ABSPATH') || exit;

$iconClass     = $iconClass ?? 'sa-icon--alert';
$severityClass = $severityClass ?? 'sa-badge--info';
$typeLabel     = $typeLabel ?? __('Alert', 'site-alerts');
$severity      = $severity ?? 'info';
$title         = $title ?? '';
$message       = $message ?? '';
$shortMessage  = $shortMessage ?? $message;
$expanded      = $expanded ?? [];
$alertDate     = $alertDate ?? '';

// Format the date for display
$formattedDate = '';
if (!empty($alertDate)) {
    $timestamp = strtotime($alertDate);
    if ($timestamp !== false) {
        $formattedDate = wp_date(get_option('date_format'), $timestamp);
    }
}

// Check if card has expandable content
$hasExpanded = !empty($expanded['meaning']) || !empty($expanded['checks']) || !empty($expanded['topUrls']);

// Generate unique ID for accessibility
$cardId = 'sa-alert-' . wp_unique_id();
?>

<div class="sa-card sa-alert-card sa-alert-card--<?php echo esc_attr($severity); ?><?php echo $hasExpanded ? ' sa-alert-card--collapsible' : ''; ?>">
    <div class="sa-alert-card__body">
        <div class="sa-alert-card__icon">
            <span class="<?php echo esc_attr($iconClass); ?>"></span>
        </div>
        <div class="sa-alert-card__content">
            <div class="sa-alert-card__header">
                <span class="sa-badge <?php echo esc_attr($severityClass); ?>">
                    <?php echo esc_html($typeLabel); ?>
                </span>
                <?php if (!empty($formattedDate)) : ?>
                    <span class="sa-alert-card__date"><?php echo esc_html($formattedDate); ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($title)) : ?>
                <h5 class="sa-alert-card__title"><?php echo esc_html($title); ?></h5>
            <?php endif; ?>
            <?php if (!empty($shortMessage)) : ?>
                <p class="sa-alert-card__message"><?php echo esc_html($shortMessage); ?></p>
            <?php endif; ?>

            <?php if ($hasExpanded) : ?>
                <div id="<?php echo esc_attr($cardId); ?>-details" class="sa-alert-card__details" hidden>
                    <?php if (!empty($expanded['meaning'])) : ?>
                        <div class="sa-alert-card__section">
                            <h6 class="sa-alert-card__section-title"><?php esc_html_e('What this means', 'site-alerts'); ?></h6>
                            <p class="sa-alert-card__section-text"><?php echo esc_html($expanded['meaning']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($expanded['checks']) && is_array($expanded['checks'])) : ?>
                        <div class="sa-alert-card__section">
                            <h6 class="sa-alert-card__section-title"><?php esc_html_e('What you should check next', 'site-alerts'); ?></h6>
                            <ul class="sa-alert-card__checklist">
                                <?php foreach ($expanded['checks'] as $check) : ?>
                                    <li><?php echo esc_html($check); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($expanded['topUrls']) && is_array($expanded['topUrls'])) : ?>
                        <div class="sa-alert-card__section">
                            <h6 class="sa-alert-card__section-title"><?php esc_html_e('Top 404 URLs', 'site-alerts'); ?></h6>
                            <ul class="sa-alert-card__url-list">
                                <?php foreach ($expanded['topUrls'] as $urlItem) : ?>
                                    <li>
                                        <code class="sa-alert-card__url-path"><?php echo esc_html($urlItem['path']); ?></code>
                                        <span class="sa-alert-card__url-count"><?php echo esc_html(number_format_i18n($urlItem['count'])); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($hasExpanded) : ?>
            <button
                type="button"
                class="sa-alert-card__toggle"
                aria-expanded="false"
                aria-controls="<?php echo esc_attr($cardId); ?>-details"
                aria-label="<?php esc_attr_e('Toggle details', 'site-alerts'); ?>"
            >
                <span class="sa-icon--chevron-down"></span>
            </button>
        <?php endif; ?>
    </div>
</div>
