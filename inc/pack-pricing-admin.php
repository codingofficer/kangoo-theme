<?php
defined('ABSPATH') || exit;

function kangoo_pack_pricing_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        __('Kangoo Pack Pricing', 'kangoo'),
        __('Pack Pricing', 'kangoo'),
        'manage_woocommerce',
        'kangoo-pack-pricing',
        'kangoo_render_pack_pricing_admin_page'
    );
}
add_action('admin_menu', 'kangoo_pack_pricing_admin_menu');

function kangoo_pack_pricing_presets() {
    return array(
        'standard' => array(
            'label' => __('Standard', 'kangoo'),
            'regular_price' => '3.99',
            'tiers' => array(
                array('quantity' => 1, 'pack_price' => '2.99', 'badge' => '', 'default_selected' => 1),
                array('quantity' => 3, 'pack_price' => '10.99', 'badge' => 'Popular', 'default_selected' => 0),
                array('quantity' => 5, 'pack_price' => '17.99', 'badge' => 'Save more', 'default_selected' => 0),
                array('quantity' => 10, 'pack_price' => '31.90', 'badge' => 'Best value', 'default_selected' => 0),
            ),
        ),
        'premium' => array(
            'label' => __('Premium / VELO', 'kangoo'),
            'regular_price' => '3.99',
            'tiers' => array(
                array('quantity' => 1, 'pack_price' => '2.99', 'badge' => '', 'default_selected' => 1),
                array('quantity' => 3, 'pack_price' => '11.99', 'badge' => 'Popular', 'default_selected' => 0),
                array('quantity' => 5, 'pack_price' => '19.95', 'badge' => 'Save more', 'default_selected' => 0),
                array('quantity' => 10, 'pack_price' => '38.90', 'badge' => 'Best value', 'default_selected' => 0),
            ),
        ),
        'trial_99p' => array(
            'label' => __('99p Trial', 'kangoo'),
            'regular_price' => '3.99',
            'tiers' => array(),
        ),
    );
}

function kangoo_pack_pricing_format_tiers($tiers) {
    if (empty($tiers) || !is_array($tiers)) {
        return '';
    }

    $parts = array();

    foreach ($tiers as $tier) {
        $quantity = isset($tier['quantity']) ? absint($tier['quantity']) : 0;
        $price = isset($tier['pack_price']) ? wc_format_decimal($tier['pack_price'], 2) : '';

        if (!$quantity || $price === '') {
            continue;
        }

        $badge = isset($tier['badge']) ? sanitize_text_field($tier['badge']) : '';
        $default = !empty($tier['default_selected']) ? '1' : '0';
        $parts[] = implode(':', array($quantity, $price, str_replace(array('|', ':'), ' ', $badge), $default));
    }

    return implode('|', $parts);
}

function kangoo_pack_pricing_parse_tiers($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return array();
    }

    $tiers = array();
    $rows = explode('|', $value);

    foreach ($rows as $row) {
        $columns = array_map('trim', explode(':', $row));
        $quantity = isset($columns[0]) ? absint($columns[0]) : 0;
        $price = isset($columns[1]) ? wc_format_decimal($columns[1], 2) : '';

        if (!$quantity || $price === '') {
            continue;
        }

        $tiers[] = array(
            'quantity' => $quantity,
            'pack_price' => $price,
            'badge' => isset($columns[2]) ? sanitize_text_field($columns[2]) : '',
            'default_selected' => !empty($columns[3]) ? 1 : 0,
        );
    }

    if (!empty($tiers) && !array_filter(wp_list_pluck($tiers, 'default_selected'))) {
        $tiers[0]['default_selected'] = 1;
    }

    return $tiers;
}

function kangoo_pack_pricing_product_rows() {
    $products = get_posts(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'fields' => 'ids',
    ));

    $rows = array();

    foreach ($products as $product_id) {
        $product = wc_get_product($product_id);

        if (!$product || $product->is_type('variation')) {
            continue;
        }

        $rows[] = array(
            'id' => $product_id,
            'sku' => $product->get_sku(),
            'name' => $product->get_name(),
            'brand' => $product->get_attribute('pa_brand') ?: $product->get_attribute('brand'),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'is_99p' => function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product_id),
            'pack_enabled' => get_post_meta($product_id, 'pack_pricing_enabled', true),
            'tiers' => function_exists('kangoo_get_pack_pricing_tiers') ? kangoo_get_pack_pricing_tiers($product_id) : array(),
        );
    }

    return $rows;
}

function kangoo_pack_pricing_write_tiers($product_id, $tiers) {
    $product_id = absint($product_id);

    if (!$product_id) {
        return;
    }

    delete_post_meta($product_id, 'pack_pricing_tiers');
    delete_post_meta($product_id, '_pack_pricing_tiers');

    $count = count($tiers);
    update_post_meta($product_id, 'pack_pricing_tiers', $count);
    update_post_meta($product_id, '_pack_pricing_tiers', 'field_kangoo_pack_pricing_tiers');

    foreach ($tiers as $index => $tier) {
        update_post_meta($product_id, "pack_pricing_tiers_{$index}_quantity", absint($tier['quantity']));
        update_post_meta($product_id, "_pack_pricing_tiers_{$index}_quantity", 'field_kangoo_pack_tier_quantity');
        update_post_meta($product_id, "pack_pricing_tiers_{$index}_pack_price", wc_format_decimal($tier['pack_price'], 2));
        update_post_meta($product_id, "_pack_pricing_tiers_{$index}_pack_price", 'field_kangoo_pack_tier_price');
        update_post_meta($product_id, "pack_pricing_tiers_{$index}_badge", sanitize_text_field($tier['badge']));
        update_post_meta($product_id, "_pack_pricing_tiers_{$index}_badge", 'field_kangoo_pack_tier_badge');
        update_post_meta($product_id, "pack_pricing_tiers_{$index}_default_selected", !empty($tier['default_selected']) ? 1 : 0);
        update_post_meta($product_id, "_pack_pricing_tiers_{$index}_default_selected", 'field_kangoo_pack_tier_default');
    }
}

function kangoo_pack_pricing_apply_row($product_id, $regular_price, $tiers, $is_99p = false) {
    $product = wc_get_product($product_id);

    if (!$product) {
        return;
    }

    $regular_price = wc_format_decimal($regular_price, 2);

    if ($regular_price !== '') {
        $product->set_regular_price($regular_price);
        $product->set_sale_price($is_99p ? kangoo_99p_price() : '2.99');
        $product->set_price($is_99p ? kangoo_99p_price() : '2.99');
        $product->save();
    }

    if ($is_99p || empty($tiers)) {
        update_post_meta($product_id, 'pack_pricing_enabled', 0);
        update_post_meta($product_id, '_pack_pricing_enabled', 'field_kangoo_pack_pricing_enabled');
        kangoo_pack_pricing_write_tiers($product_id, array());
        return;
    }

    update_post_meta($product_id, 'pack_pricing_enabled', 1);
    update_post_meta($product_id, '_pack_pricing_enabled', 'field_kangoo_pack_pricing_enabled');
    kangoo_pack_pricing_write_tiers($product_id, $tiers);
}

function kangoo_pack_pricing_export_csv() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to export pricing.', 'kangoo'));
    }

    check_admin_referer('kangoo_pack_pricing_export');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kangoo-pack-pricing-' . gmdate('Y-m-d-His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'SKU', 'Name', 'Brand', 'Pricing preset', 'Regular price', 'Pack tiers', '99p product'));

    foreach (kangoo_pack_pricing_product_rows() as $row) {
        fputcsv($output, array(
            $row['id'],
            $row['sku'],
            $row['name'],
            $row['brand'],
            $row['is_99p'] ? 'trial_99p' : '',
            $row['regular_price'],
            kangoo_pack_pricing_format_tiers($row['tiers']),
            $row['is_99p'] ? '1' : '0',
        ));
    }

    fclose($output);
    exit;
}
add_action('admin_post_kangoo_pack_pricing_export', 'kangoo_pack_pricing_export_csv');

function kangoo_pack_pricing_import_csv() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to import pricing.', 'kangoo'));
    }

    check_admin_referer('kangoo_pack_pricing_import');

    if (empty($_FILES['kangoo_pack_pricing_csv']['tmp_name'])) {
        wp_safe_redirect(add_query_arg('kangoo_pack_notice', 'missing_file', wp_get_referer()));
        exit;
    }

    $handle = fopen($_FILES['kangoo_pack_pricing_csv']['tmp_name'], 'r');

    if (!$handle) {
        wp_safe_redirect(add_query_arg('kangoo_pack_notice', 'open_failed', wp_get_referer()));
        exit;
    }

    $headers = fgetcsv($handle);
    $updated = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $data = array();

        foreach ($headers as $index => $header) {
            $data[$header] = isset($row[$index]) ? $row[$index] : '';
        }

        $product_id = isset($data['ID']) ? absint($data['ID']) : 0;

        if (!$product_id || get_post_type($product_id) !== 'product') {
            continue;
        }

        $preset_key = isset($data['Pricing preset']) ? sanitize_key($data['Pricing preset']) : '';
        $presets = kangoo_pack_pricing_presets();
        $is_99p = !empty($data['99p product']) || $preset_key === 'trial_99p' || (function_exists('kangoo_is_99p_product') && kangoo_is_99p_product($product_id));

        if ($preset_key && isset($presets[$preset_key])) {
            $regular_price = $presets[$preset_key]['regular_price'];
            $tiers = $presets[$preset_key]['tiers'];
        } else {
            $regular_price = isset($data['Regular price']) ? $data['Regular price'] : '';
            $tiers = isset($data['Pack tiers']) ? kangoo_pack_pricing_parse_tiers($data['Pack tiers']) : array();
        }

        kangoo_pack_pricing_apply_row($product_id, $is_99p ? kangoo_99p_price() : $regular_price, $tiers, $is_99p);
        $updated++;
    }

    fclose($handle);
    wp_safe_redirect(add_query_arg(array('page' => 'kangoo-pack-pricing', 'kangoo_pack_updated' => $updated), admin_url('edit.php?post_type=product')));
    exit;
}
add_action('admin_post_kangoo_pack_pricing_import', 'kangoo_pack_pricing_import_csv');

function kangoo_pack_pricing_bulk_apply() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to update pricing.', 'kangoo'));
    }

    check_admin_referer('kangoo_pack_pricing_bulk_apply');

    $preset_key = isset($_POST['kangoo_pack_preset']) ? sanitize_key(wp_unslash($_POST['kangoo_pack_preset'])) : '';
    $product_ids = isset($_POST['product_ids']) ? array_map('absint', (array) $_POST['product_ids']) : array();
    $presets = kangoo_pack_pricing_presets();

    if (!$preset_key || empty($presets[$preset_key]) || empty($product_ids)) {
        wp_safe_redirect(add_query_arg('kangoo_pack_notice', 'bulk_missing', wp_get_referer()));
        exit;
    }

    $preset = $presets[$preset_key];
    $updated = 0;

    foreach ($product_ids as $product_id) {
        if (get_post_type($product_id) !== 'product') {
            continue;
        }

        kangoo_pack_pricing_apply_row($product_id, $preset['regular_price'], $preset['tiers'], $preset_key === 'trial_99p');
        $updated++;
    }

    wp_safe_redirect(add_query_arg(array('page' => 'kangoo-pack-pricing', 'kangoo_pack_updated' => $updated), admin_url('edit.php?post_type=product')));
    exit;
}
add_action('admin_post_kangoo_pack_pricing_bulk_apply', 'kangoo_pack_pricing_bulk_apply');

function kangoo_render_pack_pricing_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $rows = kangoo_pack_pricing_product_rows();
    $presets = kangoo_pack_pricing_presets();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Kangoo Pack Pricing', 'kangoo'); ?></h1>
        <p><?php esc_html_e('Bulk manage ACF pack pricing tiers. Tier format is quantity:pack_price:badge:default, separated with pipes.', 'kangoo'); ?></p>

        <?php if (isset($_GET['kangoo_pack_updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html(sprintf(__('Updated %d products.', 'kangoo'), absint($_GET['kangoo_pack_updated']))); ?></p>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:minmax(280px,1fr) minmax(280px,1fr);gap:16px;max-width:1100px;margin:18px 0;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="background:#fff;border:1px solid #dcdcde;padding:16px;border-radius:8px;">
                <?php wp_nonce_field('kangoo_pack_pricing_export'); ?>
                <input type="hidden" name="action" value="kangoo_pack_pricing_export">
                <h2><?php esc_html_e('Export editable CSV', 'kangoo'); ?></h2>
                <p><?php esc_html_e('Open in Excel, change preset or tier strings, then import it back here.', 'kangoo'); ?></p>
                <?php submit_button(__('Download pack pricing CSV', 'kangoo'), 'secondary', 'submit', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="background:#fff;border:1px solid #dcdcde;padding:16px;border-radius:8px;">
                <?php wp_nonce_field('kangoo_pack_pricing_import'); ?>
                <input type="hidden" name="action" value="kangoo_pack_pricing_import">
                <h2><?php esc_html_e('Import edited CSV', 'kangoo'); ?></h2>
                <input type="file" name="kangoo_pack_pricing_csv" accept=".csv" required>
                <?php submit_button(__('Import pricing', 'kangoo'), 'primary', 'submit', false); ?>
            </form>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('kangoo_pack_pricing_bulk_apply'); ?>
            <input type="hidden" name="action" value="kangoo_pack_pricing_bulk_apply">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="kangoo_pack_preset" required>
                        <option value=""><?php esc_html_e('Apply pricing preset...', 'kangoo'); ?></option>
                        <?php foreach ($presets as $key => $preset) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($preset['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button(__('Apply to selected', 'kangoo'), 'secondary', 'submit', false); ?>
                </div>
            </div>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" onclick="document.querySelectorAll('.kangoo-pack-product').forEach((box)=>box.checked=this.checked);"></td>
                        <th><?php esc_html_e('Product', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Brand', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Regular', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Pack pricing', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Pack tiers', 'kangoo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <th class="check-column"><input class="kangoo-pack-product" type="checkbox" name="product_ids[]" value="<?php echo esc_attr($row['id']); ?>"></th>
                            <td>
                                <strong><a href="<?php echo esc_url(get_edit_post_link($row['id'])); ?>"><?php echo esc_html($row['name']); ?></a></strong>
                                <br><code><?php echo esc_html($row['sku'] ?: ('#' . $row['id'])); ?></code>
                                <?php if ($row['is_99p']) : ?>
                                    <span class="dashicons dashicons-tag" title="<?php esc_attr_e('99p trial product', 'kangoo'); ?>"></span>
                                    <strong><?php esc_html_e('99p', 'kangoo'); ?></strong>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($row['brand']); ?></td>
                            <td><?php echo esc_html($row['regular_price']); ?></td>
                            <td><?php echo $row['pack_enabled'] ? esc_html__('Enabled', 'kangoo') : esc_html__('Off', 'kangoo'); ?></td>
                            <td><code><?php echo esc_html(kangoo_pack_pricing_format_tiers($row['tiers'])); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
    <?php
}
