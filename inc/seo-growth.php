<?php

defined('ABSPATH') || exit;

function kangoo_seo_brand_slugs() {
    return array('fumi', 'killa', 'nordic-spirit', 'pablo', 'ubbs', 'velo', 'xqs', 'zyn');
}

function kangoo_seo_matching_product_category($slug) {
    $term = get_term_by('slug', sanitize_title($slug), 'product_cat');
    return $term instanceof WP_Term ? $term : null;
}

function kangoo_seo_canonical_brand_link($url, $term, $taxonomy) {
    if ($taxonomy !== 'product_brand' || !$term instanceof WP_Term) {
        return $url;
    }

    $category = kangoo_seo_matching_product_category($term->slug);
    $category_url = $category ? get_term_link($category) : '';

    return !is_wp_error($category_url) && $category_url ? $category_url : $url;
}
add_filter('term_link', 'kangoo_seo_canonical_brand_link', 10, 3);

function kangoo_seo_exclude_brand_taxonomy_sitemap($excluded, $taxonomy) {
    return $taxonomy === 'product_brand' ? true : $excluded;
}
add_filter('wpseo_sitemap_exclude_taxonomy', 'kangoo_seo_exclude_brand_taxonomy_sitemap', 10, 2);

function kangoo_seo_noindex_thin_facets($robots) {
    if ((function_exists('is_shop') && is_shop()) || is_search() || is_404()) {
        $robots['index'] = 'noindex';
        $robots['follow'] = 'follow';
        return $robots;
    }

    if (!is_tax(array('pa_flavour', 'pa_strength'))) {
        return $robots;
    }

    $term = get_queried_object();

    if ($term instanceof WP_Term && (int) $term->count < 2) {
        $robots['index'] = 'noindex';
        $robots['follow'] = 'follow';
    }

    return $robots;
}
add_filter('wpseo_robots_array', 'kangoo_seo_noindex_thin_facets');

function kangoo_seo_exclude_utility_pages_from_sitemap($post_ids) {
    $utility_ids = array_filter(array(
        function_exists('wc_get_page_id') ? wc_get_page_id('cart') : 0,
        function_exists('wc_get_page_id') ? wc_get_page_id('checkout') : 0,
        function_exists('wc_get_page_id') ? wc_get_page_id('myaccount') : 0,
        function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0,
    ));

    return array_values(array_unique(array_merge((array) $post_ids, array_map('absint', $utility_ids))));
}
add_filter('wpseo_exclude_from_sitemap_by_post_ids', 'kangoo_seo_exclude_utility_pages_from_sitemap');

function kangoo_seo_exclude_thin_facets_from_sitemap($term_ids) {
    $thin_ids = get_terms(array(
        'taxonomy' => array('pa_flavour', 'pa_strength'),
        'hide_empty' => false,
        'fields' => 'ids',
        'number' => 0,
        'meta_query' => array(),
    ));

    if (is_wp_error($thin_ids)) {
        return $term_ids;
    }

    $thin_ids = array_filter($thin_ids, static function ($term_id) {
        $term = get_term($term_id);
        return $term instanceof WP_Term && (int) $term->count < 2;
    });

    return array_values(array_unique(array_merge((array) $term_ids, array_map('absint', $thin_ids))));
}
add_filter('wpseo_exclude_from_sitemap_by_term_ids', 'kangoo_seo_exclude_thin_facets_from_sitemap');

function kangoo_seo_redirect_legacy_urls() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (is_tax('product_brand')) {
        $term = get_queried_object();
        $category = $term instanceof WP_Term ? kangoo_seo_matching_product_category($term->slug) : null;

        if ($category) {
            wp_safe_redirect(get_term_link($category), 301, 'Kangoo canonical brand archive');
            exit;
        }
    }

    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $redirects = array(
        'blog/best-nicotine-pouches-uk-2026-top-brands-flavours-strengths' => 'blog/best-nicotine-pouches-uk',
        'blog/how-to-use-nicotine-pouches' => 'blog/how-to-use-nicotine-pouches-placement-timing-mistakes',
        'blog/zyn-vs-velo-nicotine-pouches' => 'blog/zyn-vs-velo',
        'blog/strongest-nicotine-pouches-uk' => 'blog/strong-nicotine-pouches-uk-strength-guide',
        'blog/snus-meaning-a-simple-uk-guide' => 'blog/what-is-snus',
        'blog/what-is-snus-a-uk-guide-to-snus-and-nicotine-pouches' => 'blog/what-is-snus',
        'blog/nicotine-pouches-for-beginners' => 'blog/what-are-nicotine-pouches',
        'blog/what-are-nicotine-pouches-a-complete-uk-guide' => 'blog/what-are-nicotine-pouches',
        'blog/snus-uk-legal-alternatives-and-what-to-know' => 'blog/snus-uk',
        'blog/nicotine-pouches-from-2-99-kangoo-single-tin-prices' => 'blog/nicotine-pouches-from-3-99-kangoo-single-tin-prices',
    );

    if (isset($redirects[$path])) {
        wp_safe_redirect(home_url('/' . $redirects[$path] . '/'), 301, 'Kangoo content consolidation');
        exit;
    }
}
add_action('template_redirect', 'kangoo_seo_redirect_legacy_urls', 1);

function kangoo_seo_robots_txt($output, $public) {
    if (!$public) {
        return $output;
    }

    $rules = array(
        'Disallow: /cart/',
        'Disallow: /checkout/',
        'Disallow: /my-account/',
        'Disallow: /*?s=',
        'Disallow: /*?add-to-cart=',
        'Disallow: /*?orderby=',
        'Disallow: /*?filter_',
    );

    foreach ($rules as $rule) {
        if (strpos($output, $rule) === false) {
            $output .= "\n" . $rule;
        }
    }

    if (strpos($output, 'Sitemap:') === false) {
        $output .= "\nSitemap: " . home_url('/sitemap_index.xml');
    }

    return trim($output) . "\n";
}
add_filter('robots_txt', 'kangoo_seo_robots_txt', 20, 2);

function kangoo_seo_free_shipping_threshold() {
    $settings = get_option('woocommerce_free_shipping_1_settings', array());
    return isset($settings['min_amount']) ? (float) $settings['min_amount'] : 14.95;
}

function kangoo_seo_plain_price($product) {
    if (!$product instanceof WC_Product || $product->get_price() === '') {
        return '';
    }

    $price = html_entity_decode(wp_strip_all_tags(wc_price(wc_get_price_to_display($product))), ENT_QUOTES, 'UTF-8');
    return preg_replace('/£\s*/u', 'GBP ', $price);
}

function kangoo_seo_key_links() {
    return array(
        'Nicotine Pouches UK' => home_url('/product-category/nicotine-pouches/'),
        '99p Nicotine Pouches - now from 79p' => home_url('/product-category/99p-pouches/'),
        'VELO Nicotine Pouches' => home_url('/product-category/velo/'),
        'ZYN Nicotine Pouches' => home_url('/product-category/zyn/'),
        'Nordic Spirit Nicotine Pouches' => home_url('/product-category/nordic-spirit/'),
        'KILLA Nicotine Pouches' => home_url('/product-category/killa/'),
        'What are nicotine pouches?' => home_url('/blog/what-are-nicotine-pouches/'),
        'Snus UK guide' => home_url('/blog/snus-uk/'),
        'What is snus?' => home_url('/blog/what-is-snus/'),
        'Sitemap' => home_url('/sitemap_index.xml'),
    );
}

function kangoo_seo_render_llms_summary() {
    $threshold = kangoo_seo_free_shipping_threshold();
    $lines = array(
        '# Kangoo Pouches',
        '',
        '> UK online retailer of tobacco-free nicotine pouches for adults aged 18 and over.',
        '',
        'Kangoo Pouches sells nicotine pouches from stocked brands including VELO, ZYN, Nordic Spirit, KILLA, PABLO, FUMi, Ubbs and XQS. Traditional tobacco snus is not sold. Educational snus content explains the distinction and legal tobacco-free alternatives in the UK.',
        '',
        '- Market: United Kingdom',
        '- Audience: adults aged 18+',
        '- Free delivery threshold: GBP ' . number_format($threshold, 2),
        '- Dispatch: orders placed before 2pm Monday-Friday are dispatched the same day, excluding bank holidays',
        '- Catalogue: live prices and stock are authoritative on canonical product pages',
        '',
        '## Key pages',
    );

    foreach (kangoo_seo_key_links() as $label => $url) {
        $lines[] = '- [' . $label . '](' . $url . ')';
    }

    $lines[] = '';
    $lines[] = 'For the complete in-stock catalogue, use ' . home_url('/llms-full.txt') . '.';

    return implode("\n", $lines) . "\n";
}

function kangoo_seo_render_llms_full() {
    $lines = array(rtrim(kangoo_seo_render_llms_summary()), '', '## In-stock product catalogue');

    if (!function_exists('wc_get_products')) {
        return implode("\n", $lines) . "\n";
    }

    $products = wc_get_products(array(
        'status' => 'publish',
        'stock_status' => 'instock',
        'limit' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'return' => 'objects',
    ));

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible()) {
            continue;
        }

        $summary = function_exists('kangoo_get_product_seo_summary') ? kangoo_get_product_seo_summary($product) : array();
        $facts = array_filter(array(
            !empty($summary['brand']) ? 'Brand: ' . $summary['brand'] : '',
            !empty($summary['flavour']) ? 'Flavour: ' . $summary['flavour'] : '',
            !empty($summary['strength']) ? 'Strength: ' . $summary['strength'] : '',
            !empty($summary['pouch_count']) ? 'Pouches: ' . (int) $summary['pouch_count'] : '',
            'Price: ' . kangoo_seo_plain_price($product),
            'Availability: in stock',
        ));

        $lines[] = '- [' . $product->get_name() . '](' . get_permalink($product->get_id()) . ') - ' . implode('; ', $facts);
    }

    return implode("\n", $lines) . "\n";
}

function kangoo_seo_serve_ai_files() {
    $path = untrailingslashit((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

    if (!in_array($path, array('/llms.txt', '/llms-full.txt'), true)) {
        return;
    }

    status_header(200);
    nocache_headers();
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
    echo $path === '/llms-full.txt' ? kangoo_seo_render_llms_full() : kangoo_seo_render_llms_summary();
    exit;
}
add_action('template_redirect', 'kangoo_seo_serve_ai_files', -100);

function kangoo_seo_write_ai_files() {
    if (!is_writable(ABSPATH)) {
        return false;
    }

    $files = array(
        ABSPATH . 'llms.txt' => kangoo_seo_render_llms_summary(),
        ABSPATH . 'llms-full.txt' => kangoo_seo_render_llms_full(),
    );

    foreach ($files as $path => $content) {
        $temporary = $path . '.tmp';
        file_put_contents($temporary, $content, LOCK_EX);
        rename($temporary, $path);
    }

    update_option('kangoo_ai_discovery_files_version', '2026-06-11-2', false);
    return true;
}

function kangoo_seo_write_robots_file() {
    if (!is_writable(ABSPATH)) {
        return false;
    }

    $content = implode("\n", array(
        'User-agent: *',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: /cart/',
        'Disallow: /checkout/',
        'Disallow: /my-account/',
        'Disallow: /search/',
        'Disallow: /*?s=',
        'Disallow: /*?add-to-cart=',
        'Disallow: /*?*add-to-cart=',
        'Disallow: /*?orderby=',
        'Disallow: /*?filter_',
        '',
        'Sitemap: ' . home_url('/sitemap_index.xml'),
        '',
    ));

    file_put_contents(ABSPATH . 'robots.txt', $content, LOCK_EX);
    return true;
}

function kangoo_seo_sync_discovery_files() {
    if (get_option('kangoo_ai_discovery_files_version') === '2026-06-11-2') {
        return;
    }

    kangoo_seo_write_ai_files();
    kangoo_seo_write_robots_file();
}
add_action('init', 'kangoo_seo_sync_discovery_files', 99);

function kangoo_seo_refresh_ai_catalogue($product_id = 0) {
    if ($product_id && get_post_type($product_id) !== 'product') {
        return;
    }

    delete_option('kangoo_ai_discovery_files_version');
    kangoo_seo_write_ai_files();
}
add_action('save_post_product', 'kangoo_seo_refresh_ai_catalogue', 30);
add_action('woocommerce_update_product', 'kangoo_seo_refresh_ai_catalogue', 30);
