<?php

defined('ABSPATH') || exit;

if (!defined('WP_CLI') || !WP_CLI) {
    exit('Run this file with wp eval-file.');
}

const KANGOO_SEO_90_VERSION = '2026-06-15.3';

function kangoo_seo_90_post($slug, $post_type = 'kangoo_blog') {
    return get_page_by_path($slug, OBJECT, $post_type);
}

function kangoo_seo_90_field($post_id, $name, $value) {
    if (function_exists('update_field')) {
        update_field($name, $value, $post_id);
        return;
    }

    update_post_meta($post_id, $name, $value);
}

function kangoo_seo_90_sources() {
    return array(
        array(
            'label' => 'UK Government - Tobacco and Vapes Bill factsheet',
            'url' => 'https://www.gov.uk/government/publications/tobacco-and-vapes-bill-2024-factsheets/tobacco-and-vapes-bill-creating-a-smoke-free-uk-and-tackling-youth-vaping-factsheet',
        ),
        array(
            'label' => 'UK Parliament - Tobacco and Vapes Bill',
            'url' => 'https://bills.parliament.uk/bills/3703',
        ),
        array(
            'label' => 'Committee on Toxicity - oral nicotine pouch statement',
            'url' => 'https://cot.food.gov.uk/Oral%20nicotine%20pouches',
        ),
        array(
            'label' => 'Action on Smoking and Health - nicotine pouches explainer',
            'url' => 'https://ash.org.uk/uploads/190913-Nicotine-pouches.pdf',
        ),
    );
}

function kangoo_seo_90_backup() {
    $upload = wp_upload_dir();
    $dir = trailingslashit($upload['basedir']) . 'kangoo-seo-backups';
    wp_mkdir_p($dir);

    $posts = get_posts(array(
        'post_type' => array('kangoo_blog', 'product'),
        'post_status' => array('publish', 'draft', 'trash'),
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    $terms = get_terms(array(
        'taxonomy' => array('product_cat', 'pa_flavour', 'pa_strength'),
        'hide_empty' => false,
    ));

    $data = array(
        'created_at' => current_time('mysql'),
        'version' => KANGOO_SEO_90_VERSION,
        'wpseo_taxonomy_meta' => get_option('wpseo_taxonomy_meta', array()),
        'posts' => array(),
        'terms' => array(),
    );

    foreach ($posts as $post_id) {
        $post = get_post($post_id, ARRAY_A);
        $data['posts'][$post_id] = array(
            'post' => $post,
            'meta' => get_post_meta($post_id),
        );
    }

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $data['terms'][$term->taxonomy . ':' . $term->term_id] = array(
                'term' => (array) $term,
                'meta' => get_term_meta($term->term_id),
            );
        }
    }

    $file = trailingslashit($dir) . 'seo-90day-backup-' . gmdate('Ymd-His') . '.json';
    file_put_contents($file, wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    WP_CLI::log('Backup: ' . $file);
}

function kangoo_seo_90_update_article($slug, $data) {
    $post = kangoo_seo_90_post($slug);
    $post_data = array(
        'post_type' => 'kangoo_blog',
        'post_status' => 'publish',
        'post_title' => $data['title'],
        'post_name' => $slug,
        'post_excerpt' => $data['excerpt'],
        'post_content' => $data['content'],
    );

    if ($post) {
        $post_data['ID'] = $post->ID;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        WP_CLI::warning($slug . ': ' . $post_id->get_error_message());
        return 0;
    }

    update_post_meta($post_id, '_yoast_wpseo_title', $data['seo_title']);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['seo_desc']);
    update_post_meta($post_id, '_yoast_wpseo_focuskw', $data['focus']);
    kangoo_seo_90_field($post_id, 'blog_seo_title', $data['seo_title']);
    kangoo_seo_90_field($post_id, 'blog_meta_description', $data['seo_desc']);
    kangoo_seo_90_field($post_id, 'blog_standfirst', $data['excerpt']);
    kangoo_seo_90_field($post_id, 'blog_eyebrow', isset($data['eyebrow']) ? $data['eyebrow'] : 'Guide');

    if (!empty($data['sources'])) {
        kangoo_seo_90_field($post_id, 'blog_sources', $data['sources']);
    }

    WP_CLI::log('Updated article: ' . $slug);
    return (int) $post_id;
}

function kangoo_seo_90_merge($source_slug, $target_slug) {
    $source = kangoo_seo_90_post($source_slug);
    $target = kangoo_seo_90_post($target_slug);

    if ($source && $target && $source->ID !== $target->ID) {
        wp_trash_post($source->ID);
        WP_CLI::log('Consolidated ' . $source_slug . ' into ' . $target_slug);
    }
}

function kangoo_seo_90_update_category($slug, $data, &$yoast) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term instanceof WP_Term) {
        WP_CLI::warning('Missing category: ' . $slug);
        return;
    }

    update_term_meta($term->term_id, 'category_seo_title', $data['heading']);
    update_term_meta($term->term_id, 'category_intro', $data['intro']);
    update_term_meta($term->term_id, 'category_seo_content', $data['content']);
    update_term_meta($term->term_id, 'category_faq', $data['faq']);

    $yoast['product_cat'] = isset($yoast['product_cat']) && is_array($yoast['product_cat']) ? $yoast['product_cat'] : array();
    $yoast['product_cat'][$term->term_id] = array_merge(isset($yoast['product_cat'][$term->term_id]) ? $yoast['product_cat'][$term->term_id] : array(), array(
        'wpseo_title' => $data['seo_title'],
        'wpseo_desc' => $data['seo_desc'],
        'wpseo_focuskw' => $data['focus'],
        'wpseo_noindex' => 'default',
        'wpseo_canonical' => '',
    ));
    WP_CLI::log('Updated category: ' . $slug);
}

function kangoo_seo_90_update_product($product_id, $data) {
    $product = wc_get_product($product_id);
    if (!$product) {
        WP_CLI::warning('Missing product: ' . $product_id);
        return;
    }

    wp_update_post(array(
        'ID' => $product_id,
        'post_excerpt' => $data['excerpt'],
        'post_content' => $data['content'],
    ));
    update_post_meta($product_id, '_yoast_wpseo_title', $data['seo_title']);
    update_post_meta($product_id, '_yoast_wpseo_metadesc', $data['seo_desc']);
    update_post_meta($product_id, '_yoast_wpseo_focuskw', $data['focus']);
    WP_CLI::log('Updated product: ' . $product_id . ' ' . $product->get_name());
}

if (get_option('kangoo_seo_90_version') === KANGOO_SEO_90_VERSION) {
    WP_CLI::success('SEO 90-day migration already applied: ' . KANGOO_SEO_90_VERSION);
    return;
}

kangoo_seo_90_backup();

$yoast = get_option('wpseo_taxonomy_meta', array());

$category_common = '<h2>How to compare nicotine pouches</h2><p>Start with the nicotine amount per pouch, then compare flavour, pouch format, pouch count, current stock and pack price. Brand dot systems are not directly interchangeable, so the milligrams shown on the product page are the clearest comparison. Nicotine is addictive and these products are for adults aged 18 and over.</p><h2>Live prices and availability</h2><p>Kangoo product cards use current WooCommerce prices and stock. Promotional products are shown separately from normal single-tin and multi-buy pricing. If a product is unavailable it should not be treated as a current buying option.</p><h2>Delivery</h2><p>Orders placed before 2pm Monday-Friday are dispatched the same day, excluding weekends and bank holidays. Free delivery applies when the live basket reaches the displayed threshold.</p>';

$categories = array(
    'nicotine-pouches' => array(
        'heading' => 'Nicotine Pouches UK',
        'intro' => 'Buy nicotine pouches online in the UK. Compare live prices, strengths, flavours and in-stock products from VELO, ZYN, Nordic Spirit, KILLA, PABLO and more.',
        'content' => '<h2>Buy nicotine pouches online in the UK</h2><p>Nicotine pouches are small tobacco-free pouches designed for adult nicotine users. Kangoo brings current products into one catalogue so you can compare exact strength, flavour, pouch format, pouch count and price without relying on inconsistent brand labels.</p><h2>Brands, flavours and strengths</h2><p>Current ranges include VELO, ZYN, Nordic Spirit, KILLA, PABLO, FUMi, XQS and Ubbs where stock is available. Mint and berry are the broadest flavour groups, with citrus, fruit, coffee, ice and tropical choices also represented. Lower-strength products and stronger ranges are separated through the site filters; always check milligrams per pouch before ordering.</p>' . $category_common . '<h2>Nicotine pouches and snus are different</h2><p>Kangoo sells tobacco-free nicotine pouches, not traditional tobacco snus. Our educational guides explain the terminology, UK position and product differences without presenting nicotine pouches as tobacco products.</p>',
        'faq' => array(
            array('question' => 'What are nicotine pouches?', 'answer' => 'Nicotine pouches are tobacco-free pouches containing nicotine, flavourings, sweeteners and plant-based filling material. They are placed between the lip and gum and disposed of after use.'),
            array('question' => 'Can I buy nicotine pouches online in the UK?', 'answer' => 'Yes. Adults aged 18 and over can order stocked nicotine pouches from Kangoo Pouches for UK delivery.'),
            array('question' => 'Does Kangoo sell snus?', 'answer' => 'No. Kangoo sells tobacco-free nicotine pouches, not traditional tobacco snus.'),
        ),
		'seo_title' => 'Nicotine Pouches UK | Buy Online from 79p | Kangoo Pouches',
        'seo_desc' => 'Buy nicotine pouches online in the UK. Compare live prices, flavours and strengths from VELO, ZYN, Nordic Spirit, KILLA and more. Adults 18+.',
        'focus' => 'nicotine pouches UK',
    ),
    '99p-pouches' => array(
        'heading' => '99p Nicotine Pouches',
        'intro' => 'Shop the established 99p nicotine pouch collection, with selected products temporarily reduced to 79p while promotional stock lasts.',
        'content' => '<h2>99p nicotine pouches with selected products now 79p</h2><p>This collection is Kangoo\'s permanent home for selected low-price nicotine pouches. The 79p price is a temporary promotion on qualifying products, not a replacement name for the collection and not a claim that every pouch on the site costs 79p.</p><h2>Check the product before ordering</h2><p>Promotional stock can include different brands, flavours and nicotine strengths. Compare the exact milligrams per pouch and any one-per-order restriction shown on the product card. A low promotional price does not make a high-strength pouch suitable for an inexperienced user.</p>' . $category_common,
        'faq' => array(
            array('question' => 'Are these still 99p nicotine pouches?', 'answer' => 'Yes. This is the established 99p collection, with selected products temporarily reduced to 79p while promotional stock lasts.'),
            array('question' => 'Is every nicotine pouch 79p?', 'answer' => 'No. The promotion applies only to selected products in this collection. Standard products and pack offers keep their own live prices.'),
            array('question' => 'Is there a purchase limit?', 'answer' => 'Some promotional tins are limited to one per order. The product card and basket show the current rule.'),
        ),
		'seo_title' => '99p Nicotine Pouches - Now from 79p | Kangoo Pouches',
        'seo_desc' => 'Shop 99p nicotine pouches with selected stocked products now 79p. Compare brands, flavours and strengths while promotional stock lasts. Adults 18+.',
        'focus' => '99p nicotine pouches',
    ),
);

$brand_data = array(
    'velo' => array('VELO', 'cooling mint, berry, citrus and tropical profiles', '6mg to 14mg products where stocked'),
    'zyn' => array('ZYN', 'mint, berry, citrus, coffee and fruit profiles', 'mini and regular products across lower and stronger options'),
    'nordic-spirit' => array('Nordic Spirit', 'fresh mint and berry-style profiles', 'the exact strengths currently shown on the two live product cards'),
    'killa' => array('KILLA', 'mint, berry, fruit, cola and cooling profiles', 'strong and extra-strong choices for experienced adult users'),
    'pablo' => array('PABLO', 'mint, ice and fruit-led profiles', 'strong and extra-strong choices for experienced adult users'),
);

foreach ($brand_data as $slug => $brand) {
    $name = $brand[0];
    $categories[$slug] = array(
        'heading' => $name . ' Nicotine Pouches',
        'intro' => 'Shop ' . $name . ' nicotine pouches in the UK and compare current flavours, exact strengths, pouch formats, live prices and stock.',
        'content' => '<h2>' . esc_html($name) . ' nicotine pouches in the UK</h2><p>Use this category to compare the ' . esc_html($name) . ' products Kangoo currently stocks. The range can include ' . esc_html($brand[1]) . ', with ' . esc_html($brand[2]) . '. Availability changes, so live product cards are the source for price and stock.</p>' . $category_common . '<h2>' . esc_html($name) . ' nicotine pouches, not tobacco snus</h2><p>Some shoppers use the word snus when searching for ' . esc_html($name) . '. Kangoo sells the tobacco-free nicotine pouch products shown here, not traditional tobacco snus.</p>',
        'faq' => array(
            array('question' => 'Can I buy ' . $name . ' nicotine pouches in the UK?', 'answer' => 'Yes. Adults aged 18 and over can order the available ' . $name . ' products shown on this page.'),
            array('question' => 'Which ' . $name . ' strength should I choose?', 'answer' => 'Compare the exact milligrams per pouch on each product. Dot systems and labels are not directly equivalent between brands.'),
            array('question' => 'Does Kangoo sell ' . $name . ' snus?', 'answer' => 'Kangoo sells tobacco-free ' . $name . ' nicotine pouches, not traditional tobacco snus.'),
        ),
        'seo_title' => $name . ' Nicotine Pouches UK | Flavours & Strengths',
        'seo_desc' => 'Shop ' . $name . ' nicotine pouches in the UK. Compare current flavours, exact strengths, pouch formats, live prices and stock. Adults 18+.',
        'focus' => $name . ' nicotine pouches',
    );
}

$categories['velo']['content'] = '<h2>What are VELO nicotine pouches?</h2><p>VELO nicotine pouches are small, tobacco-free pouches used under the upper lip. They release nicotine without smoke, vapour or tobacco leaf. Kangoo stocks selected VELO mint, fruit, berry, citrus and cooling products, with current availability shown on the product cards above.</p><h2>How to use VELO nicotine pouches</h2><ol><li><strong>Place:</strong> Put one pouch between your upper lip and gum.</li><li><strong>Use:</strong> Leave it in place for the period stated on the product packaging and remove it if the sensation is uncomfortable.</li><li><strong>Dispose:</strong> Put the used pouch in general waste. Where the can includes a catch lid, it can hold used pouches temporarily until a bin is available.</li></ol><p>Do not chew, swallow or reuse a nicotine pouch. Always follow the instructions supplied with the exact product.</p><h2>VELO strengths and dots</h2><p>The published Kangoo VELO catalogue currently includes products labelled from 6mg to 17mg per pouch, although live stock changes. VELO dots show relative strength within the brand and are not a universal conversion to milligrams. Compare the exact mg per pouch on each card or read our <a href="/blog/velo-strength-dots-explained/">VELO strength dots guide</a>.</p><h2>VELO flavours in the current range</h2><ul><li><strong>Mint and cooling:</strong> peppermint, spearmint and ice-led products.</li><li><strong>Berry and fruit:</strong> grape, berry, cherry and watermelon profiles.</li><li><strong>Citrus and tropical:</strong> lime, lemon, orange, mango and tropical profiles where stocked.</li></ul><p>Flavour does not indicate strength. Check the product name, nicotine amount, pouch count and format separately.</p><h2>VELO safety and age restriction</h2><p>Nicotine is addictive. VELO products are for adults aged 18 and over who already use nicotine, and they should be kept away from children and pets. They are not medicines or stop-smoking treatments. Read the broader <a href="/blog/what-are-nicotine-pouches/">nicotine pouch guide</a> for contents, use, disposal and UK context.</p>' . $category_common . '<h2>VELO nicotine pouches, not tobacco snus</h2><p>Some shoppers search for VELO snus, but the products sold here are tobacco-free nicotine pouches rather than traditional tobacco snus.</p>';

$categories['zyn']['content'] = '<h2>What are ZYN nicotine pouches?</h2><p>ZYN nicotine pouches are tobacco-free oral pouches designed to sit discreetly between the upper lip and gum. They release nicotine without combustion, smoke or vapour. The live ZYN products above show Kangoo\'s current prices, stock, pouch formats and pack options.</p><h2>How to use ZYN</h2><ol><li><strong>Place:</strong> Put one pouch under your upper lip.</li><li><strong>Use:</strong> Keep it in place for the period directed on the can and remove it if it feels uncomfortable.</li><li><strong>Dispose:</strong> Place the used pouch in general waste. A catch lid can be used for temporary storage where the packaging includes one.</li></ol><p>Do not chew, swallow or reuse the pouch. Follow the instructions on the exact ZYN can because formats and formulations can differ.</p><h2>ZYN mini and regular formats</h2><p>Mini pouches use a smaller physical format, while regular products use a larger pouch. Format does not determine nicotine strength. Kangoo\'s published ZYN catalogue currently includes products labelled from 1.5mg to 16.5mg per pouch, but availability changes and the exact product card remains authoritative.</p><h2>Popular ZYN flavour groups</h2><ul><li><strong>Mint:</strong> Cool Mint, Spearmint, Icy Mint and Menthol Ice products where stocked.</li><li><strong>Fruit and berry:</strong> Black Cherry, Red Fruits, Blueberry Frost and Red Berry Fizz.</li><li><strong>Other profiles:</strong> Citrus, Coffee and selected seasonal flavours.</li></ul><p>Choose by exact mg per pouch, flavour, format and pouch count rather than assuming mini means weak or a familiar flavour means mild.</p><h2>ZYN safety and age restriction</h2><p>Nicotine is addictive. ZYN products are restricted to adults aged 18 and over who already use nicotine, and must be kept away from children and pets. They are not medicines or stop-smoking treatments. For brand background, read <a href="/blog/what-is-zyn-uk-guide-to-zyn-nicotine-pouches/">What is ZYN?</a>; for general use and UK context, read <a href="/blog/what-are-nicotine-pouches/">What are nicotine pouches?</a>.</p>' . $category_common . '<h2>ZYN nicotine pouches, not tobacco snus</h2><p>ZYN is sometimes described as snus in searches, but the ZYN products sold by Kangoo are tobacco-free nicotine pouches, not traditional tobacco snus.</p>';

$categories['pablo']['content'] = '<h2>What are PABLO nicotine pouches?</h2><p>PABLO is a tobacco-free nicotine pouch range known for high-strength products and cooling or fruit-led flavours. The products sit between the lip and gum and release nicotine without smoke or vapour. Kangoo\'s published PABLO catalogue currently includes products labelled at 24mg and 30mg per pouch; live stock is shown above.</p><h2>PABLO strengths and who they are for</h2><p>PABLO products are at the strong end of Kangoo\'s range and are intended only for experienced adult nicotine users. They are not suitable for people who do not already use nicotine. Compare the exact milligrams per pouch rather than relying on names such as Ice Cold, Frosted Ice or Grape Ice.</p><h2>How to use and dispose of PABLO pouches</h2><ol><li>Place one pouch between the upper lip and gum.</li><li>Follow the use instructions printed on the exact can and remove it if it feels uncomfortable.</li><li>Dispose of the used pouch in general waste; use the catch lid temporarily where one is provided.</li></ol><p>Do not chew, swallow or reuse nicotine pouches. Pouch format and count can vary, so check the individual product specification instead of assuming every PABLO can is identical.</p><h2>Current PABLO flavours and products</h2><p>Kangoo\'s published range includes Ice Cold, Grape Ice, Red and Frosted Ice products, with availability changing as stock moves. Product cards and basket prices are the source for current availability and multi-buy value.</p><h2>PABLO safety and age restriction</h2><p>Nicotine is addictive. PABLO products are for adults aged 18 and over, must be kept away from children and pets, and are not medicines or stop-smoking treatments. Adults uncertain about nicotine use should seek advice from a qualified health professional.</p>' . $category_common . '<h2>PABLO nicotine pouches, not tobacco snus</h2><p>PABLO is often found through snus-related searches, but Kangoo sells the tobacco-free nicotine pouch products shown here, not traditional tobacco snus.</p>';

foreach ($categories as $slug => $data) {
    kangoo_seo_90_update_category($slug, $data, $yoast);
}
update_option('wpseo_taxonomy_meta', $yoast, false);

$articles = array(
    'what-are-nicotine-pouches' => array(
        'title' => 'What Are Nicotine Pouches? UK Guide',
        'excerpt' => 'Nicotine pouches are tobacco-free oral pouches placed between the lip and gum. This source-led UK guide explains what they contain, how they are used, strengths, disposal and current rules.',
        'content' => '<p><strong>Nicotine pouches are small, tobacco-free oral pouches containing nicotine, flavourings, sweeteners and plant-based filling material. An adult user places one between the lip and gum, where nicotine is released through the lining of the mouth. The used pouch is removed and put in a bin.</strong></p><nav aria-label="On this page"><strong>On this page</strong><ul><li><a href="#contents">What is inside a nicotine pouch?</a></li><li><a href="#use">How are nicotine pouches used?</a></li><li><a href="#strength">Strengths and brand labels</a></li><li><a href="#safety">Safety and UK rules</a></li><li><a href="#compare">How to compare products</a></li></ul></nav><h2 id="contents">What is inside a nicotine pouch?</h2><p>A typical pouch contains nicotine, flavouring, sweetener, plant fibre and ingredients that control moisture and pH. It does not contain tobacco leaf. Ingredients vary by manufacturer, so the packaging and product page should be checked rather than treating every brand as identical.</p><h2 id="use">How are nicotine pouches used?</h2><ol><li>Choose a strength appropriate for your existing nicotine experience.</li><li>Place one pouch between the upper or lower lip and gum.</li><li>Leave it in place for the time stated by the manufacturer. A tingling sensation can occur.</li><li>Remove the pouch and dispose of it in general waste. Do not swallow the pouch.</li></ol><p>Pouches create no smoke or vapour. They should still be kept away from children and pets, and nicotine remains addictive.</p><h2 id="strength">Strengths, dots and milligrams</h2><p>The clearest comparison is milligrams of nicotine per pouch. Brand dot scales are internal guides and are not directly equivalent: three dots from one brand may not match three dots from another. Lower-strength products can sit around 2mg to 4mg per pouch, while stronger products can reach 9mg, 12mg or more. The product label is the authority.</p><h2>Popular nicotine pouch brands</h2><p>UK shoppers commonly compare brands such as ZYN, VELO and Nordic Spirit. Kangoo also stocks KILLA, PABLO, FUMi, XQS and Ubbs where available. Each brand differs in pouch size, moisture, flavour and strength range.</p><h2 id="safety">Safety, age and UK rules</h2><p>Nicotine is addictive and can cause unwanted effects. Nicotine pouches are not for children, non-nicotine users, people who are pregnant, or anyone advised to avoid nicotine. Kangoo restricts sales to adults aged 18 and over. UK rules are developing, so this guide links to current government, parliamentary and independent sources rather than making medical or cessation claims.</p><h2>Nicotine pouches and snus</h2><p>Traditional snus contains tobacco. Nicotine pouches sold by Kangoo are tobacco-free. The terms are sometimes mixed in searches, but they describe different product categories.</p><h2 id="compare">How to compare nicotine pouches</h2><table><thead><tr><th>Check</th><th>Why it matters</th></tr></thead><tbody><tr><td>mg per pouch</td><td>The most useful strength comparison.</td></tr><tr><td>Flavour</td><td>Mint, berry, citrus, coffee and other profiles feel different.</td></tr><tr><td>Format</td><td>Mini and slim pouches differ in size and fit.</td></tr><tr><td>Pouch count</td><td>Tin contents vary by product.</td></tr><tr><td>Price and stock</td><td>Use the live product page, not an old guide, for current information.</td></tr></tbody></table><p>Ready to compare current products? Visit the <a href="/product-category/nicotine-pouches/">nicotine pouches UK shop</a>. For practical placement guidance, read <a href="/blog/how-to-use-nicotine-pouches-placement-timing-and-tips/">how to use nicotine pouches</a>.</p>',
		'seo_title' => 'What Are Nicotine Pouches? UK Guide | Kangoo Pouches',
        'seo_desc' => 'What are nicotine pouches? Learn what they contain, how adults use and dispose of them, how strengths compare and the current UK context.',
        'focus' => 'what are nicotine pouches',
        'eyebrow' => 'Nicotine pouch basics',
        'sources' => kangoo_seo_90_sources(),
    ),
    'velo-nicotine-pouches-guide-flavours-strengths-and-best-picks' => array(
        'title' => 'VELO Nicotine Pouches Guide: Flavours and Strengths',
        'excerpt' => 'Compare the current VELO nicotine pouch range by flavour, exact milligrams, pouch format and live stock, with a clear explanation of VELO strength dots.',
        'content' => '<p>VELO is one of the most searched nicotine pouch brands in the UK. Its range covers mint, berry, citrus and tropical profiles, but the exact products and strengths change over time. Use this guide for comparison and the <a href="/product-category/velo/">live VELO category</a> for current price and availability.</p><h2>How VELO strength dots work</h2><p>VELO uses dots as a quick scale within its own range. The dots should not be used as a direct comparison with another brand. Check the milligrams per pouch on the product name and packaging. Our separate <a href="/blog/velo-strength-dots-explained/">VELO dots guide</a> groups the common one-to-six-dot searches in one place.</p><h2>Current VELO flavour groups</h2><ul><li><strong>Mint and cooling:</strong> peppermint, spearmint and ice-led profiles.</li><li><strong>Berry and fruit:</strong> ruby berry, grape, watermelon and mixed fruit directions.</li><li><strong>Citrus and tropical:</strong> lemon, orange, mango and tropical blends.</li></ul><h2>How to choose a VELO pouch</h2><p>Start with milligrams per pouch, then consider flavour, pouch size and count. A familiar flavour does not make a higher strength suitable for a new user. If a product is out of stock, compare a genuinely similar strength and format rather than choosing by colour or dots alone.</p><h2>VELO prices and pack sizes</h2><p>Kangoo shows live single-tin and pack pricing on each product. Guides do not hardcode a promise that can become stale. Multi-buy value should be assessed by total price and unit price, while stock and any promotional limits remain visible in the basket.</p><h2>VELO reviews</h2><p>Customer ratings shown on product pages come from genuine submitted reviews. Flavour is subjective: cooling intensity, sweetness and pouch fit can feel different from person to person. The most useful review is one that identifies the exact product and explains fit or flavour without making health claims.</p><p>Compare <a href="/blog/velo-vs-nordic-spirit/">VELO vs Nordic Spirit</a>, <a href="/blog/zyn-vs-velo/">ZYN vs VELO</a>, or shop the <a href="/product-category/velo/">current VELO range</a>.</p>',
        'seo_title' => 'VELO Nicotine Pouches UK: Flavours & Strengths',
        'seo_desc' => 'Compare VELO nicotine pouches by flavour, exact strength, format and live stock. Includes VELO dot guidance and links to current UK products.',
        'focus' => 'VELO nicotine pouches',
        'eyebrow' => 'Brand guide',
    ),
    'zyn-nicotine-pouches-guide-strengths-flavours-and-best-picks' => array(
        'title' => 'ZYN Nicotine Pouches Guide: Flavours and Strengths',
        'excerpt' => 'Compare ZYN nicotine pouches by mini or regular format, flavour, exact strength and current UK stock without confusing commercial and educational searches.',
        'content' => '<p>ZYN nicotine pouches are available in mini and regular formats across mint, berry, citrus, coffee and fruit-led profiles. This guide explains the range; current price and stock remain on the <a href="/product-category/zyn/">ZYN category page</a>.</p><h2>ZYN formats</h2><p>Mini pouches are physically smaller, while regular products use a larger pouch format. Format does not determine nicotine strength, so check milligrams per pouch separately. Pouch count can also vary between products.</p><h2>ZYN flavours</h2><p>Popular stocked directions include Black Cherry, Red Fruits, Citrus and mint-led products, with coffee and other profiles appearing when available. Flavour descriptions are subjective and should not be used as a proxy for strength.</p><h2>ZYN strengths</h2><p>Lower-strength ZYN mini products and stronger regular products can appear in the same category. Compare the exact number in milligrams per pouch. Adults who are unsure should not assume the strongest option offers better value.</p><h2>How to compare ZYN prices</h2><p>Use live product cards for the current single-tin price, regular price, saving and pack options. Kangoo separates limited promotional tins from normal multi-buy pricing so a one-per-order promotion is not confused with the standard range.</p><h2>What is ZYN?</h2><p>ZYN is a brand of tobacco-free nicotine pouches. Readers looking for brand background rather than products can use the separate <a href="/blog/what-is-zyn-uk-guide-to-zyn-nicotine-pouches/">What is ZYN?</a> guide.</p><p>For direct comparisons, read <a href="/blog/zyn-vs-velo/">ZYN vs VELO</a> or <a href="/blog/zyn-vs-nordic-spirit/">ZYN vs Nordic Spirit</a>.</p>',
        'seo_title' => 'ZYN Nicotine Pouches UK: Flavours & Strengths',
        'seo_desc' => 'Compare ZYN nicotine pouches by flavour, mini or regular format, exact strength and current UK stock. See live products and pack prices.',
        'focus' => 'ZYN nicotine pouches',
        'eyebrow' => 'Brand guide',
    ),
    'velo-strength-dots-explained' => array(
        'title' => 'VELO Strength Dots Explained: 1 to 6 Dots',
        'excerpt' => 'VELO dots are an internal brand strength guide, not a universal nicotine scale. Compare one to six dots using the exact milligrams per pouch.',
        'content' => '<p><strong>VELO strength dots are a quick guide within the VELO range. They do not provide a universal conversion to milligrams and should not be compared directly with another brand\'s dots.</strong></p><nav aria-label="On this page"><strong>Jump to</strong><ul><li><a href="#meaning">What VELO dots mean</a></li><li><a href="#levels">One to six dots</a></li><li><a href="#compare">How to compare products</a></li></ul></nav><h2 id="meaning">What do VELO dots mean?</h2><p>The dot row helps shoppers see relative strength within the current VELO range. Product formulations and markets can change, so the exact milligrams per pouch printed on the tin and product page remain the authority.</p><h2 id="levels">VELO one, two, three, four, five and six dots</h2><table><thead><tr><th>Dot search</th><th>How to read it</th></tr></thead><tbody><tr><td>1 dot VELO</td><td>Lower relative position within the VELO range; check mg per pouch.</td></tr><tr><td>2 dot VELO</td><td>A step above one dot within the same range.</td></tr><tr><td>3 dot VELO</td><td>Mid-range relative marker; not a fixed mg conversion.</td></tr><tr><td>4 dot VELO</td><td>Higher relative marker; verify the exact product label.</td></tr><tr><td>5 dot VELO</td><td>Strong end of many VELO ranges; intended for experienced adult users.</td></tr><tr><td>6 dot VELO</td><td>Highest relative marker seen in some ranges; check exact mg and local formulation.</td></tr></tbody></table><h2 id="compare">The safest comparison method</h2><ol><li>Read milligrams per pouch, not only dots.</li><li>Confirm pouch count and format.</li><li>Compare the same named product because formulations can differ.</li><li>Do not move to a stronger product simply because it has a lower price.</li></ol><p>See the <a href="/product-category/velo/">current VELO products</a> or read the broader <a href="/blog/velo-nicotine-pouches-guide-flavours-strengths-and-best-picks/">VELO guide</a>.</p>',
        'seo_title' => 'VELO Strength Dots Explained: 1-6 Dot Guide',
        'seo_desc' => 'What do VELO strength dots mean? Compare VELO 1 to 6 dot searches correctly using exact nicotine milligrams per pouch.',
        'focus' => 'VELO strength dots',
        'eyebrow' => 'Strength guide',
    ),
    'nordic-spirit-reviews' => array(
        'title' => 'Nordic Spirit Review: Flavours, Strengths and UK Range',
        'excerpt' => 'A factual Nordic Spirit review of current flavours, strengths, pouch format and stock, with genuine customer ratings kept separate from editorial guidance.',
        'content' => '<p>Nordic Spirit is a tobacco-free nicotine pouch brand known for mint and berry-style flavour profiles. Kangoo currently carries a small range, so this page focuses on the products actually available rather than presenting discontinued or unstocked options as choices.</p><h2>Nordic Spirit flavours</h2><p>Current products can include spearmint and berry-led profiles. Flavour is subjective, and cooling or sweetness can feel different to each adult user. Product pages contain the exact description and ingredients supplied for that item.</p><h2>Strength and format</h2><p>Check milligrams per pouch rather than relying on colour, product name or another retailer\'s scale. The tin and live product page also confirm pouch count and format.</p><h2>Prices and availability</h2><p>Kangoo publishes current stock and price on the <a href="/product-category/nordic-spirit/">Nordic Spirit category</a>. A category can remain useful with a smaller range when it accurately shows available products and links to comparable choices.</p><h2>What do customer reviews mean?</h2><p>Only genuine submitted ratings should be used as customer review evidence. This editorial guide does not invent a score. Readers can compare exact product feedback on the relevant product page.</p><h2>Alternatives</h2><p>Adults comparing a wider mint or berry range can read <a href="/blog/velo-vs-nordic-spirit/">VELO vs Nordic Spirit</a> and <a href="/blog/zyn-vs-nordic-spirit/">ZYN vs Nordic Spirit</a>.</p>',
        'seo_title' => 'Nordic Spirit Review UK: Flavours & Strengths',
        'seo_desc' => 'Read a factual Nordic Spirit review covering current UK flavours, exact strengths, pouch format, live stock and genuine product ratings.',
        'focus' => 'Nordic Spirit reviews',
        'eyebrow' => 'Brand review',
    ),
);

foreach ($articles as $slug => $data) {
    kangoo_seo_90_update_article($slug, $data);
}

$comparison_specs = array(
    'velo-vs-nordic-spirit' => array('VELO vs Nordic Spirit: UK Pouch Comparison', 'VELO vs Nordic Spirit', 'VELO', 'Nordic Spirit', '/product-category/velo/', '/product-category/nordic-spirit/'),
    'zyn-vs-nordic-spirit' => array('ZYN vs Nordic Spirit: UK Pouch Comparison', 'ZYN vs Nordic Spirit', 'ZYN', 'Nordic Spirit', '/product-category/zyn/', '/product-category/nordic-spirit/'),
    'zyn-vs-velo' => array('ZYN vs VELO: UK Nicotine Pouch Comparison', 'ZYN vs VELO', 'ZYN', 'VELO', '/product-category/zyn/', '/product-category/velo/'),
);

foreach ($comparison_specs as $slug => $spec) {
    $content = '<p><strong>' . esc_html($spec[2]) . ' and ' . esc_html($spec[3]) . ' are tobacco-free nicotine pouch brands. The better choice depends on the exact strength, flavour, pouch format and live product availability rather than one brand being universally better.</strong></p><h2>Quick comparison</h2><table><thead><tr><th>Factor</th><th>' . esc_html($spec[2]) . '</th><th>' . esc_html($spec[3]) . '</th></tr></thead><tbody><tr><td>Strength</td><td>Compare mg per pouch on each live product.</td><td>Compare mg per pouch on each live product.</td></tr><tr><td>Flavour</td><td>Review the currently stocked profiles.</td><td>Review the currently stocked profiles.</td></tr><tr><td>Format</td><td>Check pouch size and count.</td><td>Check pouch size and count.</td></tr><tr><td>Price</td><td>Use live single and pack prices.</td><td>Use live single and pack prices.</td></tr></tbody></table><h2>Strength</h2><p>Brand dots and labels are not universal. Use milligrams per pouch to make a like-for-like comparison and avoid choosing a stronger product solely because it is discounted.</p><h2>Flavour and pouch fit</h2><p>Mint, berry, citrus and fruit names can hide meaningful differences in cooling, sweetness and pouch moisture. Format and fit are personal, so genuine product reviews are more useful when they identify the exact product.</p><h2>Price and stock</h2><p>Guide prices become stale quickly. Compare the live <a href="' . esc_url($spec[4]) . '">' . esc_html($spec[2]) . ' range</a> with the live <a href="' . esc_url($spec[5]) . '">' . esc_html($spec[3]) . ' range</a>. Out-of-stock products should not decide the comparison.</p><h2>Which should you choose?</h2><p>Choose the product whose exact nicotine amount, flavour and format fit your existing experience. Nicotine is addictive and every product is restricted to adults aged 18 and over.</p>';
    kangoo_seo_90_update_article($slug, array(
        'title' => $spec[0],
        'excerpt' => 'Compare ' . $spec[2] . ' and ' . $spec[3] . ' by exact nicotine strength, flavours, pouch format, live stock and current UK price.',
        'content' => $content,
        'seo_title' => $spec[1] . ' | UK Nicotine Pouch Comparison',
        'seo_desc' => 'Compare ' . $spec[2] . ' vs ' . $spec[3] . ' by exact strength, flavour, format, live stock and current UK price. Adults 18+.',
        'focus' => $spec[1],
        'eyebrow' => 'Brand comparison',
    ));
}

kangoo_seo_90_merge('zyn-pouches-guide-flavours-strengths-and-buying-tips', 'zyn-nicotine-pouches-guide-strengths-flavours-and-best-picks');
kangoo_seo_90_merge('velo-reviews', 'velo-nicotine-pouches-guide-flavours-strengths-and-best-picks');

$products = array(
    306 => array(
        'excerpt' => 'VELO Freezing Peppermint 10.9mg is a slim, tobacco-free nicotine pouch with a cooling peppermint flavour. Each product page shows the current pouch count, price and stock.',
        'content' => '<h2>VELO Freezing Peppermint 10.9mg</h2><p>This VELO pouch combines a cooling peppermint flavour with 10.9mg nicotine per pouch. It is positioned for experienced adult nicotine users; the strength should be checked before purchase rather than inferred from the flavour or dot scale.</p><h2>Product details</h2><ul><li>Brand: VELO</li><li>Flavour: Freezing Peppermint</li><li>Strength: 10.9mg per pouch</li><li>Format: check the live product specification</li><li>Tobacco-free nicotine pouch</li></ul><p>Use the live pack selector for current prices. Nicotine is addictive and this product is for adults aged 18 and over.</p>',
        'seo_title' => 'VELO Freezing Peppermint 10.9mg Pouches',
        'seo_desc' => 'Shop VELO Freezing Peppermint 10.9mg nicotine pouches. Check live UK stock, pouch count, single-tin price and pack options. Adults 18+.',
        'focus' => 'VELO Freezing Peppermint',
    ),
    358 => array(
        'excerpt' => 'VELO Ruby Berry 10mg is a tobacco-free nicotine pouch with a berry-led flavour and 10mg nicotine per pouch. Check the page for current availability.',
        'content' => '<h2>VELO Ruby Berry 10mg</h2><p>Ruby Berry uses a sweet berry-led flavour with 10mg nicotine per pouch. Availability can change, so the stock status on this page is authoritative.</p><h2>Product details</h2><ul><li>Brand: VELO</li><li>Flavour: Ruby Berry</li><li>Strength: 10mg per pouch</li><li>Tobacco-free nicotine pouch</li></ul><p>Compare the exact strength and format before ordering. Nicotine is addictive and this product is for adults aged 18 and over.</p>',
        'seo_title' => 'VELO Ruby Berry 10mg Nicotine Pouches',
        'seo_desc' => 'Compare VELO Ruby Berry 10mg nicotine pouches with live UK stock, current price, pouch count and pack details. Adults 18+.',
        'focus' => 'VELO Ruby Berry',
    ),
    321 => array(
        'excerpt' => 'VELO Crispy Peppermint 6mg is a slim, tobacco-free nicotine pouch with a fresh peppermint flavour and 6mg nicotine per pouch.',
        'content' => '<h2>VELO Crispy Peppermint 6mg</h2><p>Crispy Peppermint combines a fresh mint profile with 6mg nicotine per pouch. It sits below several stronger VELO products, but suitability still depends on the adult user\'s existing nicotine experience.</p><h2>Product details</h2><ul><li>Brand: VELO</li><li>Flavour: Crispy Peppermint</li><li>Strength: 6mg per pouch</li><li>Tobacco-free nicotine pouch</li></ul><p>Check the live selector for price and pack availability. Nicotine is addictive and this product is for adults aged 18 and over.</p>',
        'seo_title' => 'VELO Crispy Peppermint 6mg Nicotine Pouches',
        'seo_desc' => 'Shop VELO Crispy Peppermint 6mg nicotine pouches. Compare live UK stock, pouch count, single price and multi-buy packs. Adults 18+.',
        'focus' => 'VELO Crispy Peppermint',
    ),
    570 => array(
        'excerpt' => 'ZYN Black Cherry Mini 3mg is a mini tobacco-free nicotine pouch with a black cherry flavour and 3mg nicotine per pouch.',
        'content' => '<h2>ZYN Black Cherry Mini 3mg</h2><p>This mini-format ZYN pouch combines a black cherry flavour with 3mg nicotine per pouch. The smaller format and lower numerical strength distinguish it from stronger regular ZYN products.</p><h2>Product details</h2><ul><li>Brand: ZYN</li><li>Flavour: Black Cherry</li><li>Strength: 3mg per pouch</li><li>Format: Mini</li><li>Tobacco-free nicotine pouch</li></ul><p>Use the live pack selector for current pricing and availability. Nicotine is addictive and this product is for adults aged 18 and over.</p>',
        'seo_title' => 'ZYN Black Cherry Mini 3mg Nicotine Pouches',
        'seo_desc' => 'Shop ZYN Black Cherry Mini 3mg nicotine pouches. Check current UK stock, pouch count, live price and pack options. Adults 18+.',
        'focus' => 'ZYN Black Cherry Mini 3mg',
    ),
);

foreach ($products as $product_id => $data) {
    kangoo_seo_90_update_product($product_id, $data);
}

update_option('kangoo_seo_90_version', KANGOO_SEO_90_VERSION, false);
delete_option('kangoo_ai_discovery_version');
flush_rewrite_rules(false);
clean_post_cache(856);

WP_CLI::success('SEO 90-day growth migration applied: ' . KANGOO_SEO_90_VERSION);
