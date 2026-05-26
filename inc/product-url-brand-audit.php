<?php
defined('ABSPATH') || exit;

function kangoo_brand_url_audit_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        __('Product URL Brand Audit', 'kangoo'),
        __('URL Brand Audit', 'kangoo'),
        'manage_woocommerce',
        'kangoo-url-brand-audit',
        'kangoo_render_brand_url_audit_admin_page'
    );
}
add_action('admin_menu', 'kangoo_brand_url_audit_admin_menu');

function kangoo_brand_url_audit_brand_variants($brand) {
    $brand = trim((string) $brand);

    if ($brand === '') {
        return array();
    }

    $slug = sanitize_title($brand);
    $variants = array_filter(array_unique(array(
        $slug,
        str_replace('-', '', $slug),
    )));

    return array_values($variants);
}

function kangoo_brand_url_audit_slug_has_brand($slug, $brand) {
    $slug = sanitize_title($slug);
    $compact_slug = str_replace('-', '', $slug);

    foreach (kangoo_brand_url_audit_brand_variants($brand) as $variant) {
        if (strpos($slug, $variant) !== false || strpos($compact_slug, $variant) !== false) {
            return true;
        }
    }

    return false;
}

function kangoo_brand_url_audit_term_matches_brand($term, $brand) {
    if (!$term || empty($term->slug)) {
        return false;
    }

    $brand_variants = kangoo_brand_url_audit_brand_variants($brand);

    if (empty($brand_variants)) {
        return false;
    }

    $term_slug = sanitize_title($term->slug);
    $term_name = sanitize_title($term->name);
    $compact_slug = str_replace('-', '', $term_slug);
    $compact_name = str_replace('-', '', $term_name);

    foreach ($brand_variants as $variant) {
        if ($term_slug === $variant || $term_name === $variant || $compact_slug === $variant || $compact_name === $variant) {
            return true;
        }
    }

    return false;
}

function kangoo_brand_url_audit_find_brand_category_term($brand) {
    $terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ));

    if (empty($terms) || is_wp_error($terms)) {
        return null;
    }

    foreach ($terms as $term) {
        if (kangoo_brand_url_audit_term_matches_brand($term, $brand)) {
            return $term;
        }
    }

    return null;
}

function kangoo_brand_url_audit_get_product_brand_category_term($product_id, $brand, $terms = null) {
    $brand_variants = kangoo_brand_url_audit_brand_variants($brand);

    if (empty($brand_variants)) {
        return null;
    }

    if ($terms === null) {
        $terms = wp_get_post_terms($product_id, 'product_cat');
    }

    if (empty($terms) || is_wp_error($terms)) {
        return null;
    }

    foreach ($terms as $term) {
        if (kangoo_brand_url_audit_term_matches_brand($term, $brand)) {
            return $term;
        }
    }

    return null;
}

function kangoo_brand_url_audit_sync_product_brand_category($product_id) {
    $product_id = absint($product_id);

    if (!$product_id || get_post_type($product_id) !== 'product' || !function_exists('wc_get_product')) {
        return false;
    }

    $product = wc_get_product($product_id);

    if (!$product || $product->is_type('variation')) {
        return false;
    }

    $brand = function_exists('kangoo_get_product_brand_label') ? kangoo_get_product_brand_label($product) : trim((string) $product->get_attribute('pa_brand'));
    $brand_term = kangoo_brand_url_audit_find_brand_category_term($brand);

    if (!$brand_term) {
        return false;
    }

    $current_term_ids = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));

    if (is_wp_error($current_term_ids)) {
        return false;
    }

    if (in_array((int) $brand_term->term_id, array_map('intval', $current_term_ids), true)) {
        return false;
    }

    $result = wp_set_object_terms($product_id, array((int) $brand_term->term_id), 'product_cat', true);

    if (is_wp_error($result)) {
        return false;
    }

    clean_post_cache($product_id);

    return true;
}

function kangoo_brand_url_audit_sync_product_brand_category_on_save($product_id) {
    kangoo_brand_url_audit_sync_product_brand_category($product_id);
}
add_action('woocommerce_new_product', 'kangoo_brand_url_audit_sync_product_brand_category_on_save', 20);
add_action('woocommerce_update_product', 'kangoo_brand_url_audit_sync_product_brand_category_on_save', 20);

function kangoo_brand_url_audit_prefer_brand_product_category($category, $terms, $post) {
    if (!$post || empty($post->ID) || get_post_type($post->ID) !== 'product') {
        return $category;
    }

    $product = wc_get_product($post->ID);

    if (!$product) {
        return $category;
    }

    $brand = function_exists('kangoo_get_product_brand_label') ? kangoo_get_product_brand_label($product) : trim((string) $product->get_attribute('pa_brand'));
    $brand_term = kangoo_brand_url_audit_get_product_brand_category_term($post->ID, $brand, $terms);

    return $brand_term ?: $category;
}
add_filter('wc_product_post_type_link_product_cat', 'kangoo_brand_url_audit_prefer_brand_product_category', 20, 3);

function kangoo_brand_url_audit_remove_leading_brand_from_slug($slug, $brand) {
    $slug = sanitize_title($slug);

    foreach (kangoo_brand_url_audit_brand_variants($brand) as $variant) {
        if ($slug === $variant) {
            return $slug;
        }

        if (strpos($slug, $variant . '-') === 0) {
            return substr($slug, strlen($variant) + 1);
        }
    }

    return $slug;
}

function kangoo_brand_url_audit_product_rows() {
    $product_ids = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => array('publish', 'draft', 'private'),
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ));

    $rows = array();

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);

        if (!$product || $product->is_type('variation')) {
            continue;
        }

        $brand = function_exists('kangoo_get_product_brand_label') ? kangoo_get_product_brand_label($product) : trim((string) $product->get_attribute('pa_brand'));
        $slug = get_post_field('post_name', $product_id);
        $url = get_permalink($product_id);
        $url_path = kangoo_brand_url_audit_normalize_path($url);
        $category_terms = wp_get_post_terms($product_id, 'product_cat');
        $brand_category_term = kangoo_brand_url_audit_get_product_brand_category_term($product_id, $brand, $category_terms);
        $brand_variants = kangoo_brand_url_audit_brand_variants($brand);
        $has_brand_in_slug = $brand !== '' && kangoo_brand_url_audit_slug_has_brand($slug, $brand);
        $has_brand_in_url = $brand !== '' && kangoo_brand_url_audit_slug_has_brand($url_path, $brand);
        $status = $brand === '' ? 'no_brand_detected' : ($has_brand_in_url ? 'ok' : 'missing_brand_in_url');

        if ($status === 'ok' && $brand_category_term && $has_brand_in_slug) {
            $status = 'brand_repeated_in_slug';
        }

        $categories = !is_wp_error($category_terms) ? wp_list_pluck($category_terms, 'name') : array();
        $suggested_slug = $slug;

        if ($brand !== '') {
            if ($brand_category_term) {
                $suggested_slug = kangoo_brand_url_audit_remove_leading_brand_from_slug($slug, $brand);
            } elseif (!$has_brand_in_url) {
                $suggested_slug = sanitize_title($brand . '-' . $slug);
            }
        }

        $rows[] = array(
            'status'          => $status,
            'id'              => $product_id,
            'sku'             => $product->get_sku(),
            'name'            => $product->get_name(),
            'brand'           => $brand,
            'brand_variants'  => implode('|', $brand_variants),
            'brand_category'  => $brand_category_term ? $brand_category_term->slug : '',
            'brand_in_slug'   => $has_brand_in_slug ? '1' : '0',
            'brand_in_url'    => $has_brand_in_url ? '1' : '0',
            'slug'            => $slug,
            'suggested_slug'  => $suggested_slug,
            'url_path'        => $url_path,
            'suggested_path'  => kangoo_brand_url_audit_normalize_path($url),
            'url'             => $url,
            'edit_url'        => get_edit_post_link($product_id, 'raw'),
            'product_status'  => get_post_status($product_id),
            'stock_status'    => $product->get_stock_status(),
            'categories'      => !empty($categories) ? implode('|', $categories) : '',
        );
    }

    return $rows;
}

function kangoo_brand_url_audit_filtered_rows($scope = 'missing') {
    $rows = kangoo_brand_url_audit_product_rows();

    if ($scope === 'all') {
        return $rows;
    }

    if ($scope === 'no_brand') {
        return array_values(array_filter($rows, function ($row) {
            return $row['status'] === 'no_brand_detected';
        }));
    }

    return array_values(array_filter($rows, function ($row) {
        return in_array($row['status'], array('missing_brand_in_url', 'brand_repeated_in_slug'), true);
    }));
}

function kangoo_brand_url_audit_normalize_path($url_or_path) {
    $path = (string) wp_parse_url((string) $url_or_path, PHP_URL_PATH);

    if ($path === '') {
        $path = (string) $url_or_path;
    }

    return trim($path, '/');
}

function kangoo_brand_url_audit_redirects() {
    $redirects = get_option('kangoo_brand_url_redirects', array());

    return is_array($redirects) ? $redirects : array();
}

function kangoo_brand_url_audit_save_redirect($old_url, $new_url, $product_id) {
    $old_path = kangoo_brand_url_audit_normalize_path($old_url);
    $new_url = esc_url_raw($new_url);

    if ($old_path === '' || !$new_url) {
        return;
    }

    $redirects = kangoo_brand_url_audit_redirects();
    $redirects[$old_path] = array(
        'product_id' => absint($product_id),
        'target'     => $new_url,
        'created'    => current_time('mysql'),
    );

    update_option('kangoo_brand_url_redirects', $redirects, false);
}

function kangoo_brand_url_audit_redirect_old_urls() {
    if (is_admin() || empty($_SERVER['REQUEST_URI'])) {
        return;
    }

    $request_path = kangoo_brand_url_audit_normalize_path(wp_unslash($_SERVER['REQUEST_URI']));

    if ($request_path === '') {
        return;
    }

    $redirects = kangoo_brand_url_audit_redirects();

    if (empty($redirects[$request_path]['target'])) {
        if (!preg_match('#^product/([^/]+)/([^/]+)/?$#', $request_path, $matches)) {
            return;
        }

        $product = get_page_by_path(sanitize_title($matches[2]), OBJECT, 'product');

        if (!$product || empty($product->ID)) {
            return;
        }

        $target = get_permalink($product->ID);
        $target_path = kangoo_brand_url_audit_normalize_path($target);

        if (!$target || $target_path === '' || $target_path === $request_path) {
            return;
        }

        wp_safe_redirect($target, 301);
        exit;
    }

    $target = esc_url_raw($redirects[$request_path]['target']);

    if (!$target) {
        return;
    }

    wp_safe_redirect($target, 301);
    exit;
}
add_action('template_redirect', 'kangoo_brand_url_audit_redirect_old_urls', 1);

function kangoo_brand_url_audit_apply_missing_slugs() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to update product URLs.', 'kangoo'));
    }

    check_admin_referer('kangoo_brand_url_audit_apply');

    $rows = kangoo_brand_url_audit_filtered_rows('missing');
    $updated = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($rows as $row) {
        $product_id = absint($row['id']);
        $suggested_slug = sanitize_title($row['suggested_slug']);

        if (!$product_id || $suggested_slug === '' || get_post_type($product_id) !== 'product') {
            $skipped++;
            continue;
        }

        $old_slug = get_post_field('post_name', $product_id);

        if ($old_slug === $suggested_slug) {
            $skipped++;
            continue;
        }

        $old_url = get_permalink($product_id);
        $post_status = get_post_status($product_id);
        $post_parent = wp_get_post_parent_id($product_id);
        $new_slug = wp_unique_post_slug($suggested_slug, $product_id, $post_status, 'product', $post_parent);

        $result = wp_update_post(array(
            'ID'        => $product_id,
            'post_name' => $new_slug,
        ), true);

        if (is_wp_error($result)) {
            $failed++;
            continue;
        }

        clean_post_cache($product_id);
        $new_url = get_permalink($product_id);
        kangoo_brand_url_audit_save_redirect($old_url, $new_url, $product_id);
        update_post_meta($product_id, '_kangoo_brand_url_previous_slug', $old_slug);
        update_post_meta($product_id, '_kangoo_brand_url_updated_at', current_time('mysql'));
        $updated++;
    }

    wp_safe_redirect(add_query_arg(array(
        'page'                      => 'kangoo-url-brand-audit',
        'kangoo_url_brand_updated'  => $updated,
        'kangoo_url_brand_skipped'  => $skipped,
        'kangoo_url_brand_failed'   => $failed,
    ), admin_url('edit.php?post_type=product')));
    exit;
}
add_action('admin_post_kangoo_brand_url_audit_apply', 'kangoo_brand_url_audit_apply_missing_slugs');

function kangoo_brand_url_audit_sync_categories() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to sync product brand categories.', 'kangoo'));
    }

    check_admin_referer('kangoo_brand_url_audit_sync_categories');

    $product_ids = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => array('publish', 'draft', 'private'),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));
    $synced = 0;
    $skipped = 0;

    foreach ($product_ids as $product_id) {
        if (kangoo_brand_url_audit_sync_product_brand_category($product_id)) {
            $synced++;
        } else {
            $skipped++;
        }
    }

    wp_safe_redirect(add_query_arg(array(
        'page'                            => 'kangoo-url-brand-audit',
        'kangoo_url_brand_synced'         => $synced,
        'kangoo_url_brand_sync_skipped'   => $skipped,
    ), admin_url('edit.php?post_type=product')));
    exit;
}
add_action('admin_post_kangoo_brand_url_audit_sync_categories', 'kangoo_brand_url_audit_sync_categories');

function kangoo_brand_url_audit_export_csv() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('You do not have permission to export product URL audits.', 'kangoo'));
    }

    check_admin_referer('kangoo_brand_url_audit_export');

    $scope = isset($_GET['scope']) ? sanitize_key(wp_unslash($_GET['scope'])) : 'missing';

    if (!in_array($scope, array('missing', 'no_brand', 'all'), true)) {
        $scope = 'missing';
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kangoo-product-url-brand-audit-' . $scope . '-' . gmdate('Y-m-d-His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array(
        'Status',
        'Product ID',
        'SKU',
        'Product name',
        'Brand',
        'Expected brand slug variants',
        'Brand category in URL path',
        'Brand in current slug',
        'Brand in full URL path',
        'Current slug',
        'Suggested slug',
        'Current URL path',
        'Suggested URL path',
        'Product URL',
        'Edit URL',
        'Product status',
        'Stock status',
        'Categories',
    ));

    foreach (kangoo_brand_url_audit_filtered_rows($scope) as $row) {
        fputcsv($output, array(
            $row['status'],
            $row['id'],
            $row['sku'],
            $row['name'],
            $row['brand'],
            $row['brand_variants'],
            $row['brand_category'],
            $row['brand_in_slug'],
            $row['brand_in_url'],
            $row['slug'],
            $row['suggested_slug'],
            $row['url_path'],
            $row['suggested_path'],
            $row['url'],
            $row['edit_url'],
            $row['product_status'],
            $row['stock_status'],
            $row['categories'],
        ));
    }

    fclose($output);
    exit;
}
add_action('admin_post_kangoo_brand_url_audit_export', 'kangoo_brand_url_audit_export_csv');

function kangoo_brand_url_audit_export_url($scope) {
    return wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'kangoo_brand_url_audit_export',
                'scope'  => sanitize_key($scope),
            ),
            admin_url('admin-post.php')
        ),
        'kangoo_brand_url_audit_export'
    );
}

function kangoo_brand_url_audit_apply_url() {
    return wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'kangoo_brand_url_audit_apply',
            ),
            admin_url('admin-post.php')
        ),
        'kangoo_brand_url_audit_apply'
    );
}

function kangoo_brand_url_audit_sync_categories_url() {
    return wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'kangoo_brand_url_audit_sync_categories',
            ),
            admin_url('admin-post.php')
        ),
        'kangoo_brand_url_audit_sync_categories'
    );
}

function kangoo_render_brand_url_audit_admin_page() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    $missing_rows = kangoo_brand_url_audit_filtered_rows('missing');
    $no_brand_rows = kangoo_brand_url_audit_filtered_rows('no_brand');
    $all_rows = kangoo_brand_url_audit_filtered_rows('all');
    $preview_rows = array_slice($missing_rows, 0, 50);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Product URL Brand Audit', 'kangoo'); ?></h1>
        <p><?php esc_html_e('Find products where Kangoo can detect a brand but the full product URL path does not contain it, plus products where the brand is repeated in the final slug. The preferred pattern is /product/brand/flavour-format-strength/, for example /product/zyn/black-cherry-mini-3mg/.', 'kangoo'); ?></p>

        <?php if (isset($_GET['kangoo_url_brand_updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    echo esc_html(sprintf(
                        __('Updated %1$d product URLs, skipped %2$d, failed %3$d. Old URLs were stored as 301 redirects.', 'kangoo'),
                        absint($_GET['kangoo_url_brand_updated']),
                        isset($_GET['kangoo_url_brand_skipped']) ? absint($_GET['kangoo_url_brand_skipped']) : 0,
                        isset($_GET['kangoo_url_brand_failed']) ? absint($_GET['kangoo_url_brand_failed']) : 0
                    ));
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['kangoo_url_brand_synced'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    echo esc_html(sprintf(
                        __('Synced %1$d products to their matching brand category, skipped %2$d.', 'kangoo'),
                        absint($_GET['kangoo_url_brand_synced']),
                        isset($_GET['kangoo_url_brand_sync_skipped']) ? absint($_GET['kangoo_url_brand_sync_skipped']) : 0
                    ));
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;max-width:980px;margin:18px 0;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px;">
                <strong style="display:block;font-size:24px;"><?php echo esc_html(count($missing_rows)); ?></strong>
                <span><?php esc_html_e('Needs URL cleanup', 'kangoo'); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px;">
                <strong style="display:block;font-size:24px;"><?php echo esc_html(count($no_brand_rows)); ?></strong>
                <span><?php esc_html_e('No brand detected', 'kangoo'); ?></span>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px;">
                <strong style="display:block;font-size:24px;"><?php echo esc_html(count($all_rows)); ?></strong>
                <span><?php esc_html_e('Products scanned', 'kangoo'); ?></span>
            </div>
        </div>

        <p>
            <a class="button button-primary" href="<?php echo esc_url(kangoo_brand_url_audit_export_url('missing')); ?>"><?php esc_html_e('Download missing-brand CSV', 'kangoo'); ?></a>
            <a class="button" href="<?php echo esc_url(kangoo_brand_url_audit_export_url('no_brand')); ?>"><?php esc_html_e('Download no-brand CSV', 'kangoo'); ?></a>
            <a class="button" href="<?php echo esc_url(kangoo_brand_url_audit_export_url('all')); ?>"><?php esc_html_e('Download full audit CSV', 'kangoo'); ?></a>
            <a class="button" href="<?php echo esc_url(kangoo_brand_url_audit_sync_categories_url()); ?>" onclick="return confirm('<?php echo esc_js(__('Add matching brand product categories to products based on their selected Brand?', 'kangoo')); ?>');"><?php esc_html_e('Sync brand categories now', 'kangoo'); ?></a>
        </p>

        <?php if (!empty($missing_rows)) : ?>
            <div style="max-width:980px;margin:18px 0;padding:16px;border:1px solid #dba617;border-radius:8px;background:#fff8e5;">
                <h2 style="margin-top:0;"><?php esc_html_e('Apply brand-led product URLs', 'kangoo'); ?></h2>
                <p><?php esc_html_e('This only updates product slugs when the slug itself needs cleaning. Brand folder URLs are handled automatically by the theme when a matching brand category is assigned to the product. Future saved products will get that matching category automatically.', 'kangoo'); ?></p>
                <p><strong><?php esc_html_e('Do this once after reviewing the preview/export. Old product/category paths are redirected to the preferred product URL.', 'kangoo'); ?></strong></p>
                <a class="button button-primary" href="<?php echo esc_url(kangoo_brand_url_audit_apply_url()); ?>" onclick="return confirm('<?php echo esc_js(__('Update product URL slugs and save redirects for all missing-brand rows?', 'kangoo')); ?>');"><?php esc_html_e('Fix missing brand URLs + save redirects', 'kangoo'); ?></a>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e('Preview: URL cleanup needed', 'kangoo'); ?></h2>
        <?php if (empty($preview_rows)) : ?>
            <div class="notice notice-success inline">
                <p><?php esc_html_e('No products need brand URL cleanup.', 'kangoo'); ?></p>
            </div>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Product', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Brand', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Current slug', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Current URL path', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Brand category', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Suggested slug', 'kangoo'); ?></th>
                        <th><?php esc_html_e('Status', 'kangoo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_rows as $row) : ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo esc_url($row['edit_url']); ?>"><?php echo esc_html($row['name']); ?></a></strong>
                                <br>
                                <small><?php echo esc_html($row['url']); ?></small>
                            </td>
                            <td><?php echo esc_html($row['brand']); ?></td>
                            <td><code><?php echo esc_html($row['slug']); ?></code></td>
                            <td><code><?php echo esc_html($row['url_path']); ?></code></td>
                            <td><code><?php echo esc_html($row['brand_category']); ?></code></td>
                            <td><code><?php echo esc_html($row['suggested_slug']); ?></code></td>
                            <td><?php echo esc_html($row['product_status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (count($missing_rows) > count($preview_rows)) : ?>
                <p><?php echo esc_html(sprintf(__('Showing first %1$d of %2$d products. Download the CSV for the full list.', 'kangoo'), count($preview_rows), count($missing_rows))); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
