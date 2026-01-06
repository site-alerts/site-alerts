<?php
/**
 * Table Component
 *
 * Displays data in a responsive table format.
 * Based on Sneat/Vuexy table design.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are locally scoped via include.
 *
 * @package SiteAlerts
 * @version 1.0.0
 *
 * @var array $columns Array of column definitions: [['key' => 'column_key', 'label' => 'Column Label'], ...]
 * @var array $rows Array of row data: [['column_key' => 'value', ...], ...]
 * @var string $tableClass Additional CSS classes for the table
 * @var string $emptyMessage Message to display when there are no rows
 */

defined('ABSPATH') || exit;

$columns      = $columns ?? [];
$rows         = $rows ?? [];
$tableClass   = $tableClass ?? '';
$emptyMessage = $emptyMessage ?? __('No data available.', 'site-alerts');
?>

<div class="sa-card sa-table-card">
    <div class="sa-table-responsive">
        <table class="sa-table <?php echo esc_attr($tableClass); ?>">
            <?php if (!empty($columns)) : ?>
                <thead>
                    <tr>
                        <?php foreach ($columns as $column) : ?>
                            <th><?php echo esc_html($column['label'] ?? ''); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
            <?php endif; ?>
            <tbody>
                <?php if (!empty($rows)) : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <?php foreach ($columns as $column) : ?>
                                <?php
                                $key   = $column['key'] ?? '';
                                $value = $row[$key] ?? '';

                                // Format date columns
                                if (isset($column['type']) && $column['type'] === 'date' && !empty($value)) {
                                    $timestamp = strtotime($value);
                                    if ($timestamp !== false) {
                                        $value = wp_date(get_option('date_format'), $timestamp);
                                    }
                                }

                                // Format number columns
                                if (isset($column['type']) && $column['type'] === 'number') {
                                    $value = number_format_i18n((int) $value);
                                }
                                ?>
                                <td><?php echo esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="<?php echo esc_attr(count($columns)); ?>" class="sa-table__empty">
                            <?php echo esc_html($emptyMessage); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
