<?php
/**
 * Template Name: Flavour Explorer
 */

defined('ABSPATH') || exit;

get_header();

$flavour_families = array(
    'mint' => array(
        'label' => __('Mint & Ice', 'kangoo'),
        'summary' => __('Fresh, clean and cooling flavours for a crisp pouch experience.', 'kangoo'),
        'best_for' => __('Daily use, work breaks and after meals.', 'kangoo'),
        'terms' => array('mint', 'menthol', 'ice', 'cool', 'freeze', 'spearmint', 'peppermint'),
    ),
    'berry' => array(
        'label' => __('Berry', 'kangoo'),
        'summary' => __('Sweet, fruity profiles with a softer edge than mint-heavy pouches.', 'kangoo'),
        'best_for' => __('Flavour explorers and shoppers who prefer a fruit-led pouch.', 'kangoo'),
        'terms' => array('berry', 'blueberry', 'strawberry', 'raspberry', 'black cherry', 'cherry'),
    ),
    'citrus' => array(
        'label' => __('Citrus', 'kangoo'),
        'summary' => __('Sharp, bright and refreshing flavours with a cleaner finish.', 'kangoo'),
        'best_for' => __('After meals, daytime use and anyone who wants a crisp alternative to mint.', 'kangoo'),
        'terms' => array('citrus', 'lemon', 'lime', 'orange', 'grapefruit'),
    ),
    'tropical' => array(
        'label' => __('Tropical', 'kangoo'),
        'summary' => __('Juicier fruit flavours with a warmer, sweeter profile.', 'kangoo'),
        'best_for' => __('Pick n Mix bundles, weekends and trying something less traditional.', 'kangoo'),
        'terms' => array('tropical', 'mango', 'pineapple', 'melon', 'peach', 'passion'),
    ),
    'sweet' => array(
        'label' => __('Sweet', 'kangoo'),
        'summary' => __('Softer sweet-shop inspired profiles for a more playful pouch rotation.', 'kangoo'),
        'best_for' => __('Switching up your usual order and building a mixed bundle.', 'kangoo'),
        'terms' => array('cola', 'candy', 'vanilla', 'caramel', 'coffee', 'dessert', 'sweet'),
    ),
);

foreach ($flavour_families as $family_key => $family) {
    $flavour_families[$family_key]['products'] = array();
    $flavour_families[$family_key]['term_url'] = '';
}

foreach ($flavour_families as $family_key => $family) {
    if (!taxonomy_exists('pa_flavour')) {
        continue;
    }

    foreach ($family['terms'] as $term_keyword) {
        $terms = get_terms(array(
            'taxonomy'   => 'pa_flavour',
            'hide_empty' => true,
            'search'     => $term_keyword,
            'number'     => 1,
        ));

        if (!empty($terms) && !is_wp_error($terms)) {
            $term_link = get_term_link($terms[0]);
            $flavour_families[$family_key]['term_url'] = !is_wp_error($term_link) ? $term_link : '';
            break;
        }
    }
}

if (function_exists('wc_get_products')) {
    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => 120,
        'orderby' => 'popularity',
        'return'  => 'objects',
    ));

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible()) {
            continue;
        }

        $product_id = $product->get_id();
        $flavour = $product->get_attribute('pa_flavour');
        $haystack = strtolower(get_the_title($product_id) . ' ' . $flavour);
        $matched_family = '';

        foreach ($flavour_families as $family_key => $family) {
            foreach ($family['terms'] as $term_keyword) {
                if (strpos($haystack, strtolower($term_keyword)) !== false) {
                    $matched_family = $family_key;
                    break 2;
                }
            }
        }

        if (!$matched_family || count($flavour_families[$matched_family]['products']) >= 4) {
            continue;
        }

        $strength_mg = function_exists('get_field') ? get_field('strength_mg', $product_id) : '';
        $strength_label = $strength_mg ? strtoupper((string) $strength_mg) : $product->get_attribute('pa_strength');

        if ($strength_label && stripos($strength_label, 'mg') === false && preg_match('/\d/', $strength_label)) {
            $strength_label .= 'MG';
        }

        $flavour_families[$matched_family]['products'][] = array(
            'id'       => $product_id,
            'name'     => get_the_title($product_id),
            'url'      => get_permalink($product_id),
            'image'    => get_the_post_thumbnail_url($product_id, 'woocommerce_thumbnail'),
            'price'    => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
            'flavour'  => $flavour,
            'strength' => $strength_label,
        );
    }
}

$finder_url = home_url('/pouch-finder/');
$finder_page = get_posts(array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-templates/template-pouch-finder.php',
));

if (!empty($finder_page)) {
    $finder_url = get_permalink($finder_page[0]);
}

$flavour_nicotine_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$flavour_compare_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-comparison.php', '/compare-pouches/') : home_url('/compare-pouches/');
$flavour_seo_links = array(
    array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
    array('label' => __('Berry nicotine pouches', 'kangoo'), 'url' => home_url('/berry-nicotine-pouches/')),
    array('label' => __('Shop all nicotine pouches', 'kangoo'), 'url' => $flavour_nicotine_url),
    array('label' => __('Pouch finder', 'kangoo'), 'url' => $finder_url),
    array('label' => __('Compare pouches', 'kangoo'), 'url' => $flavour_compare_url),
);
?>

<main id="site-main" class="flavour-explorer">
    <section class="flavour-explorer__hero section">
        <div class="container">
            <div class="flavour-explorer__hero-grid">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Flavour Explorer', 'kangoo'); ?></span>
                    <h1><?php esc_html_e('Explore nicotine pouch flavours', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Browse Kangoo pouches by taste profile, from crisp mint and citrus to berry, tropical and sweet flavours.', 'kangoo'); ?></p>
                </div>

                <div class="flavour-explorer__nav" aria-label="<?php esc_attr_e('Flavour families', 'kangoo'); ?>">
                    <?php foreach ($flavour_families as $family_key => $family) : ?>
                        <a href="#flavour-<?php echo esc_attr($family_key); ?>"><?php echo esc_html($family['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="kangoo-seo-strip section" aria-label="<?php esc_attr_e('Flavour page buying links', 'kangoo'); ?>">
        <div class="container">
            <div class="kangoo-seo-strip__inner">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Flavour guide', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Popular nicotine pouch flavour pages', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Use the flavour explorer to compare mint, berry, citrus, tropical and sweet pouch families, then move into focused landing pages or the full nicotine pouch range.', 'kangoo'); ?></p>
                </div>
                <div class="kangoo-seo-strip__links">
                    <?php foreach ($flavour_seo_links as $flavour_seo_link) : ?>
                        <a href="<?php echo esc_url($flavour_seo_link['url']); ?>"><?php echo esc_html($flavour_seo_link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="flavour-explorer__families section">
        <div class="container">
            <div class="flavour-explorer__grid">
                <?php foreach ($flavour_families as $family_key => $family) : ?>
                    <article id="flavour-<?php echo esc_attr($family_key); ?>" class="flavour-family flavour-family--<?php echo esc_attr($family_key); ?>">
                        <div class="flavour-family__copy">
                            <span><?php echo esc_html($family['label']); ?></span>
                            <h2><?php echo esc_html($family['label']); ?></h2>
                            <p><?php echo esc_html($family['summary']); ?></p>
                            <div class="flavour-family__best">
                                <strong><?php esc_html_e('Best for', 'kangoo'); ?></strong>
                                <p><?php echo esc_html($family['best_for']); ?></p>
                            </div>
                            <?php if ($family['term_url']) : ?>
                                <a class="btn btn--secondary" href="<?php echo esc_url($family['term_url']); ?>">
                                    <?php esc_html_e('Shop this flavour', 'kangoo'); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="flavour-family__products">
                            <?php if (!empty($family['products'])) : ?>
                                <?php foreach ($family['products'] as $product) : ?>
                                    <a class="flavour-product" href="<?php echo esc_url($product['url']); ?>">
                                        <span class="flavour-product__image">
                                            <?php if ($product['image']) : ?>
                                                <img src="<?php echo esc_url($product['image']); ?>" alt="">
                                            <?php endif; ?>
                                        </span>
                                        <span class="flavour-product__body">
                                            <strong><?php echo esc_html($product['name']); ?></strong>
                                            <span><?php echo esc_html(trim($product['strength'] . ' - ' . $product['flavour'], ' -')); ?></span>
                                            <span class="flavour-product__price"><?php echo wp_kses_post($product['price']); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="flavour-family__empty">
                                    <?php esc_html_e('No matching pouches are currently assigned to this flavour family.', 'kangoo'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="flavour-explorer__cta">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Need a recommendation?', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Let Kangoo match your flavour and strength', 'kangoo'); ?></h2>
                </div>
                <a class="btn btn--primary" href="<?php echo esc_url($finder_url); ?>">
                    <?php esc_html_e('Use pouch finder', 'kangoo'); ?>
                </a>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
