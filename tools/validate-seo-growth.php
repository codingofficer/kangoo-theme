<?php

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    exit('Run this file with wp eval-file.');
}

$checks = array();

function kangoo_seo_growth_check($label, $passed, $detail = '') {
    if ($passed) {
        WP_CLI::log('PASS: ' . $label . ($detail ? ' - ' . $detail : ''));
        return;
    }

    WP_CLI::warning('FAIL: ' . $label . ($detail ? ' - ' . $detail : ''));
}

$robots = @file_get_contents(ABSPATH . 'robots.txt');
kangoo_seo_growth_check('robots.txt exists', is_string($robots) && $robots !== '');
kangoo_seo_growth_check('AI crawlers are not blocked', !preg_match('/User-agent:\s*(GPTBot|Google-Extended|ClaudeBot|PerplexityBot)[\s\S]{0,200}Disallow:\s*\//i', (string) $robots));
kangoo_seo_growth_check('Private commerce routes are blocked', strpos((string) $robots, 'Disallow: /checkout/') !== false && strpos((string) $robots, 'Disallow: /my-account/') !== false);

$llms = @file_get_contents(ABSPATH . 'llms.txt');
$llms_full = @file_get_contents(ABSPATH . 'llms-full.txt');
kangoo_seo_growth_check('llms.txt is UTF-8 without BOM', substr((string) $llms, 0, 3) !== "\xEF\xBB\xBF");
kangoo_seo_growth_check('llms.txt has no stale claims', !preg_match('/£9\.99|Â|Caffeine Pouches|\/brand\//i', (string) $llms));
kangoo_seo_growth_check('llms-full.txt contains catalogue products', substr_count((string) $llms_full, "\n- [") > 20);

$brand_sitemap = wp_remote_get(home_url('/product_brand-sitemap.xml'), array('redirection' => 0, 'timeout' => 10));
$brand_status = is_wp_error($brand_sitemap) ? 0 : wp_remote_retrieve_response_code($brand_sitemap);
kangoo_seo_growth_check('Duplicate brand sitemap is unavailable', $brand_status === 404, 'HTTP ' . $brand_status);

$brand_redirect = wp_remote_get(home_url('/brand/velo/'), array('redirection' => 0, 'timeout' => 10));
$brand_location = is_wp_error($brand_redirect) ? '' : wp_remote_retrieve_header($brand_redirect, 'location');
kangoo_seo_growth_check('Brand archive redirects to product category', strpos((string) $brand_location, '/product-category/velo/') !== false, (string) $brand_location);

$yoast = get_option('wpseo_taxonomy_meta', array());
$main = get_term_by('slug', 'nicotine-pouches', 'product_cat');
$trial = get_term_by('slug', '99p-pouches', 'product_cat');
$main_title = $main && isset($yoast['product_cat'][$main->term_id]['wpseo_title']) ? $yoast['product_cat'][$main->term_id]['wpseo_title'] : '';
$trial_title = $trial && isset($yoast['product_cat'][$trial->term_id]['wpseo_title']) ? $yoast['product_cat'][$trial->term_id]['wpseo_title'] : '';
kangoo_seo_growth_check('Core category title', $main_title === 'Nicotine Pouches UK | Buy Online from 79p | Kangoo Pouches', $main_title);
kangoo_seo_growth_check('99p entity title', $trial_title === '99p Nicotine Pouches - Now from 79p | Kangoo Pouches', $trial_title);

WP_CLI::success('SEO growth validation finished. Review any warnings above.');
