<?php

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    exit('Run this file with wp eval-file.');
}

$failures = 0;

function kangoo_seo_90_check($label, $passed, $detail = '') {
    global $failures;
    if ($passed) {
        WP_CLI::log('PASS: ' . $label . ($detail ? ' - ' . $detail : ''));
        return;
    }
    $failures++;
    WP_CLI::warning('FAIL: ' . $label . ($detail ? ' - ' . $detail : ''));
}

function kangoo_seo_90_request($path, $redirection = 3) {
    return wp_remote_get(home_url($path), array('timeout' => 20, 'redirection' => $redirection, 'user-agent' => 'Kangoo SEO Validator/1.0'));
}

$core = kangoo_seo_90_request('/product-category/nicotine-pouches/');
$core_html = is_wp_error($core) ? '' : wp_remote_retrieve_body($core);
kangoo_seo_90_check('Core category responds', !is_wp_error($core) && wp_remote_retrieve_response_code($core) === 200);
kangoo_seo_90_check('Core category owns commercial H1', strpos($core_html, '<h1') !== false && strpos($core_html, 'Nicotine Pouches UK') !== false);
kangoo_seo_90_check('Core category avoids unverified best-seller claim', stripos($core_html, 'Best selling nicotine pouches UK') === false);
kangoo_seo_90_check('Core category has no mojibake', strpos($core_html, 'Â£') === false && strpos($core_html, 'Ã') === false);

$guide = kangoo_seo_90_request('/blog/what-are-nicotine-pouches/');
$guide_html = is_wp_error($guide) ? '' : wp_remote_retrieve_body($guide);
kangoo_seo_90_check('Beginner guide responds', !is_wp_error($guide) && wp_remote_retrieve_response_code($guide) === 200);
kangoo_seo_90_check('Beginner guide is answer-first', strpos($guide_html, 'Nicotine pouches are small, tobacco-free oral pouches') !== false);
kangoo_seo_90_check('Beginner guide publishes sources', strpos($guide_html, 'blog-article__sources') !== false);

foreach (array(
    '/blog/zyn-pouches-guide-flavours-strengths-and-buying-tips/' => '/blog/zyn-nicotine-pouches-guide-strengths-flavours-and-best-picks/',
    '/blog/velo-reviews/' => '/blog/velo-nicotine-pouches-guide-flavours-strengths-and-best-picks/',
    '/product/nicotine-pouches/pablo-ice-cold-nicotine-pouches-24mg/' => '/product/pablo/ice-cold-24mg/',
    '/product/nicotine-pouches/pablo-grape-ice-nicotine-pouches-30mg/' => '/product/pablo/grape-ice-30mg/',
) as $old => $new) {
    $response = kangoo_seo_90_request($old, 0);
    $location = is_wp_error($response) ? '' : wp_remote_retrieve_header($response, 'location');
    kangoo_seo_90_check('Redirect ' . $old, !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 301 && strpos($location, $new) !== false, (string) $location);
}

foreach (array(
    '/strong-strength-nicotine-pouches/page/2/',
    '/extra-strong-strength-nicotine-pouches/page/2/',
) as $archive_page) {
    $response = kangoo_seo_90_request($archive_page);
    kangoo_seo_90_check('Archive pagination ' . $archive_page, !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200);
}

foreach (array('zyn-pouches-guide-flavours-strengths-and-buying-tips', 'velo-reviews') as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'kangoo_blog');
    kangoo_seo_90_check('Duplicate post trashed: ' . $slug, !$post || $post->post_status === 'trash');
}

$dots = get_page_by_path('velo-strength-dots-explained', OBJECT, 'kangoo_blog');
kangoo_seo_90_check('VELO dots guide exists', $dots && $dots->post_status === 'publish');

$caffeine = kangoo_seo_90_request('/product-category/caffeine-pouches/');
$caffeine_html = is_wp_error($caffeine) ? '' : wp_remote_retrieve_body($caffeine);
kangoo_seo_90_check('Empty caffeine category is noindex', preg_match('/<meta[^>]+name=["\']robots["\'][^>]+noindex/i', $caffeine_html) === 1 || preg_match('/<meta[^>]+content=["\'][^"\']*noindex/i', $caffeine_html) === 1);

foreach (array(306 => '10.9mg', 321 => '6mg', 358 => '10mg', 570 => '3mg') as $product_id => $strength) {
    $product = get_post($product_id);
    $content = $product ? $product->post_excerpt . ' ' . $product->post_content : '';
    kangoo_seo_90_check('Product ' . $product_id . ' has factual strength', $product && strpos($content, $strength) !== false);
}

$llms = kangoo_seo_90_request('/llms.txt');
$llms_full = kangoo_seo_90_request('/llms-full.txt');
$llms_body = is_wp_error($llms) ? '' : wp_remote_retrieve_body($llms);
$llms_full_body = is_wp_error($llms_full) ? '' : wp_remote_retrieve_body($llms_full);
kangoo_seo_90_check('llms.txt responds as UTF-8 text', !is_wp_error($llms) && wp_remote_retrieve_response_code($llms) === 200 && substr($llms_body, 0, 3) !== "\xEF\xBB\xBF");
kangoo_seo_90_check('llms.txt links core guide', strpos($llms_body, '/blog/what-are-nicotine-pouches/') !== false);
kangoo_seo_90_check('llms-full contains live catalogue', substr_count($llms_full_body, "\n- [") > 20);

$yoast = get_option('wpseo_taxonomy_meta', array());
$main = get_term_by('slug', 'nicotine-pouches', 'product_cat');
$trial = get_term_by('slug', '99p-pouches', 'product_cat');
$main_title = $main && isset($yoast['product_cat'][$main->term_id]['wpseo_title']) ? $yoast['product_cat'][$main->term_id]['wpseo_title'] : '';
$trial_title = $trial && isset($yoast['product_cat'][$trial->term_id]['wpseo_title']) ? $yoast['product_cat'][$trial->term_id]['wpseo_title'] : '';
kangoo_seo_90_check('Core Yoast title', $main_title === 'Nicotine Pouches UK | Buy Online from 79p | Kangoo Pouches', $main_title);
kangoo_seo_90_check('99p Yoast title', $trial_title === '99p Nicotine Pouches - Now from 79p | Kangoo Pouches', $trial_title);

foreach (array(
    '/product-category/velo/' => array('What are VELO nicotine pouches?', 'VELO strengths and dots'),
    '/product-category/zyn/' => array('What are ZYN nicotine pouches?', 'ZYN mini and regular formats'),
    '/product-category/pablo/' => array('What are PABLO nicotine pouches?', 'PABLO strengths and who they are for'),
) as $path => $needles) {
    $response = kangoo_seo_90_request($path);
    $html = is_wp_error($response) ? '' : wp_remote_retrieve_body($response);
    kangoo_seo_90_check(
        'Detailed brand guide ' . $path,
        !is_wp_error($response)
        && wp_remote_retrieve_response_code($response) === 200
        && strpos($html, $needles[0]) !== false
        && strpos($html, $needles[1]) !== false
    );
}

if ($failures > 0) {
    WP_CLI::error('SEO validation failed with ' . $failures . ' issue(s).');
}

WP_CLI::success('SEO 90-day validation passed.');
