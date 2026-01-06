<?php
/**
 * Promo Card Component
 *
 * Displays a premium upsell card with features list and CTA button.
 * Optionally dismissible with a close button.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 *
 * @var string $badge Badge text (e.g., "Pro")
 * @var string $title Card title
 * @var string $description Card description
 * @var array $features List of feature strings
 * @var string $buttonText CTA button text
 * @var string $buttonUrl CTA button URL
 * @var bool $dismissible Whether the card can be dismissed
 * @var string $dismissNonce Nonce for dismiss AJAX action
 */

defined('ABSPATH') || exit;

$badge       = $badge ?? __('Pro', 'site-alerts');
$title       = $title ?? __('Site Alerts Pro', 'site-alerts');
$description = $description ?? '';
$features    = $features ?? [];
$note        = $note ?? '';
$buttonText  = $buttonText ?? __('Upgrade to Pro', 'site-alerts');
$buttonUrl   = $buttonUrl ?? '#';
$dismissible = $dismissible ?? false;
?>

<div class="sa-card sa-promo-card"<?php echo $dismissible ? ' data-sa-dismissible="true"' : ''; ?>>
    <?php if ($dismissible) : ?>
        <button
            type="button"
            class="sa-promo-card__dismiss"
            data-sa-action="dismiss-promo"
            aria-label="<?php esc_attr_e('Dismiss', 'site-alerts'); ?>"
        >
            <span class="sa-icon--close"></span>
        </button>
    <?php endif; ?>
    <div class="sa-promo-card__body">
        <?php if (!empty($badge)) : ?>
            <span class="sa-promo-card__badge">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <polygon points="12,2 15,9 22,9 17,14 19,22 12,17 5,22 7,14 2,9 9,9"/>
                </svg>
                <?php echo esc_html($badge); ?>
            </span>
        <?php endif; ?>
        <h4 class="sa-promo-card__title"><?php echo esc_html($title); ?></h4>
        <?php if (!empty($description)) : ?>
            <p class="sa-promo-card__description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php if (!empty($features)) : ?>
            <ul class="sa-promo-card__features sa-mb-3">
                <?php foreach ($features as $feature) : ?>
                    <li>
                        <span class="sa-promo-card__check"></span>
                        <?php echo esc_html($feature); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($note)) : ?>
            <p class="sa-promo-card__note">
                <?php echo esc_html($note); ?>
            </p>
        <?php endif; ?>
        <a href="<?php echo esc_url($buttonUrl); ?>" class="sa-btn sa-btn--gold" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <polygon points="12,2 15,9 22,9 17,14 19,22 12,17 5,22 7,14 2,9 9,9"/>
            </svg>
            <?php echo esc_html($buttonText); ?>
        </a>
    </div>
</div>
