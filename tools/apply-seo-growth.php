<?php

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    exit('Run this file with wp eval-file.');
}

$backup_dir = WP_CONTENT_DIR . '/uploads/kangoo-seo-backups';
wp_mkdir_p($backup_dir);
$stamp = gmdate('Ymd-His');

$backup = array(
    'created_at' => gmdate('c'),
    'wpseo_taxonomy_meta' => get_option('wpseo_taxonomy_meta', array()),
    'terms' => array(),
    'posts' => array(),
);

foreach (get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false)) as $term) {
    $backup['terms'][$term->term_id] = array(
        'name' => $term->name,
        'slug' => $term->slug,
        'description' => $term->description,
        'meta' => get_term_meta($term->term_id),
    );
}

foreach (get_posts(array('post_type' => 'kangoo_blog', 'post_status' => 'any', 'posts_per_page' => -1)) as $post) {
    $backup['posts'][$post->ID] = array(
        'post_title' => $post->post_title,
        'post_name' => $post->post_name,
        'post_status' => $post->post_status,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'meta' => get_post_meta($post->ID),
    );
}

$backup_file = trailingslashit($backup_dir) . 'seo-growth-before-migration-' . $stamp . '.json';
file_put_contents($backup_file, wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

function kangoo_seo_growth_update_term($slug, $data) {
    $term = get_term_by('slug', $slug, 'product_cat');

    if (!$term instanceof WP_Term) {
        WP_CLI::warning('Missing product category: ' . $slug);
        return 0;
    }

    $term_update = array('description' => '');

    if (!empty($data['name'])) {
        $term_update['name'] = $data['name'];
    }

    wp_update_term($term->term_id, 'product_cat', $term_update);
    update_term_meta($term->term_id, 'category_seo_title', $data['heading']);
    update_term_meta($term->term_id, 'category_intro', $data['intro']);
    update_term_meta($term->term_id, 'category_seo_content', $data['content']);

    if (function_exists('update_field')) {
        update_field('category_faq', $data['faq'], 'product_cat_' . $term->term_id);
    }

    return $term->term_id;
}

function kangoo_seo_growth_brand_content($brand, $intro, $flavours, $strengths, $format, $extra = '') {
    return '<h2>Shop ' . esc_html($brand) . ' nicotine pouches in the UK</h2>'
        . '<p>' . esc_html($intro) . ' This page shows the ' . esc_html($brand) . ' products currently available from Kangoo Pouches, with live stock and current single-tin or multi-buy pricing on each product card.</p>'
        . '<h2>' . esc_html($brand) . ' flavours and strengths</h2>'
        . '<p>Available flavours can include ' . esc_html($flavours) . ', depending on current stock. Strength options can include ' . esc_html($strengths) . '. Check the strength shown on each product before ordering rather than relying on colour or flavour alone.</p>'
        . '<h2>Compare the range</h2>'
        . '<p>' . esc_html($format) . ' Use the filters to compare flavour and strength, then open a product page for pouch count, current price, pack options and delivery information. Products that are unavailable are not presented as in-stock choices.</p>'
        . ($extra ? '<p>' . esc_html($extra) . '</p>' : '')
        . '<h2>Buying nicotine pouches responsibly</h2>'
        . '<p>Nicotine is addictive. These tobacco-free nicotine pouches are intended only for adults aged 18 and over. They are not traditional tobacco snus. If you are new to nicotine, avoid choosing a high-strength pouch simply because it offers a flavour you recognise.</p>'
        . '<h2>Delivery and current prices</h2>'
        . '<p>Prices, promotions and availability can change, so the product and basket totals are the authoritative source. Orders placed before 2pm Monday-Friday are dispatched the same day, excluding bank holidays. Free delivery is available when the qualifying basket threshold shown at checkout is reached.</p>';
}

$categories = array(
    'nicotine-pouches' => array(
        'heading' => 'Nicotine Pouches UK',
        'intro' => 'Buy nicotine pouches online in the UK from stocked brands, with mint, berry, citrus, fruit and stronger tobacco-free options available for adults aged 18 and over.',
        'content' => '<h2>Buy nicotine pouches online in the UK</h2><p>Compare tobacco-free nicotine pouches from stocked brands including VELO, ZYN, Nordic Spirit, KILLA, PABLO, FUMi, Ubbs and XQS. The catalogue brings together flavour, strength, pouch count, current price and availability so you can compare real products rather than broad claims.</p><h2>Choose by brand, flavour or strength</h2><p>Use the brand, flavour and strength filters to narrow the range. Mint and ice profiles are common choices for a cooling taste, while berry, citrus, fruit, coffee and tropical options offer different flavour directions. Strength is measured per pouch and should be checked on the individual product page.</p><h2>Low, medium and strong nicotine pouches</h2><p>Lower-strength pouches may suit adults looking for a lighter option, while strong and extra-strong products are intended for experienced nicotine users. A higher number is not automatically a better choice. Product titles, strength badges and descriptions provide the current manufacturer information available to Kangoo.</p><h2>Nicotine pouches and snus are not the same</h2><p>Kangoo sells tobacco-free nicotine pouches, not traditional tobacco snus. Our snus guides explain the UK legal position and the differences between tobacco snus and legal tobacco-free alternatives.</p><h2>Prices, delivery and stock</h2><p>Current prices and pack options appear on each product page. Selected products in the established 99p collection may be reduced to 79p during the current promotion. Orders placed before 2pm Monday-Friday are dispatched the same day, excluding bank holidays, and the checkout shows every available delivery service and live total.</p>',
        'faq' => array(
            array('question' => 'Can I buy nicotine pouches online in the UK?', 'answer' => 'Yes. Kangoo Pouches sells tobacco-free nicotine pouches online to adults aged 18 and over in the UK.'),
            array('question' => 'Are nicotine pouches the same as snus?', 'answer' => 'No. Nicotine pouches are tobacco-free. Traditional snus contains tobacco and is not what Kangoo sells.'),
            array('question' => 'How do I compare nicotine pouch strengths?', 'answer' => 'Check the nicotine amount shown for each pouch on the product page and choose a level appropriate for your experience.'),
        ),
        'yoast_title' => 'Nicotine Pouches UK | Buy Online from 79p | Kangoo',
        'yoast_desc' => 'Buy nicotine pouches online in the UK from VELO, ZYN, Nordic Spirit, KILLA and more. Compare flavours, strengths and live prices from 79p.',
        'focus' => 'nicotine pouches UK',
    ),
    '99p-pouches' => array(
        'name' => '99p Nicotine Pouches',
        'heading' => '99p Nicotine Pouches - Now from 79p',
        'intro' => 'Shop the established 99p nicotine pouch collection, with selected stocked pouches temporarily reduced to 79p while promotional stock lasts.',
        'content' => '<h2>99p nicotine pouches, now from 79p</h2><p>This is Kangoo Pouches’ established 99p nicotine pouch collection. Selected products are currently reduced further to 79p as a temporary promotion. The collection name and URL remain centred on the searched 99p nicotine pouches term, while the live product price shows the current offer.</p><h2>What is included?</h2><p>The available selection can include products from brands such as ZYN, VELO, FUMi, Nordic Spirit and XQS. Stock changes, so only products shown as available can be purchased. Each promotional product is limited to the quantity rules displayed on the product card or basket.</p><h2>Compare before you buy</h2><p>Check the brand, flavour and nicotine strength on every product. Promotional pricing does not make a strong pouch suitable for a new user. Use the product page and filters to find the most appropriate option rather than choosing by price alone.</p><h2>Separate from normal pack pricing</h2><p>The promotional collection is separate from standard single-tin and multi-buy pricing. Products outside this collection keep their normal product and pack prices. Basket totals, stock controls and delivery thresholds continue to apply.</p><h2>Adults only</h2><p>Nicotine is addictive and every product is for adults aged 18 and over. Kangoo sells tobacco-free nicotine pouches, not traditional tobacco snus.</p>',
        'faq' => array(
            array('question' => 'Are these still 99p nicotine pouches?', 'answer' => 'Yes. This is the established 99p collection, with selected products temporarily reduced to 79p while promotional stock lasts.'),
            array('question' => 'Is there a purchase limit?', 'answer' => 'Some promotional pouches are limited per order. The product card and basket show the current limit.'),
            array('question' => 'Are all nicotine pouches 79p?', 'answer' => 'No. The 79p promotion applies only to selected products in this collection. Standard products and pack offers use their normal live prices.'),
        ),
        'yoast_title' => '99p Nicotine Pouches - Now from 79p | Kangoo',
        'yoast_desc' => 'Shop 99p nicotine pouches with selected stocked products now reduced to 79p. Compare brands, flavours and strengths while promotional stock lasts.',
        'focus' => '99p nicotine pouches',
    ),
);

$brand_specs = array(
    'velo' => array('VELO', 'Shop VELO nicotine pouches online in the UK, with live stock across cooling, fruit and citrus profiles.', 'peppermint, spearmint, berry, grape, mango, lemon, orange and watermelon', 'lighter 6mg products through stronger 10mg and 10.9mg options', 'VELO products use slim, tobacco-free pouch formats, with pouch count confirmed on each product page.'),
    'zyn' => array('ZYN', 'Compare ZYN nicotine pouches available in the UK, including mini and regular formats across fruit, mint and coffee profiles.', 'black cherry, red fruits, citrus, mint, coffee and other stocked profiles', 'lower mini strengths and stronger regular options', 'ZYN formats and pouch counts vary by product, so check the individual product details.'),
    'nordic-spirit' => array('Nordic Spirit', 'Browse stocked Nordic Spirit nicotine pouches in fresh mint and berry-style flavours.', 'mint, spearmint, berry and other available profiles', 'the strengths shown on current product cards', 'Nordic Spirit products are tobacco-free and the live product page confirms the exact format.'),
    'killa' => array('KILLA', 'Shop KILLA nicotine pouches for experienced adult users looking for strong flavour and nicotine options.', 'mint, fruit, berry, cola and other stocked profiles', 'strong and extra-strong options', 'KILLA products are nicotine pouches, sometimes searched for as KILLA snus, but they are sold here as tobacco-free alternatives.'),
    'pablo' => array('PABLO', 'Compare stocked PABLO nicotine pouches for experienced adult nicotine users.', 'mint, ice, fruit and other available profiles', 'strong and extra-strong options', 'Strength should be checked carefully because PABLO products can sit at the stronger end of the range.'),
    'fumi' => array('FUMi', 'Browse FUMi nicotine pouches in distinctive fruit, mint and mixed flavour profiles.', 'fruit, berry, mint and other stocked flavours', 'the nicotine levels shown on each live product', 'FUMi availability changes, and the product page is the source for pouch count and format.'),
    'ubbs' => array('Ubbs', 'Shop available Ubbs nicotine pouches and compare current flavours, strengths and pack pricing.', 'berry and other stocked profiles', 'the strength choices currently available', 'Use live product cards to compare the exact strength and pouch count before ordering.'),
    'xqs' => array('XQS', 'Compare stocked XQS nicotine pouches across cooling and fruit-led flavour profiles.', 'mint, citrus, fruit and other available profiles', 'the strengths shown in the current catalogue', 'XQS stock can change, so unavailable flavours are not presented as purchasable choices.'),
);

foreach ($brand_specs as $slug => $spec) {
    list($brand, $intro, $flavours, $strengths, $format) = $spec;
    $categories[$slug] = array(
        'heading' => $brand . ' Nicotine Pouches UK',
        'intro' => $intro,
        'content' => kangoo_seo_growth_brand_content($brand, $intro, $flavours, $strengths, $format),
        'faq' => array(
            array('question' => 'Can I buy ' . $brand . ' nicotine pouches in the UK?', 'answer' => 'Yes. Available ' . $brand . ' nicotine pouches can be ordered online from Kangoo Pouches by adults aged 18 and over.'),
            array('question' => 'Which ' . $brand . ' strength should I choose?', 'answer' => 'Compare the nicotine strength shown on each product and choose an option appropriate for your experience. Stronger is not automatically better.'),
            array('question' => 'Does Kangoo sell ' . $brand . ' snus?', 'answer' => 'Kangoo sells tobacco-free ' . $brand . ' nicotine pouches, not traditional tobacco snus.'),
        ),
        'yoast_title' => $brand . ' Nicotine Pouches UK | Flavours & Strengths',
        'yoast_desc' => 'Shop ' . $brand . ' nicotine pouches in the UK. Compare stocked flavours, strengths, pouch formats, live prices and multi-buy options. Adults 18+ only.',
        'focus' => $brand . ' nicotine pouches',
    );
}

$yoast = get_option('wpseo_taxonomy_meta', array());
$yoast['product_cat'] = isset($yoast['product_cat']) && is_array($yoast['product_cat']) ? $yoast['product_cat'] : array();

foreach ($categories as $slug => $data) {
    $term_id = kangoo_seo_growth_update_term($slug, $data);

    if (!$term_id) {
        continue;
    }

    $yoast['product_cat'][$term_id] = array_merge(isset($yoast['product_cat'][$term_id]) ? $yoast['product_cat'][$term_id] : array(), array(
        'wpseo_title' => $data['yoast_title'],
        'wpseo_desc' => $data['yoast_desc'],
        'wpseo_focuskw' => $data['focus'],
        'wpseo_canonical' => '',
        'wpseo_noindex' => 'default',
    ));
}

$facet_groups = array(
    'pa_flavour' => array(
        'berry' => array('Berry Nicotine Pouches UK', 'Shop berry nicotine pouches in the UK and compare stocked sweet, mixed-fruit and cooling berry profiles.', 'Berry nicotine pouches range from sweeter fruit-led flavours to sharper or cooling blends. Compare the exact brand, strength and live stock on each product rather than assuming every berry pouch tastes or feels the same.', 'Berry Nicotine Pouches UK | Compare Flavours', 'Shop berry nicotine pouches in the UK. Compare stocked brands, nicotine strengths, pouch formats and live prices for adults aged 18 and over.', 'berry nicotine pouches'),
        'mint' => array('Mint Nicotine Pouches UK', 'Compare mint nicotine pouches including peppermint, spearmint, ice and cooling profiles from stocked brands.', 'Mint nicotine pouches are available in several flavour directions, from clean spearmint to sharper peppermint and colder ice profiles. Check strength per pouch and current availability before ordering.', 'Mint Nicotine Pouches UK | Peppermint & Spearmint', 'Shop mint nicotine pouches in the UK, including peppermint, spearmint and cooling options. Compare strength, format, stock and current prices.', 'mint nicotine pouches'),
        'citrus' => array('Citrus Nicotine Pouches UK', 'Shop citrus nicotine pouches and compare lemon, orange and mixed citrus profiles currently in stock.', 'Citrus profiles can range from bright lemon to sweeter orange and mixed fruit blends. Product pages show the exact strength, pouch count and current pack pricing.', 'Citrus Nicotine Pouches UK | Compare Brands', 'Compare citrus nicotine pouches in the UK by brand, strength, pouch count and live price. Tobacco-free products for adults aged 18 and over.', 'citrus nicotine pouches'),
        'coffee' => array('Coffee Nicotine Pouches UK', 'Compare coffee nicotine pouches and current coffee-led flavours from stocked brands.', 'Coffee nicotine pouches offer a different flavour direction from mint and fruit products. Check the nicotine amount and pouch format on each product before choosing.', 'Coffee Nicotine Pouches UK | Shop Online', 'Shop coffee nicotine pouches in the UK. Compare current brands, nicotine strengths, formats, pouch counts and live stock.', 'coffee nicotine pouches'),
        'fruit' => array('Fruit Nicotine Pouches UK', 'Browse fruit nicotine pouches across berry, citrus, tropical and mixed-fruit profiles.', 'Fruit nicotine pouches cover a broad flavour group, so use the filters and product descriptions to compare the actual flavour, nicotine strength and format.', 'Fruit Nicotine Pouches UK | Compare Flavours', 'Shop fruit nicotine pouches in the UK. Compare berry, citrus, tropical and mixed-fruit profiles, strengths and current prices.', 'fruit nicotine pouches'),
        'ice' => array('Ice Nicotine Pouches UK', 'Compare ice nicotine pouches with cooling mint, fruit and menthol-style profiles.', 'Ice describes a cooling flavour direction, not a nicotine strength. Always check the amount per pouch separately, particularly with stronger products.', 'Ice Nicotine Pouches UK | Cooling Flavours', 'Compare cooling ice nicotine pouches in the UK by brand, flavour, strength, pouch count and current live price.', 'ice nicotine pouches'),
        'sweet' => array('Sweet Nicotine Pouches UK', 'Browse sweet nicotine pouches across stocked berry, fruit and mixed-flavour profiles.', 'Sweet flavour descriptions are subjective and can include berry, fruit or confectionery-style notes. Compare the exact product description and nicotine strength.', 'Sweet Nicotine Pouches UK | Compare Flavours', 'Shop sweet nicotine pouches in the UK. Compare stocked fruit and berry profiles, strengths, pouch formats and live prices.', 'sweet nicotine pouches'),
        'tropical' => array('Tropical Nicotine Pouches UK', 'Compare tropical nicotine pouches with mango, citrus and mixed-fruit flavour directions.', 'Tropical ranges can combine mango, citrus and other fruit notes. The live product page confirms the flavour, strength, pouch count and available pack options.', 'Tropical Nicotine Pouches UK | Shop Online', 'Compare tropical nicotine pouches in the UK by brand, flavour, nicotine strength, pouch format and live price.', 'tropical nicotine pouches'),
    ),
    'pa_strength' => array(
        'light' => array('Low-Strength Nicotine Pouches UK', 'Compare lower-strength nicotine pouches currently in stock for adults looking for lighter nicotine options.', 'Lower-strength nicotine pouches may suit adults who want to avoid the stronger end of the range. Compare the exact milligrams per pouch because brand strength labels are not always directly equivalent.', 'Low-Strength Nicotine Pouches UK | Compare Options', 'Compare low-strength nicotine pouches in the UK by milligrams per pouch, brand, flavour, format, stock and live price.', 'low strength nicotine pouches'),
        'medium' => array('Medium-Strength Nicotine Pouches UK', 'Compare medium-strength nicotine pouches across stocked brands and flavours.', 'Medium is a useful browsing band, but the exact nicotine amount per pouch remains the important figure. Check every product before ordering.', 'Medium-Strength Nicotine Pouches UK', 'Shop medium-strength nicotine pouches in the UK. Compare exact nicotine levels, flavours, pouch formats, stock and live prices.', 'medium strength nicotine pouches'),
        'strong' => array('Strong Nicotine Pouches UK', 'Compare strong nicotine pouches intended for experienced adult nicotine users.', 'Strong nicotine pouches should be selected by the exact milligrams per pouch, not flavour or price alone. If you are unsure, compare a lower-strength range first.', 'Strong Nicotine Pouches UK | Compare Brands', 'Compare strong nicotine pouches in the UK by exact strength, brand, flavour, pouch format, stock and current price. Adults 18+ only.', 'strong nicotine pouches'),
        'extra-strong' => array('Extra-Strong Nicotine Pouches UK', 'Browse extra-strong nicotine pouches for experienced adult nicotine users and compare exact strengths carefully.', 'Extra-strong products sit at the highest end of the catalogue. Nicotine is addictive, and these options are not appropriate for inexperienced users.', 'Extra-Strong Nicotine Pouches UK | Adults 18+', 'Compare extra-strong nicotine pouches in the UK by exact nicotine level, brand, flavour, pouch count and live price.', 'extra strong nicotine pouches'),
        '4mg' => array('4mg Nicotine Pouches UK', 'Shop 4mg nicotine pouches and compare current flavours, brands and pouch formats.', 'A 4mg pouch can sit within a lower or medium range depending on the brand system. Compare the exact product details and pouch count alongside price.', '4mg Nicotine Pouches UK | Shop Online', 'Compare 4mg nicotine pouches in the UK by brand, flavour, pouch count, format, live stock and current price.', '4mg nicotine pouches'),
    ),
);

foreach ($facet_groups as $taxonomy => $terms) {
    $yoast[$taxonomy] = isset($yoast[$taxonomy]) && is_array($yoast[$taxonomy]) ? $yoast[$taxonomy] : array();

    foreach ($terms as $slug => $data) {
        $term = get_term_by('slug', $slug, $taxonomy);

        if (!$term instanceof WP_Term || (int) $term->count < 2) {
            continue;
        }

        update_term_meta($term->term_id, 'category_seo_title', $data[0]);
        update_term_meta($term->term_id, 'category_intro', $data[1]);
        update_term_meta($term->term_id, 'category_seo_content', '<h2>' . esc_html($data[0]) . '</h2><p>' . esc_html($data[2]) . '</p><h2>Compare current products</h2><p>Use the product cards to compare brand, exact nicotine strength, flavour, pouch count, price and availability. Nicotine is addictive and every product is for adults aged 18 and over. Kangoo sells tobacco-free nicotine pouches, not traditional tobacco snus.</p>');
        $yoast[$taxonomy][$term->term_id] = array_merge(isset($yoast[$taxonomy][$term->term_id]) ? $yoast[$taxonomy][$term->term_id] : array(), array(
            'wpseo_title' => $data[3],
            'wpseo_desc' => $data[4],
            'wpseo_focuskw' => $data[5],
            'wpseo_noindex' => 'default',
        ));
    }
}
update_option('wpseo_taxonomy_meta', $yoast, false);

function kangoo_seo_growth_merge_post($target_id, $source_id, $new_slug = '') {
    $target = get_post($target_id);
    $source = get_post($source_id);

    if (!$target || !$source) {
        return;
    }

    $update = array('ID' => $target_id);

    if (strlen(wp_strip_all_tags($source->post_content)) > strlen(wp_strip_all_tags($target->post_content))) {
        $update['post_content'] = $source->post_content;
        $update['post_excerpt'] = $source->post_excerpt;
    }

    if ($new_slug !== '') {
        $update['post_name'] = $new_slug;
    }

    wp_update_post($update);
    wp_trash_post($source_id);
}

kangoo_seo_growth_merge_post(474, 818);
kangoo_seo_growth_merge_post(821, 492);
kangoo_seo_growth_merge_post(485, 826);
kangoo_seo_growth_merge_post(820, 480);
kangoo_seo_growth_merge_post(822, 858, 'what-is-snus');
kangoo_seo_growth_merge_post(856, 483, 'what-are-nicotine-pouches');

foreach (array(859 => 'snus-uk', 1138 => 'nicotine-pouches-from-3-99-kangoo-single-tin-prices') as $post_id => $slug) {
    if (get_post($post_id)) {
        wp_update_post(array('ID' => $post_id, 'post_name' => $slug));
    }
}

function kangoo_seo_growth_article($data) {
    $existing = get_page_by_path($data['slug'], OBJECT, 'kangoo_blog');
    $post_id = $existing ? $existing->ID : wp_insert_post(array(
        'post_type' => 'kangoo_blog',
        'post_status' => 'publish',
        'post_title' => $data['title'],
        'post_name' => $data['slug'],
        'post_excerpt' => $data['excerpt'],
        'post_content' => $data['content'],
    ));

    if (is_wp_error($post_id)) {
        WP_CLI::warning($post_id->get_error_message());
        return;
    }

    if ($existing) {
        wp_update_post(array('ID' => $post_id, 'post_title' => $data['title'], 'post_excerpt' => $data['excerpt'], 'post_content' => $data['content']));
    }

    update_post_meta($post_id, '_yoast_wpseo_title', $data['seo_title']);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['seo_desc']);
    update_post_meta($post_id, '_yoast_wpseo_focuskw', $data['focus']);
    wp_set_object_terms($post_id, array($data['topic']), 'blog_topic', false);
}

$articles = array(
    array(
        'slug' => '3mg-nicotine-pouches', 'title' => '3mg Nicotine Pouches: A UK Guide to Lower-Strength Options', 'topic' => 'Strength Guides', 'focus' => '3mg nicotine pouches',
        'seo_title' => '3mg Nicotine Pouches UK | Lower-Strength Guide', 'seo_desc' => 'Compare 3mg nicotine pouches in the UK, including stocked brands, flavours, pouch formats and practical strength considerations for adults.',
        'excerpt' => 'A practical guide to 3mg nicotine pouches, current UK options and how to compare lower strengths.',
        'content' => '<p>3mg nicotine pouches sit toward the lower end of many adult nicotine pouch ranges. They may appeal to people looking for a lighter option, but the number should still be compared with the exact pouch format and manufacturer information.</p><h2>What does 3mg mean?</h2><p>On products listed as 3mg per pouch, each individual pouch contains the stated nicotine amount. Do not confuse this with a figure measured per gram or per tin. Kangoo product pages use the manufacturer information available for the exact product.</p><h2>Which brands offer 3mg pouches?</h2><p>Current stock can include lower-strength mini products from brands such as ZYN. Availability changes, so use the live catalogue rather than assuming every flavour is always stocked.</p><h2>Flavours and formats</h2><p>Lower-strength products can still appear in mint, berry, citrus or other flavour profiles. Mini pouches may feel more discreet under the lip, but pouch size does not replace the need to check nicotine strength.</p><h2>Are 3mg pouches suitable for beginners?</h2><p>Nicotine is addictive, and people who do not already use nicotine should not start. For existing adult nicotine users comparing strengths, 3mg is lower than many strong and extra-strong products. Individual response varies, so stop using a product if it causes unwanted effects.</p><h2>Compare price and pouch count</h2><p>Look at pouch count, current single-tin price and any multi-buy options alongside strength. A cheaper tin is not automatically better value if it contains a different number of pouches or does not match the strength you want.</p><h2>Shop current lower-strength options</h2><p>Use the strength filters and product pages to compare live stock. Kangoo sells tobacco-free nicotine pouches to adults aged 18 and over, not traditional tobacco snus.</p>',
    ),
    array(
        'slug' => 'low-strength-nicotine-pouches', 'title' => 'Low-Strength Nicotine Pouches: UK Brands and Strengths', 'topic' => 'Strength Guides', 'focus' => 'low strength nicotine pouches',
        'seo_title' => 'Low-Strength Nicotine Pouches UK | Compare', 'seo_desc' => 'Compare low-strength nicotine pouches in the UK by milligrams per pouch, brand, flavour, format and current stock. Adults 18+ only.',
        'excerpt' => 'How to compare lower-strength nicotine pouches by milligrams per pouch, format, flavour and live stock.',
        'content' => '<p>Low-strength nicotine pouches give existing adult nicotine users an alternative to the strong and extra-strong end of the market. There is no single industry-wide colour or label that means low strength, so compare the actual milligrams per pouch.</p><h2>Understanding lower strengths</h2><p>Products around 1.5mg, 3mg, 4mg or similar levels may be presented as low, light or mini options depending on the brand. Those labels are useful for browsing, but the numeric amount is the more reliable comparison.</p><h2>Brands and flavours</h2><p>Lower-strength options can appear across ZYN, VELO, FUMi and other stocked ranges. Mint, berry, citrus and coffee flavours do not determine nicotine strength. Check both details independently.</p><h2>Mini and slim pouch formats</h2><p>Some lower-strength products use mini pouches, which may feel smaller under the lip. Slim and regular products can also appear at moderate levels. The product page confirms format and pouch count where that information is available.</p><h2>Who should consider a lower strength?</h2><p>People who do not use nicotine should not begin. Existing adult users may compare lower strengths when a strong pouch feels excessive or when they prefer a lighter option. Nicotine is addictive and individual response varies.</p><h2>Price comparison</h2><p>Compare the full product rather than strength alone: pouch count, flavour, format, current price and multi-buy quantities all matter. Promotional 79p products are separate from standard pack pricing and may be limited per order.</p><h2>Browse the live catalogue</h2><p>Kangoo shows current stock and prices on the product and category pages. All products are tobacco-free nicotine pouches for adults aged 18 and over, not traditional tobacco snus.</p>',
    ),
    array(
        'slug' => 'velo-vs-nordic-spirit', 'title' => 'VELO vs Nordic Spirit: Which Nicotine Pouch Brand Suits You?', 'topic' => 'Comparisons', 'focus' => 'VELO vs Nordic Spirit',
        'seo_title' => 'VELO vs Nordic Spirit | UK Brand Comparison', 'seo_desc' => 'Compare VELO and Nordic Spirit nicotine pouches by flavour, strength, format and current UK availability. An adult-only, stock-aware guide.',
        'excerpt' => 'A practical comparison of VELO and Nordic Spirit nicotine pouches available in the UK.',
        'content' => '<p>VELO and Nordic Spirit are two established tobacco-free nicotine pouch brands available to UK adults. The better choice depends on the flavour, strength and pouch format you prefer, not on a single universal winner.</p><h2>Range and availability</h2><p>VELO currently offers Kangoo shoppers a broader mix of cooling, fruit and citrus-led profiles. Nordic Spirit has a more focused range, often centred on mint and berry-style products. Live category pages are the reliable source because individual flavours can sell out or change.</p><h2>Flavour comparison</h2><p>VELO is useful if you want to compare peppermint, spearmint, berry, grape, tropical or citrus directions. Nordic Spirit may suit shoppers who prefer a smaller, familiar flavour set. Taste is subjective, so product descriptions should be treated as guidance rather than a guarantee.</p><h2>Strength and pouch feel</h2><p>Both brands offer products at different nicotine levels. Read the amount per pouch on the exact product page. Do not assume two tins with similar colours or names have the same strength. Adults with less nicotine experience should avoid jumping directly to a high-strength option.</p><h2>Price and pack options</h2><p>Kangoo shows the current single-tin price and available multi-buy tiers beside each product. Compare the total and per-tin value for the quantity you actually need. Promotional products are separate from normal pack pricing and may carry per-order limits.</p><h2>Which should you choose?</h2><p>Choose VELO when breadth of flavour and strength is the priority. Choose Nordic Spirit when one of its current mint or berry products closely matches your preference. If neither range fits, compare ZYN or another stocked brand rather than choosing only by logo.</p><h2>Important information</h2><p>Both ranges sold by Kangoo are tobacco-free nicotine pouches for adults aged 18 and over. Nicotine is addictive. They are not traditional tobacco snus.</p>',
    ),
    array(
        'slug' => 'zyn-vs-nordic-spirit', 'title' => 'ZYN vs Nordic Spirit: UK Nicotine Pouch Comparison', 'topic' => 'Comparisons', 'focus' => 'ZYN vs Nordic Spirit',
        'seo_title' => 'ZYN vs Nordic Spirit | UK Pouch Comparison', 'seo_desc' => 'Compare ZYN and Nordic Spirit nicotine pouches by flavour, strength, format, price and current UK stock. For adults aged 18 and over.',
        'excerpt' => 'Compare ZYN and Nordic Spirit across flavour, format, strength and current UK availability.',
        'content' => '<p>ZYN and Nordic Spirit both make tobacco-free nicotine pouches, but their current ranges differ in flavour breadth, format and strength. This comparison uses products actually stocked by Kangoo rather than pretending every international product is available in the UK.</p><h2>ZYN range</h2><p>ZYN products can include mini and regular formats with fruit, citrus, coffee and mint profiles. Mini pouches may appeal to adults who prefer a smaller pouch, but the product strength still needs to be checked individually.</p><h2>Nordic Spirit range</h2><p>Nordic Spirit offers a more concentrated selection on Kangoo, including fresh mint and berry-style choices when stocked. A smaller range can make comparison simpler, although it offers fewer flavour directions than ZYN.</p><h2>Strength comparison</h2><p>Brand name is not a strength guide. Compare the nicotine amount per pouch shown on the product page. If you are unsure, choose a lower level rather than assuming a stronger pouch provides better value.</p><h2>Price and availability</h2><p>Current prices, pack options and stock appear live on the relevant category and product pages. Selected promotional tins may be limited per order. Normal multi-buy prices remain separate.</p><h2>ZYN or Nordic Spirit?</h2><p>ZYN may suit shoppers who value a wider choice of flavour and format. Nordic Spirit may suit those who already know they prefer one of its mint or berry products. Both are adult-only, tobacco-free nicotine pouch ranges rather than traditional snus.</p>',
    ),
    array(
        'slug' => 'nordic-spirit-reviews', 'title' => 'Nordic Spirit Review: Flavours, Strengths and UK Options', 'topic' => 'Brand Guides', 'focus' => 'Nordic Spirit reviews',
        'seo_title' => 'Nordic Spirit Review | Flavours & Strengths UK', 'seo_desc' => 'Read a stock-aware Nordic Spirit review covering flavours, strengths, pouch format and UK buying considerations. No fabricated customer claims.',
        'excerpt' => 'An evidence-led look at Nordic Spirit flavours, strengths and current UK buying options.',
        'content' => '<p>This Nordic Spirit review focuses on product facts and current availability rather than inventing a universal taste score. Flavour preference and pouch feel are personal, so the useful comparison is between the options you can actually buy.</p><h2>What is Nordic Spirit?</h2><p>Nordic Spirit is a tobacco-free nicotine pouch brand. The pouch sits under the lip and releases nicotine and flavour without smoke. Products are intended only for adults aged 18 and over.</p><h2>Flavours</h2><p>Kangoo stock can include fresh mint, spearmint and berry-style Nordic Spirit products. The live category page shows what is currently available. Product descriptions explain the intended flavour direction but cannot guarantee how an individual shopper will perceive it.</p><h2>Strengths</h2><p>Check the nicotine amount per pouch rather than relying on a product colour. Stronger products are intended for experienced adult nicotine users. If you are moving from a lower-strength pouch, compare the numbers carefully.</p><h2>Pouch format and count</h2><p>The exact pouch count and format appear on each product page. These details matter when comparing price because a tin price alone does not explain pouch count or nicotine strength.</p><h2>Value and delivery</h2><p>Kangoo displays live prices and any available pack tiers. Orders placed before 2pm Monday-Friday are dispatched the same day, excluding bank holidays. Checkout shows the delivery methods available for the basket.</p><h2>Verdict</h2><p>Nordic Spirit is worth considering if its focused flavour range and available strengths match your preferences. Compare it with VELO or ZYN if you want a broader flavour choice. This is not medical advice and nicotine is addictive.</p>',
    ),
    array(
        'slug' => 'velo-reviews', 'title' => 'VELO Review: Flavours, Strengths and UK Prices', 'topic' => 'Brand Guides', 'focus' => 'VELO reviews',
        'seo_title' => 'VELO Review | Flavours, Strengths & UK Prices', 'seo_desc' => 'Review the VELO nicotine pouch range by flavour, strength, format and current UK pricing. Stock-aware guidance for adults aged 18 and over.',
        'excerpt' => 'A practical review of VELO flavours, strengths, formats and live UK buying options.',
        'content' => '<p>VELO has one of the broader tobacco-free nicotine pouch ranges available through Kangoo. This review explains the differences shoppers can verify: flavour, nicotine strength, pouch format, pouch count, price and stock.</p><h2>VELO flavour range</h2><p>Depending on current availability, VELO flavours can include peppermint, spearmint, berry, grape, mango, lemon, orange and watermelon. Cooling mint products and sweeter fruit products provide distinctly different directions, so there is no single best VELO flavour for everyone.</p><h2>Strength options</h2><p>VELO products on Kangoo span lighter and stronger nicotine levels. Read the exact amount per pouch on each product. A flavour you enjoy is not a reason to select a strength above your experience.</p><h2>Format and pouch count</h2><p>Many VELO products use slim pouch formats, but the exact format and count should be confirmed on the individual product page. These details help make a fair price comparison between tins.</p><h2>Current prices and multi-buys</h2><p>Single-tin prices and pack tiers are shown live. Some VELO products also appear in the established 99p promotional collection when allocated stock is available. Promotional limits and normal multi-buy ladders are separate.</p><h2>How VELO compares</h2><p>VELO generally offers broader flavour choice than Nordic Spirit in the current Kangoo catalogue. ZYN adds different mini and regular formats. Compare the categories directly if format matters as much as flavour.</p><h2>Verdict</h2><p>VELO is a strong range for adults who want to compare several flavour and strength combinations from one brand. The best choice is the product that matches your preferred flavour and suitable nicotine level. Nicotine is addictive and these products are for adults aged 18 and over.</p>',
    ),
);

foreach ($articles as $article) {
    kangoo_seo_growth_article($article);
}

$product_id = 306;
$product = get_post($product_id);

if ($product) {
    wp_update_post(array(
        'ID' => $product_id,
        'post_title' => str_ireplace('11mg', '10.9mg', $product->post_title),
        'post_name' => 'freezing-peppermint-10-9mg',
        'post_content' => str_ireplace('11mg', '10.9mg', $product->post_content),
        'post_excerpt' => str_ireplace('11mg', '10.9mg', $product->post_excerpt),
    ));

    foreach (get_post_meta($product_id) as $key => $values) {
        foreach ($values as $value) {
            $decoded = maybe_unserialize($value);

            if (is_string($decoded) && stripos($decoded, '11mg') !== false) {
                update_post_meta($product_id, $key, str_ireplace('11mg', '10.9mg', $decoded));
            }
        }
    }

    update_post_meta($product_id, 'strength_mg', '10.9');
    update_post_meta($product_id, '_yoast_wpseo_title', 'VELO Freezing Peppermint 10.9mg Pouches | Kangoo');
}

foreach (wc_get_products(array('status' => 'publish', 'limit' => -1, 'return' => 'objects')) as $catalogue_product) {
    if (!$catalogue_product instanceof WC_Product) {
        continue;
    }

    $catalogue_id = $catalogue_product->get_id();

    if (trim((string) get_post_meta($catalogue_id, '_yoast_wpseo_metadesc', true)) === '') {
        $description = sprintf(
            'Shop %s online. Compare live stock, pouch count, pack prices and UK delivery options. Adults 18+ only.',
            $catalogue_product->get_name()
        );
        update_post_meta($catalogue_id, '_yoast_wpseo_metadesc', mb_substr($description, 0, 155));
    }

    if (trim((string) get_post_meta($catalogue_id, '_yoast_wpseo_focuskw', true)) === '') {
        update_post_meta($catalogue_id, '_yoast_wpseo_focuskw', $catalogue_product->get_name());
    }
}

$page_descriptions = array(
    'privacy-policy' => 'Read the Kangoo Pouches privacy policy, including how personal information is collected, used, stored and protected when you use our website.',
    'returns-and-refunds' => 'Read the Kangoo Pouches returns and refunds policy, including eligibility, time limits, exclusions and how to contact us about an order.',
    'cookie-policy' => 'Learn how Kangoo Pouches uses cookies and similar technologies, what they do and how you can manage your preferences.',
    '18-plus-policy' => 'Read the Kangoo Pouches adults-only policy and the measures used to restrict nicotine pouch sales to customers aged 18 and over.',
    'compare-pouches' => 'Compare stocked nicotine pouches by brand, flavour, strength, format and current price to find a suitable adult-only option.',
    'kangoo-app' => 'Download the Kangoo Pouches app for convenient access to the latest nicotine pouch catalogue, account features and current offers.',
    'contact' => 'Contact Kangoo Pouches for help with products, orders, delivery, returns, rewards or your online account.',
    'kangoo-rewards' => 'Learn how Kangoo Rewards works, how points are earned and how eligible customers can use rewards on future purchases.',
    'terms-and-conditions' => 'Read the Kangoo Pouches website and sales terms, including ordering, payment, delivery, account and product conditions.',
    'referral-program' => 'Learn how the Kangoo Pouches referral programme works, including eligibility, referral rewards and programme conditions.',
    'faq' => 'Find answers to common Kangoo Pouches questions about products, strength, ordering, delivery, returns, rewards and adult-only sales.',
);

$front_page_id = (int) get_option('page_on_front');

if ($front_page_id > 0) {
    $home_title = 'Kangoo Pouches UK | Nicotine Pouches from 79p';
    $home_description = 'Shop nicotine pouches from VELO, ZYN, Nordic Spirit, KILLA and more. Compare flavours, strengths and live UK prices from 79p. Adults 18+ only.';

    update_post_meta($front_page_id, '_yoast_wpseo_title', $home_title);
    update_post_meta($front_page_id, '_yoast_wpseo_metadesc', $home_description);
    update_post_meta($front_page_id, '_yoast_wpseo_focuskw', 'Kangoo Pouches');
    update_post_meta($front_page_id, '_yoast_wpseo_opengraph-title', $home_title);
    update_post_meta($front_page_id, '_yoast_wpseo_opengraph-description', $home_description);
    update_post_meta($front_page_id, '_yoast_wpseo_twitter-title', $home_title);
    update_post_meta($front_page_id, '_yoast_wpseo_twitter-description', $home_description);
}

foreach ($page_descriptions as $slug => $description) {
    $page = get_page_by_path($slug, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        update_post_meta($page->ID, '_yoast_wpseo_metadesc', $description);
    }
}

foreach (get_posts(array('post_type' => 'kangoo_blog', 'post_status' => 'publish', 'posts_per_page' => -1)) as $article) {
    if (stripos($article->post_content, '<h1') !== false) {
        wp_update_post(array(
            'ID' => $article->ID,
            'post_content' => preg_replace(array('/<h1\b/i', '/<\/h1>/i'), array('<h2', '</h2>'), $article->post_content),
        ));
    }
}

$source_section = '<h2>Sources and further reading</h2><ul><li><a href="https://www.legislation.gov.uk/uksi/2016/507/contents/made" rel="nofollow noopener">The Tobacco and Related Products Regulations 2016</a></li><li><a href="https://cot.food.gov.uk/Statement%20on%20the%20bioavailability%20of%20nicotine%20from%20the%20use%20of%20oral%20nicotine%20pouches%20and%20assessment%20of%20the%20potential%20toxicological%20risk%20to%20users" rel="nofollow noopener">UK Committee on Toxicity: oral nicotine pouch risk statement</a></li><li><a href="https://www.bfr.bund.de/cm/349/health-risk-assessment-of-nicotine-pouches.pdf" rel="nofollow noopener">German Federal Institute for Risk Assessment: Health risk assessment of nicotine pouches</a></li></ul><p><small>Sources are provided for context. This article is not medical advice.</small></p>';

foreach (array(857, 831, 859, 822, 823) as $source_post_id) {
    $source_post = get_post($source_post_id);

    if ($source_post instanceof WP_Post) {
        $source_content = str_replace(
            'https://www.gov.uk/government/publications/tobacco-and-vapes-bill-2024-factsheets',
            'https://cot.food.gov.uk/Statement%20on%20the%20bioavailability%20of%20nicotine%20from%20the%20use%20of%20oral%20nicotine%20pouches%20and%20assessment%20of%20the%20potential%20toxicological%20risk%20to%20users',
            $source_post->post_content
        );

        if (stripos($source_content, 'Sources and further reading') === false) {
            $source_content = rtrim($source_content) . $source_section;
        }

        if ($source_content !== $source_post->post_content) {
            wp_update_post(array('ID' => $source_post_id, 'post_content' => $source_content));
        }
    }
}

update_post_meta(474, '_yoast_wpseo_title', 'Best Nicotine Pouches UK (2026) | Top Picks');

flush_rewrite_rules(false);
WP_CLI::success('SEO growth migration complete. Backup: ' . $backup_file);
