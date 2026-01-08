<?php
/**
 * Admin Page: Alerts Dashboard
 *
 * Displays state-aware digest statistics, status summary, latest alerts, and history.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 *
 * @var string $pageTitle Page title
 * @var string $pageSubtitle Page subtitle/description
 * @var string $statusLine Status line for header (e.g., "Last checked: 2 minutes ago")
 * @var array $statusSummary Status summary box: ['color', 'title', 'text']
 * @var array $digestCards Array of stat card configurations
 * @var array $latestAlerts Latest alerts data: ['type' => 'message'|'alerts', ...]
 * @var array $history History data: ['showTable', 'average', 'rows', 'emptyMessage']
 * @var bool $showPromoBanner Whether to show the promo banner
 * @var string $promoDismissNonce Nonce for promo banner dismiss action
 */

defined('ABSPATH') || exit;

// Set defaults for optional variables
$pageTitle         = $pageTitle ?? esc_html__('Site Alerts', 'site-alerts');
$pageSubtitle      = $pageSubtitle ?? esc_html__('Manage your site alerts and notifications.', 'site-alerts');
$statusLine        = $statusLine ?? '';
$statusSummary     = $statusSummary ?? [];
$digestCards       = $digestCards ?? [];
$latestAlerts      = $latestAlerts ?? ['type' => 'message', 'title' => '', 'text' => '', 'helper' => ''];
$history           = $history ?? ['showTable' => true, 'average' => null, 'rows' => [], 'emptyMessage' => ''];
$showPromoBanner   = $showPromoBanner ?? true;
$promoDismissNonce = $promoDismissNonce ?? '';

// Template utility for rendering components
use SiteAlerts\Utils\TemplateUtils;

?>

<!-- Page Header -->
<div class="sa-page-header sa-mb-4">
    <h1 class="sa-page-title"><?php echo esc_html($pageTitle); ?></h1>
    <?php if (!empty($pageSubtitle)) : ?>
        <p class="sa-page-description"><?php echo esc_html($pageSubtitle); ?></p>
    <?php endif; ?>
    <?php if (!empty($statusLine)) : ?>
        <p class="sa-page-status">
            <span class="sa-icon--clock"></span>
            <?php echo esc_html($statusLine); ?>
        </p>
    <?php endif; ?>
</div>

<!-- Status Summary Box -->
<?php if (!empty($statusSummary['title']) || !empty($statusSummary['text'])) : ?>
    <div class="sa-status-summary sa-status-summary--<?php echo esc_attr($statusSummary['color'] ?? 'info'); ?> sa-mb-4">
        <div class="sa-status-summary__content">
            <?php if (!empty($statusSummary['title'])) : ?>
                <strong class="sa-status-summary__title"><?php echo esc_html($statusSummary['title']); ?></strong>
                <span class="sa-status-summary__separator">—</span>
            <?php endif; ?>
            <?php if (!empty($statusSummary['text'])) : ?>
                <span class="sa-status-summary__text"><?php echo esc_html($statusSummary['text']); ?></span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="sa-page-content">
    <!-- Weekly Digest Section -->
    <div class="sa-section">
        <h3 class="sa-section__title"><?php esc_html_e('Weekly Digest', 'site-alerts'); ?></h3>
        <p class="sa-section__description"><?php esc_html_e('Alert summary for the last 7 days.', 'site-alerts'); ?></p>
        <div class="sa-row sa-gy-4">
            <?php foreach ($digestCards as $cardKey => $card) : ?>
                <div class="sa-col-12 sa-col-sm-6 sa-col-lg-3">
                    <?php
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in template
                    echo TemplateUtils::renderTemplate('admin/components/stat-card', [
                        'iconClass' => $card['iconClass'] ?? 'sa-icon--alert',
                        'value'     => $card['value'] ?? '0',
                        'label'     => $card['label'] ?? '',
                        'subtitle'  => $card['subtitle'] ?? '',
                        'color'     => $card['color'] ?? 'primary',
                    ]);
                    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Latest Alerts Section -->
    <div class="sa-section">
        <h3 class="sa-section__title"><?php esc_html_e('Latest Alerts', 'site-alerts'); ?></h3>
        <p class="sa-section__description"><?php esc_html_e('Most recent alerts triggered on your site.', 'site-alerts'); ?></p>

        <?php if ($latestAlerts['type'] === 'message') : ?>
            <?php
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in template
            echo TemplateUtils::renderTemplate('admin/components/message-card', [
                'title'  => $latestAlerts['title'] ?? '',
                'text'   => $latestAlerts['text'] ?? '',
                'helper' => $latestAlerts['helper'] ?? '',
                'icon'   => $latestAlerts['icon'] ?? 'sa-icon--info',
                'color'  => $latestAlerts['color'] ?? 'info',
            ]);
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        <?php elseif (!empty($latestAlerts['alerts'])) : ?>
            <div class="sa-alerts-list">
                <?php
                foreach ($latestAlerts['alerts'] as $alert) :
                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in template
                    echo TemplateUtils::renderTemplate('admin/components/alert-card', [
                        'iconClass'     => $alert['icon_class'] ?? 'sa-icon--alert',
                        'severityClass' => $alert['severity_class'] ?? 'sa-badge--info',
                        'typeLabel'     => $alert['type_label'] ?? __('Alert', 'site-alerts'),
                        'severity'      => $alert['severity'] ?? 'info',
                        'title'         => $alert['title'] ?? '',
                        'shortMessage'  => $alert['short_message'] ?? ($alert['message'] ?? ''),
                        'expanded'      => $alert['expanded'] ?? [],
                        'alertDate'     => $alert['alert_date'] ?? '',
                    ]);
                    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                endforeach;
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 7-Day History Section -->
    <div class="sa-section">
        <h3 class="sa-section__title"><?php esc_html_e('7-Day History', 'site-alerts'); ?></h3>
        <p class="sa-section__description"><?php esc_html_e('Daily traffic and error statistics.', 'site-alerts'); ?></p>

        <?php if ($history['showTable']) : ?>
            <?php if (!empty($history['staleWarning'])) : ?>
                <p class="sa-history-stale sa-text-muted sa-text-sm">
                    <span class="sa-icon--clock"></span>
                    <?php esc_html_e('Data may be outdated. Last checked over 24 hours ago.', 'site-alerts'); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($history['average'])) : ?>
                <p class="sa-history-average">
                    <span class="sa-icon--traffic"></span>
                    <?php
                    printf(
                    /* translators: 1: average pageviews, 2: average 404 errors */
                        esc_html__('Average per day: %1$s pageviews · %2$s page errors (404)', 'site-alerts'),
                        '<strong>' . esc_html(number_format_i18n($history['average']['pageviews'])) . '</strong>',
                        '<strong>' . esc_html(number_format_i18n($history['average']['errors_404'])) . '</strong>'
                    );
                    ?>
                </p>
            <?php endif; ?>

            <?php
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in template
            echo TemplateUtils::renderTemplate('admin/components/table', [
                'columns'      => [
                    ['key' => 'stats_date', 'label' => __('Date', 'site-alerts'), 'type' => 'date'],
                    ['key' => 'pageviews', 'label' => __('Pageviews', 'site-alerts'), 'type' => 'number'],
                    ['key' => 'errors_404', 'label' => __('404 Errors', 'site-alerts'), 'type' => 'number'],
                ],
                'rows'         => $history['rows'],
                'tableClass'   => 'sa-table--striped',
                'emptyMessage' => $history['emptyMessage'],
            ]);
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        <?php else : ?>
            <?php
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in template
            echo TemplateUtils::renderTemplate('admin/components/message-card', [
                'title'  => $history['title'] ?? __('Building history', 'site-alerts'),
                'text'   => $history['emptyMessage'],
                'helper' => '',
                'icon'   => $history['icon'] ?? 'sa-icon--traffic',
                'color'  => 'info',
            ]);
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        <?php endif; ?>
    </div>

    <!-- Pro Box Section (conditionally shown) -->
    <?php if ($showPromoBanner) : ?>
        <div class="sa-section">
            <?php
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in template
            echo TemplateUtils::renderTemplate('admin/components/promo-card', [
                'badge'       => __('Pro', 'site-alerts'),
                'title'       => __('Never miss a critical issue', 'site-alerts'),
                'description' => __('Site Alerts Pro is coming with advanced monitoring and notification features.', 'site-alerts'),
                'features'    => [
                    __('Performance slowdown alerting', 'site-alerts'),
                    __('Email & Slack notifications', 'site-alerts'),
                    __('Custom alert rules & thresholds', 'site-alerts'),
                    __('Security alerts for suspicious logins and critical changes', 'site-alerts'),
                ],
                'note'        => __('More advanced features are planned for future versions.', 'site-alerts'),
                'buttonText'  => __('See what’s coming in Site Alerts Pro', 'site-alerts'),
                'buttonUrl'   => '#',
                'dismissible' => true,
            ]);
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
    <?php endif; ?>
</div>
