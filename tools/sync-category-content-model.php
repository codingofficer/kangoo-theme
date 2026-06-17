<?php

defined('ABSPATH') || exit;

$backup_dir = WP_CONTENT_DIR . '/uploads/kangoo-seo-backups';

if (!is_dir($backup_dir)) {
    wp_mkdir_p($backup_dir);
}

$term_ids = get_terms(array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'fields'     => 'ids',
));

$backup = array(
    'created_at'          => gmdate('c'),
    'terms'               => array(),
    'wpseo_taxonomy_meta' => get_option('wpseo_taxonomy_meta', array()),
);

foreach ($term_ids as $term_id) {
    $term = get_term($term_id, 'product_cat');

    if (!$term || is_wp_error($term)) {
        continue;
    }

    $backup['terms'][$term_id] = array(
        'name'                 => $term->name,
        'slug'                 => $term->slug,
        'description'          => $term->description,
        'category_intro'       => get_term_meta($term_id, 'category_intro', true),
        'category_seo_title'   => get_term_meta($term_id, 'category_seo_title', true),
        'category_seo_content' => get_term_meta($term_id, 'category_seo_content', true),
        'category_faq'         => get_term_meta($term_id, 'category_faq', true),
    );
}

$backup_file = trailingslashit($backup_dir) . 'category-content-before-brand-safety-sync-' . gmdate('Ymd-His') . '.json';
file_put_contents($backup_file, wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($term_ids as $term_id) {
    $term = get_term($term_id, 'product_cat');

    if (!$term || is_wp_error($term)) {
        continue;
    }

    $intro = get_term_meta($term_id, 'category_intro', true);

    if (trim((string) $intro) === '' && trim(wp_strip_all_tags($term->description)) !== '') {
        $intro = preg_replace('/\s+/', ' ', wp_strip_all_tags($term->description));
        update_term_meta($term_id, 'category_intro', trim($intro));
    }

    if ($term->description !== '') {
        wp_update_term($term_id, 'product_cat', array('description' => ''));
    }

    foreach (array('category_intro', 'category_seo_title', 'category_seo_content') as $meta_key) {
        $value = get_term_meta($term_id, $meta_key, true);
        $updated_value = str_replace(array('79p', 'from 79p', 'just 79p'), array('99p', 'from 99p', 'just 99p'), $value);

        if ($term->slug === 'velo' && $meta_key === 'category_seo_content') {
            $updated_value = str_replace(
                'Regular VELO single tins are £3.99 where available. Selected VELO trial pouches may also appear in the 99p pouch range while stock lasts.',
                'Current VELO prices and pack offers are shown live on each product. Selected VELO trial pouches may also appear in the 99p pouch range while stock lasts.',
                $updated_value
            );
        }

        if ($updated_value !== $value) {
            update_term_meta($term_id, $meta_key, $updated_value);
        }
    }

    $faq_count = (int) get_term_meta($term_id, 'category_faq', true);

    for ($index = 0; $index < $faq_count; $index++) {
        foreach (array('question', 'answer') as $part) {
            $meta_key = 'category_faq_' . $index . '_' . $part;
            $value = get_term_meta($term_id, $meta_key, true);
            $updated_value = str_replace(array('79p', 'from 79p', 'just 79p'), array('99p', 'from 99p', 'just 99p'), $value);

            if ($term->slug === 'velo' && $part === 'answer') {
                $updated_value = str_replace(
                    'Regular VELO single tins are £3.99 where available.',
                    'Current VELO prices and pack offers are shown live on each product.',
                    $updated_value
                );
            }

            if ($updated_value !== $value) {
                update_term_meta($term_id, $meta_key, $updated_value);
            }
        }
    }
}

$trial_term = get_term_by('slug', '99p-pouches', 'product_cat');

if ($trial_term instanceof WP_Term) {
    wp_update_term($trial_term->term_id, 'product_cat', array('name' => '99p Pouches'));
    update_term_meta($trial_term->term_id, 'category_intro', 'Looking for 99p nicotine pouches? Kangoo Pouches trial pouches start from 99p. Explore selected trials while limited stock is available.');
    update_term_meta($trial_term->term_id, 'category_seo_title', '99p Nicotine Pouches UK');
}

if (function_exists('kangoo_blog_guide_seeder_sync_brand_category_seo')) {
    $brand_sync = kangoo_blog_guide_seeder_sync_brand_category_seo();

    if (is_wp_error($brand_sync)) {
        WP_CLI::warning($brand_sync->get_error_message());
    } else {
        WP_CLI::log('Brand category SEO updated: ' . (int) $brand_sync['updated']);
    }
}

$yoast_meta = get_option('wpseo_taxonomy_meta', array());

if (!empty($yoast_meta['product_cat']) && is_array($yoast_meta['product_cat'])) {
    foreach ($yoast_meta['product_cat'] as $term_id => $values) {
        foreach (array('wpseo_title', 'wpseo_desc', 'wpseo_focuskw') as $key) {
            if (isset($values[$key])) {
                $yoast_meta['product_cat'][$term_id][$key] = str_replace(array('79p', 'from 79p', 'just 79p'), array('99p', 'from 99p', 'just 99p'), $values[$key]);
            }
        }
    }

    if ($trial_term instanceof WP_Term) {
		$yoast_meta['product_cat'][$trial_term->term_id]['wpseo_title'] = '99p Nicotine Pouches UK | Cheap Trial Pouches | Kangoo Pouches';
        $yoast_meta['product_cat'][$trial_term->term_id]['wpseo_desc'] = 'Searching for 99p nicotine pouches? Pick and mix selected ZYN, VELO, FUMi and XQS trial pouches from just 99p each while limited stock is available.';
        $yoast_meta['product_cat'][$trial_term->term_id]['wpseo_focuskw'] = '99p nicotine pouches';
    }

    $velo_term = get_term_by('slug', 'velo', 'product_cat');

    if ($velo_term instanceof WP_Term) {
        $yoast_meta['product_cat'][$velo_term->term_id]['wpseo_desc'] = 'Shop VELO mint, berry, citrus and tropical nicotine pouches at Kangoo Pouches, an independent retailer. See live prices, stock and pack offers. 18+ only.';
    }

    if (function_exists('kangoo_get_brand_authority_profiles')) {
        foreach (kangoo_get_brand_authority_profiles() as $slug => $profile) {
            $brand_term = get_term_by('slug', $slug, 'product_cat');

            if (!$brand_term instanceof WP_Term) {
                continue;
            }

            $yoast_meta['product_cat'][$brand_term->term_id]['wpseo_title'] = sprintf('%s Nicotine Pouches UK | Kangoo Pouches', $profile['label']);
            $yoast_meta['product_cat'][$brand_term->term_id]['wpseo_desc'] = sprintf('Shop %s nicotine pouches at Kangoo Pouches, an independent retailer. Compare live stock, flavours, strengths and current prices. 18+ only.', $profile['label']);
            $yoast_meta['product_cat'][$brand_term->term_id]['wpseo_focuskw'] = strtolower($profile['label']) . ' nicotine pouches';
        }
    }

    update_option('wpseo_taxonomy_meta', $yoast_meta, false);
}

WP_CLI::success('Category content model synchronized. Backup: ' . $backup_file);
