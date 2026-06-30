<?php

defined('ABSPATH') || exit;

function kangoo_seo_brand_slugs() {
    return array('fumi', 'killa', 'nordic-spirit', 'pablo', 'ubbs', 'velo', 'xqs', 'zyn');
}

function kangoo_seo_ai_discovery_version() {
    return '2026-06-30-ai-discovery-map-2';
}

function kangoo_seo_ai_files_last_modified_timestamp() {
    $timestamp = (int) get_option('kangoo_ai_discovery_files_last_modified', 0);
    static $latest_content_timestamp = null;

    if ($latest_content_timestamp === null) {
        $latest_content_timestamp = 0;
        $latest = get_posts(array(
            'post_type' => array('product', 'page', 'kangoo_blog'),
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));

        if (!empty($latest[0])) {
            $latest_content_timestamp = (int) get_post_modified_time('U', true, (int) $latest[0]);
        }
    }

    $latest = max($timestamp, $latest_content_timestamp);

    return $latest > 0 ? $latest : time();
}

function kangoo_seo_ai_files_last_modified_http() {
    return gmdate('D, d M Y H:i:s', kangoo_seo_ai_files_last_modified_timestamp()) . ' GMT';
}

function kangoo_seo_ai_files_last_modified_text() {
    return gmdate('Y-m-d H:i', kangoo_seo_ai_files_last_modified_timestamp()) . ' UTC';
}

function kangoo_seo_ai_files_etag($path = '') {
    $file = $path ? preg_replace('/[^a-z0-9-]+/i', '-', trim($path, '/')) : 'discovery';
    return '"kangoo-' . strtolower($file) . '-' . kangoo_seo_ai_discovery_version() . '-' . kangoo_seo_ai_files_last_modified_timestamp() . '"';
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

    if (is_tax('product_cat', 'caffeine-pouches')) {
        $robots['index'] = 'noindex';
        $robots['follow'] = 'follow';
        return $robots;
    }

    if (!is_tax(array('pa_flavour', 'pa_strength'))) {
        return $robots;
    }

    $term = get_queried_object();

    if ($term instanceof WP_Term && (int) $term->count < 3) {
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
        return $term instanceof WP_Term && (int) $term->count < 3;
    });

    $caffeine = get_term_by('slug', 'caffeine-pouches', 'product_cat');
    if ($caffeine instanceof WP_Term && (int) $caffeine->count === 0) {
        $thin_ids[] = $caffeine->term_id;
    }

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
        'blog/how-to-use-nicotine-pouches' => 'blog/how-to-use-nicotine-pouches-placement-timing-and-tips',
        'blog/zyn-vs-velo-nicotine-pouches' => 'blog/zyn-vs-velo',
        'blog/strongest-nicotine-pouches-uk' => 'blog/strongest-nicotine-pouches-uk-strong-and-extra-strong-options-explained',
        'blog/snus-meaning-a-simple-uk-guide' => 'blog/what-is-snus',
        'blog/what-is-snus-a-uk-guide-to-snus-and-nicotine-pouches' => 'blog/what-is-snus',
        'blog/nicotine-pouches-for-beginners' => 'blog/what-are-nicotine-pouches',
        'blog/what-are-nicotine-pouches-a-complete-uk-guide' => 'blog/what-are-nicotine-pouches',
        'blog/snus-uk-legal-alternatives-and-what-to-know' => 'blog/snus-uk',
        'blog/nicotine-pouches-from-2-99-kangoo-single-tin-prices' => 'blog/nicotine-pouches-from-3-99-kangoo-single-tin-prices',
        'blog/zyn-pouches-guide-flavours-strengths-and-buying-tips' => 'blog/zyn-nicotine-pouches-guide-strengths-flavours-and-best-picks',
        'blog/velo-reviews' => 'blog/velo-nicotine-pouches-guide-flavours-strengths-and-best-picks',
        'product/nicotine-pouches/pablo-ice-cold-nicotine-pouches-24mg' => 'product/pablo/ice-cold-24mg',
        'product/nicotine-pouches/pablo-grape-ice-nicotine-pouches-30mg' => 'product/pablo/grape-ice-30mg',
    );

    if (isset($redirects[$path])) {
        wp_safe_redirect(home_url('/' . $redirects[$path] . '/'), 301, 'Kangoo Pouches content consolidation');
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
        'Disallow: /search/',
        'Disallow: /*?s=',
        'Disallow: /*?add-to-cart=',
        'Disallow: /*?*add-to-cart=',
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

    $ai_sitemap = 'Sitemap: ' . home_url('/llms-sitemap.xml');
    if (strpos($output, $ai_sitemap) === false) {
        $output .= "\n" . $ai_sitemap;
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
        'ZYN Nicotine Pouches' => home_url('/product-category/zyn/'),
        'VELO Nicotine Pouches' => home_url('/product-category/velo/'),
        'PABLO Nicotine Pouches' => home_url('/product-category/pablo/'),
        'KILLA Nicotine Pouches' => home_url('/product-category/killa/'),
        'Nordic Spirit Nicotine Pouches' => home_url('/product-category/nordic-spirit/'),
        'Ubbs Nicotine Pouches' => home_url('/product-category/ubbs/'),
        'FUMi Nicotine Pouches' => home_url('/product-category/fumi/'),
        'XQS Nicotine Pouches' => home_url('/product-category/xqs/'),
        'ZYN brand guide' => home_url('/blog/what-is-zyn-uk-guide-to-zyn-nicotine-pouches/'),
        'VELO brand guide' => home_url('/blog/what-are-velo-nicotine-pouches-uk-guide/'),
        'PABLO brand guide' => home_url('/blog/what-are-pablo-nicotine-pouches-uk-guide/'),
        'KILLA brand guide' => home_url('/blog/what-are-killa-nicotine-pouches-uk-guide/'),
        'Nordic Spirit brand guide' => home_url('/blog/what-are-nordic-spirit-nicotine-pouches-uk-guide/'),
        'Ubbs brand guide' => home_url('/blog/what-are-ubbs-nicotine-pouches-uk-guide/'),
        'FUMi brand guide' => home_url('/blog/what-are-fumi-nicotine-pouches-uk-guide/'),
        'XQS brand guide' => home_url('/blog/what-are-xqs-nicotine-pouches-uk-guide/'),
        'Nicotine pouch brands compared' => home_url('/blog/nicotine-pouch-brands-uk-zyn-velo-pablo-killa-nordic-spirit-ubbs-fumi-and-xqs-compared/'),
        'What are nicotine pouches?' => home_url('/blog/what-are-nicotine-pouches/'),
        'How to use nicotine pouches' => home_url('/blog/how-to-use-nicotine-pouches-placement-timing-and-tips/'),
        'VELO strength dots explained' => home_url('/blog/velo-strength-dots-explained/'),
        'Best VELO flavours UK' => home_url('/blog/best-velo-flavours-uk/'),
        'ZYN flavour guide' => home_url('/blog/zyn-flavour-guide-black-cherry-spearmint-coffee-apple-mint/'),
        'ZYN price UK guide' => home_url('/blog/zyn-price-uk-what-affects-zyn-pouch-prices-online/'),
        'How to open a ZYN can' => home_url('/blog/how-to-open-a-zyn-can-container/'),
        'Nicotine pouches near me vs online' => home_url('/blog/nicotine-pouches-near-me-vs-online-which-buying-route-works-best/'),
        'Snus UK guide' => home_url('/blog/snus-uk/'),
        'What is snus?' => home_url('/blog/what-is-snus/'),
        'Sitemap' => home_url('/sitemap_index.xml'),
    );
}

function kangoo_seo_clean_text($value, $word_limit = 0) {
    if (is_array($value)) {
        $value = implode(' ', array_filter(array_map('strval', $value)));
    }

    $text = html_entity_decode(wp_strip_all_tags(strip_shortcodes((string) $value)), ENT_QUOTES, 'UTF-8');

    $bad_pound = chr(195) . chr(131) . chr(226) . chr(128) . chr(154) . chr(195) . chr(130) . chr(194) . chr(163);
    $mojibake_pound = chr(195) . chr(130) . chr(194) . chr(163);
    $mojibake_prefix = chr(195) . chr(130);
    $mojibake_ubbs_upper = chr(195) . chr(131) . chr(197) . chr(147) . 'bbs';
    $mojibake_ubbs_lower = chr(195) . chr(131) . chr(194) . chr(188) . 'bbs';
    $utf8_ubbs_upper = chr(195) . chr(156) . 'bbs';
    $utf8_ubbs_lower = chr(195) . chr(188) . 'bbs';
    $utf8_pound = chr(194) . chr(163);
    $en_dash = chr(226) . chr(128) . chr(147);
    $em_dash = chr(226) . chr(128) . chr(148);
    $left_single_quote = chr(226) . chr(128) . chr(152);
    $right_single_quote = chr(226) . chr(128) . chr(153);
    $left_double_quote = chr(226) . chr(128) . chr(156);
    $right_double_quote = chr(226) . chr(128) . chr(157);
    $ellipsis = chr(226) . chr(128) . chr(166);
    $bullet = chr(226) . chr(128) . chr(162);

    $text = strtr($text, array(
        $bad_pound => 'GBP ',
        $mojibake_pound => 'GBP ',
        $mojibake_prefix => '',
        $mojibake_ubbs_upper => 'Ubbs',
        strtoupper($mojibake_ubbs_upper) => 'UBBS',
        $mojibake_ubbs_lower => 'Ubbs',
        $utf8_ubbs_upper => 'Ubbs',
        $utf8_ubbs_lower => 'Ubbs',
        '&pound;' => 'GBP ',
        '&#163;' => 'GBP ',
        $utf8_pound => 'GBP ',
    ));

    $text = str_replace(
        array($en_dash, $em_dash, $left_single_quote, $right_single_quote, $left_double_quote, $right_double_quote, $ellipsis, $bullet),
        array('-', '-', "'", "'", '"', '"', '...', '-'),
        $text
    );
    $text = preg_replace('/\s+/u', ' ', trim($text));

    if ($word_limit > 0 && str_word_count($text) > $word_limit) {
        $text = wp_trim_words($text, $word_limit, '...');
    }

    return $text;
}

function kangoo_seo_markdown_link($label, $url, $summary = '') {
    $line = '- [' . kangoo_seo_clean_text($label) . '](' . esc_url_raw($url) . ')';
    $summary = kangoo_seo_clean_text($summary, 34);

    return $summary !== '' ? $line . ': ' . $summary : $line;
}

function kangoo_seo_post_meta_description($post_id) {
    $post_id = absint($post_id);
    $description = trim((string) get_post_meta($post_id, '_yoast_wpseo_metadesc', true));

    if ($description === '' && get_post_type($post_id) === 'kangoo_blog' && function_exists('kangoo_blog_meta_description')) {
        $description = kangoo_blog_meta_description($post_id);
    }

    if ($description === '' && has_excerpt($post_id)) {
        $description = get_the_excerpt($post_id);
    }

    if ($description === '') {
        $description = get_post_field('post_content', $post_id);
    }

    return kangoo_seo_clean_text($description, 36);
}

function kangoo_seo_post_discovery_item($post_id, $section = '') {
    $post_id = absint($post_id);
    $content_limit = get_post_type($post_id) === 'kangoo_blog' ? 420 : 240;

    return array(
        'title' => get_the_title($post_id),
        'url' => get_permalink($post_id),
        'summary' => kangoo_seo_post_meta_description($post_id),
        'content' => kangoo_seo_clean_text(get_post_field('post_content', $post_id), $content_limit),
        'section' => $section,
        'lastmod' => get_post_modified_time('c', true, $post_id),
    );
}

function kangoo_seo_page_item_by_path($path, $fallback_title = '', $fallback_summary = '') {
    $page = get_page_by_path(trim((string) $path, '/'));

    if (!$page instanceof WP_Post || $page->post_status !== 'publish') {
        return null;
    }

    $item = kangoo_seo_post_discovery_item($page->ID, 'Support and legal');

    if ($fallback_title !== '') {
        $item['title'] = $fallback_title;
    }

    if ($item['summary'] === '' && $fallback_summary !== '') {
        $item['summary'] = $fallback_summary;
    }

    return $item;
}

function kangoo_seo_term_acf_value($term, $field) {
    if (!$term instanceof WP_Term) {
        return '';
    }

    $acf_key = $term->taxonomy . '_' . $term->term_id;

    if (function_exists('get_field')) {
        $value = get_field($field, $acf_key);

        if ($value !== null && $value !== '') {
            return $value;
        }
    }

    return get_term_meta($term->term_id, $field, true);
}

function kangoo_seo_term_questions($term) {
    if (!$term instanceof WP_Term) {
        return array();
    }

    if (
        $term->taxonomy === 'product_cat'
        && function_exists('kangoo_get_brand_authority_profile')
        && !empty(kangoo_get_brand_authority_profile($term->slug))
        && function_exists('kangoo_get_brand_authority_faq')
    ) {
        $rows = kangoo_get_brand_authority_faq($term->slug);
    } else {
        $rows = kangoo_seo_term_acf_value($term, 'category_faq');
    }

    $people_also_ask = kangoo_seo_term_acf_value($term, 'category_people_also_ask');
    $rows = array_merge(is_array($rows) ? $rows : array(), is_array($people_also_ask) ? $people_also_ask : array());
    $questions = array();

    foreach ($rows as $row) {
        $question = isset($row['question']) ? kangoo_seo_clean_text($row['question']) : '';
        $answer = isset($row['answer']) ? kangoo_seo_clean_text($row['answer'], 70) : '';

        if ($question !== '' && $answer !== '') {
            $questions[] = array(
                'question' => $question,
                'answer' => $answer,
            );
        }
    }

    return $questions;
}

function kangoo_seo_term_discovery_item($term, $section = '') {
    if (!$term instanceof WP_Term) {
        return null;
    }

    $url = get_term_link($term);

    if (is_wp_error($url) || !$url) {
        return null;
    }

    $intro = kangoo_seo_term_acf_value($term, 'category_intro');
    $seo_title = kangoo_seo_term_acf_value($term, 'category_seo_title');
    $seo_content = kangoo_seo_term_acf_value($term, 'category_seo_content');

    if (
        $term->taxonomy === 'product_cat'
        && function_exists('kangoo_get_brand_authority_profile')
        && !empty(kangoo_get_brand_authority_profile($term->slug))
    ) {
        $profile = kangoo_get_brand_authority_profile($term->slug);
        $seo_title = !empty($profile['label']) ? $profile['label'] . ' Nicotine Pouches' : $seo_title;

        if (function_exists('kangoo_get_brand_authority_intro')) {
            $intro = kangoo_get_brand_authority_intro($term->slug);
        }

        if (function_exists('kangoo_get_brand_authority_content')) {
            $seo_content = kangoo_get_brand_authority_content($term->slug);
        }
    }

    $title = kangoo_seo_clean_text($seo_title !== '' ? $seo_title : $term->name);
    $summary = kangoo_seo_clean_text($intro !== '' ? $intro : $term->description, 38);
    $content_parts = array_filter(array(
        $summary,
        kangoo_seo_clean_text($seo_content, 260),
    ));

    foreach (kangoo_seo_term_questions($term) as $question) {
        $content_parts[] = 'Q: ' . $question['question'] . ' A: ' . $question['answer'];
    }

    return array(
        'title' => $title,
        'url' => $url,
        'summary' => $summary,
        'content' => kangoo_seo_clean_text(implode(' ', $content_parts), 420),
        'section' => $section,
        'count' => (int) $term->count,
        'lastmod' => '',
    );
}

function kangoo_seo_get_terms_for_discovery($taxonomy, $slugs = array(), $minimum_count = 1) {
    $args = array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'number' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
    );

    if (!empty($slugs)) {
        $args['slug'] = array_values(array_map('sanitize_title', (array) $slugs));
    }

    $terms = get_terms($args);

    if (is_wp_error($terms) || !is_array($terms)) {
        return array();
    }

    $blocked = array('caffeine-pouches', 'uncategorized');

    return array_values(array_filter($terms, static function ($term) use ($minimum_count, $blocked) {
        return $term instanceof WP_Term
            && !in_array($term->slug, $blocked, true)
            && ((int) $term->count >= $minimum_count || in_array($term->slug, kangoo_seo_brand_slugs(), true));
    }));
}

function kangoo_seo_discovery_group_items($context = 'summary') {
    $brand_slugs = kangoo_seo_brand_slugs();
    $groups = array();

    $groups[] = array(
        'title' => 'Core discovery resources',
        'intro' => 'Important Kangoo Pouches discovery, sitemap and buying-start pages.',
        'items' => array(
            array(
                'title' => 'Home',
                'url' => home_url('/'),
                'summary' => 'Kangoo Pouches UK homepage for tobacco-free nicotine pouches, brand browsing and current shopping routes.',
                'content' => 'Kangoo Pouches is a UK online retailer for tobacco-free nicotine pouches. Use live product and category pages for current price, stock, delivery and checkout details.',
            ),
            array(
                'title' => 'Blog and guides',
                'url' => get_post_type_archive_link('kangoo_blog') ?: home_url('/blog/'),
                'summary' => 'Kangoo Pouches guides, comparisons and buying advice for nicotine pouch shoppers.',
                'content' => 'Kangoo Pouches publishes guides about brands, strengths, flavours, snus wording and practical buying decisions for adults aged 18 and over.',
            ),
            array(
                'title' => 'Human sitemap',
                'url' => home_url('/sitemap/'),
                'summary' => 'Human-readable grouped sitemap for Kangoo Pouches shop, brand, guide, flavour, strength, help and legal pages.',
                'content' => 'Use this sitemap to find the main Kangoo Pouches ecommerce and education pages.',
            ),
            array(
                'title' => 'Yoast XML sitemap',
                'url' => home_url('/sitemap_index.xml'),
                'summary' => 'Canonical XML sitemap index generated by Yoast SEO.',
                'content' => 'Search engines should use the Yoast XML sitemap index for canonical crawl discovery.',
            ),
            array(
                'title' => 'Full LLM context',
                'url' => home_url('/llms-full.txt'),
                'summary' => 'Expanded clean text context for Kangoo Pouches categories, guides, support pages and product catalogue.',
                'content' => 'AI assistants can use the full LLM context, then confirm live stock and pricing on canonical product pages.',
            ),
        ),
    );

    $shop_terms = array_filter(kangoo_seo_get_terms_for_discovery('product_cat', array(), 1), static function ($term) use ($brand_slugs) {
        return $term instanceof WP_Term && !in_array($term->slug, $brand_slugs, true);
    });
    $groups[] = array(
        'title' => 'Shop and category pages',
        'intro' => 'Commercial category pages where shoppers compare live Kangoo Pouches products.',
        'items' => array_values(array_filter(array_map(static function ($term) {
            return kangoo_seo_term_discovery_item($term, 'Shop and category pages');
        }, $shop_terms))),
    );

    $brand_terms = kangoo_seo_get_terms_for_discovery('product_cat', $brand_slugs, 0);
    $groups[] = array(
        'title' => 'Brand category pages',
        'intro' => 'Live brand category pages with current products and brand authority copy.',
        'items' => array_values(array_filter(array_map(static function ($term) {
            return kangoo_seo_term_discovery_item($term, 'Brand category pages');
        }, $brand_terms))),
    );

    $groups[] = array(
        'title' => 'Flavour pages',
        'intro' => 'Flavour archive pages for comparing pouch profiles.',
        'items' => array_values(array_filter(array_map(static function ($term) {
            return kangoo_seo_term_discovery_item($term, 'Flavour pages');
        }, kangoo_seo_get_terms_for_discovery('pa_flavour', array(), 3)))),
    );

    $groups[] = array(
        'title' => 'Strength pages',
        'intro' => 'Strength archive pages for comparing pouch nicotine levels.',
        'items' => array_values(array_filter(array_map(static function ($term) {
            return kangoo_seo_term_discovery_item($term, 'Strength pages');
        }, kangoo_seo_get_terms_for_discovery('pa_strength', array(), 3)))),
    );

    $guide_limit = $context === 'summary' ? 60 : -1;
    $guide_ids = get_posts(array(
        'post_type' => 'kangoo_blog',
        'post_status' => 'publish',
        'posts_per_page' => $guide_limit,
        'orderby' => 'modified',
        'order' => 'DESC',
        'fields' => 'ids',
        'no_found_rows' => true,
    ));
    $groups[] = array(
        'title' => 'Guides and buying advice',
        'intro' => 'Educational and commercial guides that support brand, flavour, strength and buying queries.',
        'items' => array_map(static function ($post_id) {
            return kangoo_seo_post_discovery_item($post_id, 'Guides and buying advice');
        }, $guide_ids),
    );

    $support_items = array_filter(array(
        kangoo_seo_page_item_by_path('contact', 'Contact Kangoo Pouches', 'Contact Kangoo Pouches for order help, delivery questions, product recommendations and general enquiries.'),
        kangoo_seo_page_item_by_path('delivery-shipping', 'Delivery and shipping', 'Delivery information for Kangoo Pouches UK orders.'),
        kangoo_seo_page_item_by_path('returns-refunds', 'Returns and refunds', 'Returns and refunds information for Kangoo Pouches customers.'),
        kangoo_seo_page_item_by_path('kangoo-rewards', 'Kangoo Rewards', 'Kangoo rewards information for points and customer discounts.'),
        kangoo_seo_page_item_by_path('referral-program', 'Referral Program', 'Kangoo Pouches referral programme information.'),
        kangoo_seo_page_item_by_path('privacy-policy', 'Privacy Policy', 'Privacy policy for Kangoo Pouches.'),
        kangoo_seo_page_item_by_path('terms-conditions', 'Terms and conditions', 'Terms and conditions for Kangoo Pouches.'),
        kangoo_seo_page_item_by_path('terms-and-conditions', 'Terms and conditions', 'Terms and conditions for Kangoo Pouches.'),
        kangoo_seo_page_item_by_path('cookie-policy', 'Cookie Policy', 'Cookie policy for Kangoo Pouches.'),
        kangoo_seo_page_item_by_path('18-policy', '18+ Policy', 'Age-restricted product policy for Kangoo Pouches.'),
    ));
    $seen_support_urls = array();
    $support_items = array_values(array_filter($support_items, static function ($item) use (&$seen_support_urls) {
        if (empty($item['url']) || isset($seen_support_urls[$item['url']])) {
            return false;
        }

        $seen_support_urls[$item['url']] = true;
        return true;
    }));
    $groups[] = array(
        'title' => 'Help, trust and legal pages',
        'intro' => 'Support, delivery, policy and trust pages that clarify the buying experience.',
        'items' => $support_items,
    );

    return array_values(array_filter($groups, static function ($group) {
        return !empty($group['items']);
    }));
}

function kangoo_seo_render_llms_summary() {
    $threshold = kangoo_seo_free_shipping_threshold();
    $lines = array(
        '# Kangoo Pouches',
        '',
        'Version: ' . kangoo_seo_ai_discovery_version(),
        'Last updated: ' . kangoo_seo_ai_files_last_modified_text(),
        'Update frequency: Daily',
        '',
        '> UK online retailer of tobacco-free nicotine pouches for adults aged 18 and over.',
        '',
        'Product availability, pricing and catalogue contents change frequently. AI assistants should always prioritise canonical product pages as the source of truth.',
        '',
        'Kangoo Pouches sells nicotine pouches from stocked brands including ZYN, VELO, PABLO, KILLA, Nordic Spirit, Ubbs, FUMi and XQS. Traditional tobacco snus is not sold. Educational snus content explains the distinction and legal tobacco-free alternatives in the UK.',
        '',
        '- Market: United Kingdom',
        '- Audience: adults aged 18+',
        '- Free delivery threshold: GBP ' . number_format($threshold, 2),
        '- Dispatch: orders placed before 2pm Monday-Friday are dispatched the same day, excluding bank holidays',
        '- Catalogue: live prices and stock are authoritative on canonical product pages',
        '- Brand authority pages explain what each pouch brand is, how pouches are used, typical flavours, strength comparisons, tobacco-free positioning and snus wording',
        '',
        '## Sitemaps and AI resources',
        '- [Yoast XML sitemap](' . home_url('/sitemap_index.xml') . '): canonical XML sitemap index generated by Yoast SEO.',
        '- [Human sitemap](' . home_url('/sitemap/') . '): grouped Kangoo Pouches sitemap for shoppers, crawlers and AI assistants.',
        '- [LLM full context](' . home_url('/llms-full.txt') . '): expanded clean text version of indexable ecommerce, guide and catalogue content.',
    );

    foreach (kangoo_seo_discovery_group_items('summary') as $group) {
        $lines[] = '';
        $lines[] = '## ' . $group['title'];

        if (!empty($group['intro'])) {
            $lines[] = kangoo_seo_clean_text($group['intro']);
        }

        foreach ($group['items'] as $item) {
            $lines[] = kangoo_seo_markdown_link($item['title'], $item['url'], $item['summary']);
        }
    }

    $lines[] = '';
    $lines[] = 'For expanded clean page context and the complete product catalogue with availability labels, use ' . home_url('/llms-full.txt') . '.';

    return implode("\n", $lines) . "\n";
}

function kangoo_seo_render_llms_sitemap() {
    $lastmod = gmdate('Y-m-d', kangoo_seo_ai_files_last_modified_timestamp());
    $urls = array(
        home_url('/sitemap/'),
        home_url('/llms.txt'),
        home_url('/llms-full.txt'),
    );

    $lines = array(
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    );

    foreach ($urls as $url) {
        $lines[] = '  <url>';
        $lines[] = '    <loc>' . esc_html($url) . '</loc>';
        $lines[] = '    <lastmod>' . esc_html($lastmod) . '</lastmod>';
        $lines[] = '    <changefreq>daily</changefreq>';
        $lines[] = '  </url>';
    }

    $lines[] = '</urlset>';

    return implode("\n", $lines) . "\n";
}

function kangoo_seo_add_ai_sitemap_to_index($sitemap_index) {
    $url = home_url('/llms-sitemap.xml');

    if (strpos((string) $sitemap_index, $url) !== false) {
        return $sitemap_index;
    }

    $entry = "\n\t<sitemap>\n\t\t<loc>" . esc_html($url) . '</loc>' . "\n\t\t<lastmod>" . esc_html(gmdate('c', kangoo_seo_ai_files_last_modified_timestamp())) . '</lastmod>' . "\n\t</sitemap>\n";
    $sitemap_index = (string) $sitemap_index;

    if (strpos($sitemap_index, '</sitemapindex>') !== false) {
        return str_replace('</sitemapindex>', $entry . '</sitemapindex>', $sitemap_index);
    }

    return rtrim($sitemap_index) . $entry;
}
add_filter('wpseo_sitemap_index', 'kangoo_seo_add_ai_sitemap_to_index');

function kangoo_seo_discovery_brand_name($brand) {
    $brand = trim((string) $brand);
    $normalized = function_exists('remove_accents') ? remove_accents($brand) : $brand;
    return strcasecmp($normalized, 'ubbs') === 0 ? 'Ubbs' : $brand;
}

function kangoo_seo_render_llms_full() {
    $lines = array(
        '# Kangoo Pouches Full LLM Context',
        '',
        'Generated: ' . gmdate('Y-m-d H:i', kangoo_seo_ai_files_last_modified_timestamp()) . ' UTC',
        'Version: ' . kangoo_seo_ai_discovery_version(),
        '',
        'Kangoo Pouches is a UK online retailer of tobacco-free nicotine pouches for adults aged 18 and over. Product availability, prices and catalogue contents change frequently; canonical product pages remain the source of truth for live stock, price and checkout details.',
        '',
        'Canonical XML sitemap: ' . home_url('/sitemap_index.xml'),
        'Human sitemap: ' . home_url('/sitemap/'),
        'Summary LLM file: ' . home_url('/llms.txt'),
        '',
        '## Brand authority coverage',
        '- ZYN, VELO, PABLO, KILLA, Nordic Spirit, Ubbs, FUMi and XQS each have a live WooCommerce brand category and a dedicated educational brand guide.',
        '- Brand guides cover what the pouch brand is, what is typically inside the pouches, how pouches are used, common flavour directions, strength and format comparisons, and adult nicotine cautions.',
        '- Product and category pages remain the source of truth for current price, stock, pouch count, exact strength and pack pricing.',
        '- Product availability changes frequently. Availability fields reflect the current status when this file was generated.',
    );

    foreach (kangoo_seo_discovery_group_items('full') as $group) {
        $lines[] = '';
        $lines[] = '## ' . $group['title'];

        if (!empty($group['intro'])) {
            $lines[] = kangoo_seo_clean_text($group['intro']);
        }

        foreach ($group['items'] as $item) {
            $lines[] = '';
            $lines[] = '### ' . kangoo_seo_clean_text($item['title']);
            $lines[] = 'URL: ' . esc_url_raw($item['url']);

            if (!empty($item['summary'])) {
                $lines[] = 'SEO description: ' . kangoo_seo_clean_text($item['summary'], 42);
            }

            if (!empty($item['lastmod'])) {
                $lines[] = 'Last modified: ' . kangoo_seo_clean_text($item['lastmod']);
            }

            if (!empty($item['content'])) {
                $lines[] = 'Content:';
                $lines[] = kangoo_seo_clean_text($item['content'], 520);
            }
        }
    }

    $lines[] = '';
    $lines[] = '## Product catalogue';

    if (!function_exists('wc_get_products')) {
        return implode("\n", $lines) . "\n";
    }

    $products = wc_get_products(array(
        'status' => 'publish',
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
        $stock_status = $product->get_stock_status();
        $availability = $stock_status === 'instock'
            ? 'in stock'
            : ($stock_status === 'onbackorder' ? 'on backorder' : 'out of stock');

        $facts = array_filter(array(
            !empty($summary['brand']) ? 'Brand: ' . kangoo_seo_discovery_brand_name($summary['brand']) : '',
            !empty($summary['flavour']) ? 'Flavour: ' . $summary['flavour'] : '',
            !empty($summary['strength']) ? 'Strength: ' . $summary['strength'] : '',
            !empty($summary['pouch_count']) ? 'Pouches: ' . (int) $summary['pouch_count'] : '',
            'Price: ' . kangoo_seo_plain_price($product),
            'Availability: ' . $availability,
        ));

        $lines[] = '- [' . $product->get_name() . '](' . get_permalink($product->get_id()) . ') - ' . implode('; ', $facts);
    }

    return implode("\n", $lines) . "\n";
}

function kangoo_seo_is_human_sitemap_request() {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

    return $path === 'sitemap';
}

function kangoo_seo_human_sitemap_template($template) {
    if (!kangoo_seo_is_human_sitemap_request()) {
        return $template;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
    }

    status_header(200);

    $sitemap_template = get_theme_file_path('/page-sitemap.php');

    return file_exists($sitemap_template) ? $sitemap_template : $template;
}
add_filter('template_include', 'kangoo_seo_human_sitemap_template', 1);

function kangoo_seo_human_sitemap_title_parts($parts) {
    if (kangoo_seo_is_human_sitemap_request()) {
        $parts['title'] = __('Sitemap', 'kangoo');
    }

    return $parts;
}
add_filter('document_title_parts', 'kangoo_seo_human_sitemap_title_parts', 40);

function kangoo_seo_human_sitemap_yoast_title($title) {
    return kangoo_seo_is_human_sitemap_request()
        ? __('Sitemap | Kangoo Pouches Shop, Brands & Guides', 'kangoo')
        : $title;
}
add_filter('wpseo_title', 'kangoo_seo_human_sitemap_yoast_title', 40);

function kangoo_seo_human_sitemap_yoast_description($description) {
    return kangoo_seo_is_human_sitemap_request()
        ? __('Find Kangoo Pouches shop categories, brand pages, flavour and strength pages, buying guides, support pages and XML sitemap links.', 'kangoo')
        : $description;
}
add_filter('wpseo_metadesc', 'kangoo_seo_human_sitemap_yoast_description', 40);

function kangoo_seo_human_sitemap_yoast_canonical($canonical) {
    return kangoo_seo_is_human_sitemap_request() ? home_url('/sitemap/') : $canonical;
}
add_filter('wpseo_canonical', 'kangoo_seo_human_sitemap_yoast_canonical', 40);

function kangoo_seo_render_human_sitemap_content() {
    $groups = kangoo_seo_discovery_group_items('summary');
    $left_column_titles = array(
        'Core discovery resources',
        'Brand category pages',
        'Strength pages',
        'Help, trust and legal pages',
    );
    $columns = array(
        'primary' => array(),
        'secondary' => array(),
    );

    foreach ($groups as $group) {
        $column = in_array($group['title'], $left_column_titles, true) ? 'primary' : 'secondary';
        $columns[$column][] = $group;
    }

    ob_start();
    ?>
    <main class="kangoo-sitemap">
        <section class="section kangoo-sitemap__hero">
            <div class="container">
                <div class="kangoo-sitemap__hero-inner">
                    <div>
                        <span class="eyebrow"><?php esc_html_e('Kangoo sitemap', 'kangoo'); ?></span>
                        <h1><?php esc_html_e('Shop, brand and guide pages', 'kangoo'); ?></h1>
                        <p><?php esc_html_e('Use this grouped sitemap to find Kangoo Pouches shopping categories, brand pages, flavour and strength pages, buying guides, support information and crawl resources.', 'kangoo'); ?></p>
                    </div>
                    <aside class="kangoo-sitemap__note" aria-label="<?php esc_attr_e('Sitemap note', 'kangoo'); ?>">
                        <strong><?php esc_html_e('Live ecommerce index', 'kangoo'); ?></strong>
                        <p><?php esc_html_e('Prices, stock and delivery details can change. Canonical product pages remain the source of truth for live shopping information.', 'kangoo'); ?></p>
                        <span><?php printf(esc_html__('Last updated: %s', 'kangoo'), esc_html(kangoo_seo_ai_files_last_modified_text())); ?></span>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section kangoo-sitemap__content" aria-labelledby="kangoo-sitemap-content-title">
            <div class="container">
                <header class="section-header section-header--left">
                    <span class="eyebrow"><?php esc_html_e('Browse', 'kangoo'); ?></span>
                    <h2 id="kangoo-sitemap-content-title"><?php esc_html_e('Kangoo Pouches page index', 'kangoo'); ?></h2>
                </header>

                <div class="kangoo-sitemap__grid">
                    <?php foreach ($columns as $column_groups) : ?>
                        <div class="kangoo-sitemap__column">
                            <?php foreach ($column_groups as $group) : ?>
                                <article class="kangoo-sitemap__card">
                                    <header>
                                        <span class="kangoo-sitemap__count">
                                            <?php
                                            printf(
                                                esc_html(_n('%d page', '%d pages', count($group['items']), 'kangoo')),
                                                absint(count($group['items']))
                                            );
                                            ?>
                                        </span>
                                        <h3><?php echo esc_html($group['title']); ?></h3>
                                        <?php if (!empty($group['intro'])) : ?>
                                            <p><?php echo esc_html(kangoo_seo_clean_text($group['intro'], 28)); ?></p>
                                        <?php endif; ?>
                                    </header>

                                    <ul class="kangoo-sitemap__list">
                                        <?php foreach ($group['items'] as $item) : ?>
                                            <li>
                                                <a href="<?php echo esc_url($item['url']); ?>">
                                                    <span><?php echo esc_html($item['title']); ?></span>
                                                </a>
                                                <?php if (!empty($item['summary'])) : ?>
                                                    <p><?php echo esc_html(kangoo_seo_clean_text($item['summary'], 24)); ?></p>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
    <?php
    return ob_get_clean();
}

function kangoo_seo_serve_ai_files() {
    $path = untrailingslashit((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

    if (!in_array($path, array('/llms.txt', '/llms-full.txt', '/llms-sitemap.xml'), true)) {
        return;
    }

    status_header(200);
    nocache_headers();
    header('Last-Modified: ' . kangoo_seo_ai_files_last_modified_http());
    header('ETag: ' . kangoo_seo_ai_files_etag($path));
    header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');

    if ($path === '/llms-sitemap.xml') {
        header('Content-Type: application/xml; charset=utf-8');
        echo kangoo_seo_render_llms_sitemap();
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $path === '/llms-full.txt' ? kangoo_seo_render_llms_full() : kangoo_seo_render_llms_summary();
    exit;
}
add_action('template_redirect', 'kangoo_seo_serve_ai_files', -100);

function kangoo_seo_write_ai_files() {
    if (!is_writable(ABSPATH)) {
        return false;
    }

    update_option('kangoo_ai_discovery_files_last_modified', time(), false);

    $files = array(
        ABSPATH . 'llms.txt' => kangoo_seo_render_llms_summary(),
        ABSPATH . 'llms-full.txt' => kangoo_seo_render_llms_full(),
        ABSPATH . 'llms-sitemap.xml' => kangoo_seo_render_llms_sitemap(),
    );

    foreach ($files as $path => $content) {
        $temporary = $path . '.tmp';
        file_put_contents($temporary, $content, LOCK_EX);
        rename($temporary, $path);
    }

    update_option('kangoo_ai_discovery_files_version', kangoo_seo_ai_discovery_version(), false);
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
        'Sitemap: ' . home_url('/llms-sitemap.xml'),
        '',
    ));

    file_put_contents(ABSPATH . 'robots.txt', $content, LOCK_EX);
    return true;
}

function kangoo_seo_sync_discovery_files() {
    if (get_option('kangoo_ai_discovery_files_version') === kangoo_seo_ai_discovery_version()) {
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
