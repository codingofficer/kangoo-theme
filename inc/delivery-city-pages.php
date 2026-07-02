<?php

defined('ABSPATH') || exit;

function kangoo_delivery_city_version() {
    return '2026-07-02-city-delivery-1';
}

function kangoo_delivery_city_pages() {
    return array(
        'london' => array(
            'city' => 'London',
            'area' => 'Greater London',
            'note' => 'For London shoppers comparing pouch brands online, this page groups the main Kangoo Pouches buying routes without making physical-location claims.',
        ),
        'manchester' => array(
            'city' => 'Manchester',
            'area' => 'Greater Manchester',
            'note' => 'For Manchester shoppers, Kangoo Pouches keeps the route online: compare brands, strengths and current category pages before ordering.',
        ),
        'birmingham' => array(
            'city' => 'Birmingham',
            'area' => 'the West Midlands',
            'note' => 'For Birmingham shoppers, this page points to live online categories rather than a physical city location.',
        ),
        'leeds' => array(
            'city' => 'Leeds',
            'area' => 'West Yorkshire',
            'note' => 'For Leeds shoppers, use this page to move quickly between Kangoo Pouches brand, flavour and strength pages.',
        ),
        'newcastle' => array(
            'city' => 'Newcastle upon Tyne',
            'area' => 'Tyneside',
            'note' => 'For Newcastle shoppers, Kangoo Pouches provides online ordering routes for live nicotine pouch categories and offers.',
        ),
        'liverpool' => array(
            'city' => 'Liverpool',
            'area' => 'Merseyside',
            'note' => 'For Liverpool shoppers, this page keeps the buying path focused on online delivery and current Kangoo Pouches categories.',
        ),
        'glasgow' => array(
            'city' => 'Glasgow',
            'area' => 'central Scotland',
            'note' => 'For Glasgow shoppers, Kangoo Pouches is an online retailer with UK delivery routes and no physical-location listing.',
        ),
        'bristol' => array(
            'city' => 'Bristol',
            'area' => 'the South West',
            'note' => 'For Bristol shoppers, compare nicotine pouch brands and strengths online before choosing a live Kangoo Pouches product page.',
        ),
        'sheffield' => array(
            'city' => 'Sheffield',
            'area' => 'South Yorkshire',
            'note' => 'For Sheffield shoppers, this page groups online nicotine pouch delivery routes and avoids physical-location claims.',
        ),
        'nottingham' => array(
            'city' => 'Nottingham',
            'area' => 'the East Midlands',
            'note' => 'For Nottingham shoppers, Kangoo Pouches links the main online pouch categories, brands and offer pages in one place.',
        ),
    );
}

function kangoo_delivery_city_base_url() {
    return home_url('/nicotine-pouches-delivery/');
}

function kangoo_delivery_city_url($slug) {
    return kangoo_delivery_city_base_url() . trailingslashit(sanitize_title($slug));
}

function kangoo_delivery_city_get($slug) {
    $slug = sanitize_title((string) $slug);
    $cities = kangoo_delivery_city_pages();

    if (!isset($cities[$slug])) {
        return null;
    }

    $city = $cities[$slug];
    $city['slug'] = $slug;
    $city['url'] = kangoo_delivery_city_url($slug);
    $city['title'] = sprintf(__('Nicotine Pouches Delivered to %s', 'kangoo'), $city['city']);
    $city['seo_title'] = sprintf(__('Nicotine Pouches in %s | UK Delivery | Kangoo Pouches', 'kangoo'), $city['city']);
    $city['meta_description'] = sprintf(__('Shop nicotine pouches online for delivery to %s. Browse ZYN, VELO, PABLO, KILLA and 79p pouch offers from Kangoo Pouches.', 'kangoo'), $city['city']);
    $city['summary'] = sprintf(__('Online nicotine pouch delivery page for %s, with links to Kangoo Pouches brands, strengths, flavours and current offer pages.', 'kangoo'), $city['city']);
    $city['content'] = sprintf(
        __('Kangoo Pouches is an online UK retailer delivering tobacco-free nicotine pouches to %1$s and across the UK. Browse ZYN, VELO, PABLO, KILLA, Nordic Spirit, Ubbs, FUMi and XQS category pages, compare pouch strengths and flavours, and use canonical product pages for live price, stock and checkout details. Kangoo Pouches does not operate a physical shop in %1$s.', 'kangoo'),
        $city['city']
    );
    $city['faqs'] = kangoo_delivery_city_faqs($city);

    return $city;
}

function kangoo_delivery_city_current() {
    $slug = get_query_var('kangoo_delivery_city', '');
    return $slug !== '' ? kangoo_delivery_city_get($slug) : null;
}

function kangoo_delivery_city_is_request() {
    return get_query_var('kangoo_delivery_city', '') !== '';
}

function kangoo_delivery_city_register_routes() {
    add_rewrite_tag('%kangoo_delivery_city%', '([^&]+)');
    add_rewrite_rule(
        '^nicotine-pouches-delivery/([^/]+)/?$',
        'index.php?kangoo_delivery_city=$matches[1]',
        'top'
    );

    $option = 'kangoo_delivery_city_rewrite_version';
    if (get_option($option) !== kangoo_delivery_city_version()) {
        flush_rewrite_rules(false);
        update_option($option, kangoo_delivery_city_version(), false);
    }
}
add_action('init', 'kangoo_delivery_city_register_routes');

function kangoo_delivery_city_query_vars($vars) {
    $vars[] = 'kangoo_delivery_city';
    return $vars;
}
add_filter('query_vars', 'kangoo_delivery_city_query_vars');

function kangoo_delivery_city_template($template) {
    if (!kangoo_delivery_city_is_request()) {
        return $template;
    }

    global $wp_query;
    $city = kangoo_delivery_city_current();

    if (!$city) {
        if ($wp_query instanceof WP_Query) {
            $wp_query->set_404();
        }

        status_header(404);
        nocache_headers();
        $template_404 = get_404_template();
        return $template_404 ?: $template;
    }

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
    }

    status_header(200);
    $city_template = get_theme_file_path('/template-parts/delivery-city-page.php');
    return file_exists($city_template) ? $city_template : $template;
}
add_filter('template_include', 'kangoo_delivery_city_template', 1);

function kangoo_delivery_city_document_title_parts($parts) {
    $city = kangoo_delivery_city_current();
    if ($city) {
        $parts['title'] = $city['seo_title'];
    }

    return $parts;
}
add_filter('document_title_parts', 'kangoo_delivery_city_document_title_parts', 40);

function kangoo_delivery_city_yoast_title($title) {
    $city = kangoo_delivery_city_current();
    return $city ? $city['seo_title'] : $title;
}
add_filter('wpseo_title', 'kangoo_delivery_city_yoast_title', 40);

function kangoo_delivery_city_yoast_description($description) {
    $city = kangoo_delivery_city_current();
    return $city ? $city['meta_description'] : $description;
}
add_filter('wpseo_metadesc', 'kangoo_delivery_city_yoast_description', 40);

function kangoo_delivery_city_yoast_canonical($canonical) {
    $city = kangoo_delivery_city_current();
    return $city ? $city['url'] : $canonical;
}
add_filter('wpseo_canonical', 'kangoo_delivery_city_yoast_canonical', 40);

function kangoo_delivery_city_body_class($classes) {
    if (kangoo_delivery_city_current()) {
        $classes[] = 'kangoo-delivery-city-page';
    }

    return $classes;
}
add_filter('body_class', 'kangoo_delivery_city_body_class');

function kangoo_delivery_city_term_url($slug, $fallback) {
    return function_exists('kangoo_get_term_url_by_slug')
        ? kangoo_get_term_url_by_slug('product_cat', $slug, $fallback)
        : home_url($fallback);
}

function kangoo_delivery_city_page_url($template, $fallback) {
    return function_exists('kangoo_get_page_url_by_template')
        ? kangoo_get_page_url_by_template($template, $fallback)
        : home_url($fallback);
}

function kangoo_delivery_city_link_groups() {
    return array(
        array(
            'title' => __('Shop categories', 'kangoo'),
            'summary' => __('Start with the main Kangoo Pouches shopping pages for current price, stock and checkout details.', 'kangoo'),
            'links' => array(
                array('label' => __('All nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('nicotine-pouches', '/product-category/nicotine-pouches/')),
                array('label' => __('99p nicotine pouches now from 79p', 'kangoo'), 'url' => kangoo_delivery_city_term_url('99p-pouches', '/product-category/99p-pouches/')),
            ),
        ),
        array(
            'title' => __('Brand pages', 'kangoo'),
            'summary' => __('Compare live brand categories before choosing a product page.', 'kangoo'),
            'links' => array(
                array('label' => __('ZYN nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('zyn', '/product-category/zyn/')),
                array('label' => __('VELO nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('velo', '/product-category/velo/')),
                array('label' => __('PABLO nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('pablo', '/product-category/pablo/')),
                array('label' => __('KILLA nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('killa', '/product-category/killa/')),
                array('label' => __('Nordic Spirit nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('nordic-spirit', '/product-category/nordic-spirit/')),
                array('label' => __('Ubbs nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('ubbs', '/product-category/ubbs/')),
                array('label' => __('FUMi nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('fumi', '/product-category/fumi/')),
                array('label' => __('XQS nicotine pouches', 'kangoo'), 'url' => kangoo_delivery_city_term_url('xqs', '/product-category/xqs/')),
            ),
        ),
        array(
            'title' => __('Browse by intent', 'kangoo'),
            'summary' => __('Use flavour, strength and comparison routes when you are still narrowing down a pouch style.', 'kangoo'),
            'links' => array(
                array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
                array('label' => __('Berry nicotine pouches', 'kangoo'), 'url' => home_url('/berry-nicotine-pouches/')),
                array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
                array('label' => __('Extra strong nicotine pouches', 'kangoo'), 'url' => home_url('/extra-strong-strength-nicotine-pouches/')),
                array('label' => __('Pouch finder', 'kangoo'), 'url' => kangoo_delivery_city_page_url('page-templates/template-pouch-finder.php', '/pouch-finder/')),
                array('label' => __('Compare pouches', 'kangoo'), 'url' => kangoo_delivery_city_page_url('page-templates/template-pouch-comparison.php', '/compare-pouches/')),
                array('label' => __('Strength ladder', 'kangoo'), 'url' => kangoo_delivery_city_page_url('page-templates/template-strength-ladder.php', '/strength-ladder/')),
                array('label' => __('Flavour explorer', 'kangoo'), 'url' => kangoo_delivery_city_page_url('page-templates/template-flavour-explorer.php', '/flavour-explorer/')),
            ),
        ),
    );
}

function kangoo_delivery_city_faqs($city) {
    $city_name = $city['city'];

    return array(
        array(
            'question' => sprintf(__('Can I buy nicotine pouches online in %s?', 'kangoo'), $city_name),
            'answer' => sprintf(__('Yes. Kangoo Pouches is an online UK retailer, so shoppers in %s can browse nicotine pouch categories and place orders through the website.', 'kangoo'), $city_name),
        ),
        array(
            'question' => sprintf(__('Does Kangoo Pouches have a shop in %s?', 'kangoo'), $city_name),
            'answer' => sprintf(__('No. Kangoo Pouches does not operate a physical shop in %s. The website is the official buying route for Kangoo Pouches orders.', 'kangoo'), $city_name),
        ),
        array(
            'question' => sprintf(__('Which nicotine pouch brands can be delivered to %s?', 'kangoo'), $city_name),
            'answer' => sprintf(__('Kangoo Pouches stocks brand categories such as ZYN, VELO, PABLO, KILLA, Nordic Spirit, Ubbs, FUMi and XQS. Live product pages show current stock and prices for delivery orders to %s and across the UK.', 'kangoo'), $city_name),
        ),
        array(
            'question' => sprintf(__('Can I get 79p nicotine pouch offers delivered to %s?', 'kangoo'), $city_name),
            'answer' => sprintf(__('Selected 99p pouch offers are now from 79p while trial stock lasts. Check the 99p pouch category for current products before ordering for delivery to %s.', 'kangoo'), $city_name),
        ),
    );
}

function kangoo_delivery_city_popular_links($exclude_slug = '', $limit = 0) {
    $links = array();

    foreach (kangoo_delivery_city_pages() as $slug => $city) {
        if ($slug === $exclude_slug) {
            continue;
        }

        $links[] = array(
            'label' => sprintf(__('%s delivery', 'kangoo'), $city['city']),
            'url' => kangoo_delivery_city_url($slug),
        );
    }

    return $limit > 0 ? array_slice($links, 0, $limit) : $links;
}

function kangoo_delivery_city_featured_links($limit = 5) {
    return kangoo_delivery_city_popular_links('', $limit);
}

function kangoo_delivery_city_discovery_items() {
    $items = array();

    foreach (array_keys(kangoo_delivery_city_pages()) as $slug) {
        $city = kangoo_delivery_city_get($slug);
        if (!$city) {
            continue;
        }

        $items[] = array(
            'title' => $city['seo_title'],
            'url' => $city['url'],
            'summary' => $city['meta_description'],
            'content' => $city['content'] . ' ' . $city['note'],
            'section' => 'Delivery city pages',
            'lastmod' => gmdate('c', kangoo_delivery_city_lastmod_timestamp()),
        );
    }

    return $items;
}

function kangoo_delivery_city_lastmod_timestamp() {
    return function_exists('kangoo_seo_ai_files_last_modified_timestamp')
        ? kangoo_seo_ai_files_last_modified_timestamp()
        : time();
}

function kangoo_delivery_city_sitemap_url() {
    return home_url('/delivery-city-sitemap.xml');
}

function kangoo_delivery_city_render_sitemap() {
    $lastmod = gmdate('Y-m-d', kangoo_delivery_city_lastmod_timestamp());
    $lines = array(
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    );

    foreach (kangoo_delivery_city_discovery_items() as $item) {
        $lines[] = '  <url>';
        $lines[] = '    <loc>' . esc_html($item['url']) . '</loc>';
        $lines[] = '    <lastmod>' . esc_html($lastmod) . '</lastmod>';
        $lines[] = '    <changefreq>weekly</changefreq>';
        $lines[] = '  </url>';
    }

    $lines[] = '</urlset>';

    return implode("\n", $lines) . "\n";
}

function kangoo_delivery_city_serve_sitemap() {
    $path = untrailingslashit((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

    if ($path !== '/delivery-city-sitemap.xml') {
        return;
    }

    status_header(200);
    nocache_headers();
    header('Content-Type: application/xml; charset=utf-8');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', kangoo_delivery_city_lastmod_timestamp()) . ' GMT');
    header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
    echo kangoo_delivery_city_render_sitemap();
    exit;
}
add_action('template_redirect', 'kangoo_delivery_city_serve_sitemap', -100);

function kangoo_delivery_city_add_sitemap_to_index($sitemap_index) {
    $url = kangoo_delivery_city_sitemap_url();

    if (strpos((string) $sitemap_index, $url) !== false) {
        return $sitemap_index;
    }

    $entry = "\n\t<sitemap>\n\t\t<loc>" . esc_html($url) . '</loc>' . "\n\t\t<lastmod>" . esc_html(gmdate('c', kangoo_delivery_city_lastmod_timestamp())) . '</lastmod>' . "\n\t</sitemap>\n";
    $sitemap_index = (string) $sitemap_index;

    if (strpos($sitemap_index, '</sitemapindex>') !== false) {
        return str_replace('</sitemapindex>', $entry . '</sitemapindex>', $sitemap_index);
    }

    return rtrim($sitemap_index) . $entry;
}
add_filter('wpseo_sitemap_index', 'kangoo_delivery_city_add_sitemap_to_index', 30);

function kangoo_delivery_city_schema() {
    $city = kangoo_delivery_city_current();
    if (!$city) {
        return;
    }

    $faq_entities = array();
    foreach ($city['faqs'] as $faq) {
        $faq_entities[] = array(
            '@type' => 'Question',
            'name' => wp_strip_all_tags($faq['question']),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => wp_strip_all_tags($faq['answer']),
            ),
        );
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'BreadcrumbList',
                '@id' => $city['url'] . '#breadcrumb',
                'itemListElement' => array(
                    array(
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Home',
                        'item' => home_url('/'),
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Nicotine Pouches',
                        'item' => kangoo_delivery_city_term_url('nicotine-pouches', '/product-category/nicotine-pouches/'),
                    ),
                    array(
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $city['title'],
                        'item' => $city['url'],
                    ),
                ),
            ),
            array(
                '@type' => 'FAQPage',
                '@id' => $city['url'] . '#faq',
                'mainEntity' => $faq_entities,
            ),
        ),
    );

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'kangoo_delivery_city_schema', 30);
