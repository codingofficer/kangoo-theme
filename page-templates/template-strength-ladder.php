<?php
/**
 * Template Name: Strength Ladder
 */

defined('ABSPATH') || exit;

get_header();

$ladder_levels = array(
    'light' => array(
        'label' => __('Light', 'kangoo'),
        'range' => __('Up to 4mg', 'kangoo'),
        'summary' => __('A smoother place to start if you want lower intensity or a more comfortable daily pouch.', 'kangoo'),
        'best_for' => __('Newer users, lighter moments, and anyone prioritising comfort.', 'kangoo'),
    ),
    'balanced' => array(
        'label' => __('Balanced', 'kangoo'),
        'range' => __('5mg to 9mg', 'kangoo'),
        'summary' => __('A clear step up with a noticeable feel while staying suitable for regular rotation.', 'kangoo'),
        'best_for' => __('Everyday use, work breaks, and shoppers who want control without going too heavy.', 'kangoo'),
    ),
    'strong' => array(
        'label' => __('Strong', 'kangoo'),
        'range' => __('10mg to 14mg', 'kangoo'),
        'summary' => __('A stronger pouch experience with a more obvious kick and longer-lasting presence.', 'kangoo'),
        'best_for' => __('Experienced users who already know they prefer a stronger pouch.', 'kangoo'),
    ),
    'extra' => array(
        'label' => __('Extra Strong', 'kangoo'),
        'range' => __('15mg+', 'kangoo'),
        'summary' => __('The highest intensity band, best kept for experienced nicotine pouch users.', 'kangoo'),
        'best_for' => __('Experienced users only, especially those choosing maximum impact.', 'kangoo'),
    ),
);

foreach ($ladder_levels as $level_key => $level) {
    $ladder_levels[$level_key]['products'] = array();
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

$ladder_nicotine_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$ladder_compare_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-comparison.php', '/compare-pouches/') : home_url('/compare-pouches/');
$ladder_seo_links = array(
    array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
    array('label' => __('Extra strong nicotine pouches', 'kangoo'), 'url' => home_url('/extra-strong-strength-nicotine-pouches/')),
    array('label' => __('Shop all nicotine pouches', 'kangoo'), 'url' => $ladder_nicotine_url),
    array('label' => __('Pouch finder', 'kangoo'), 'url' => $finder_url),
    array('label' => __('Compare pouches', 'kangoo'), 'url' => $ladder_compare_url),
);

if (function_exists('wc_get_products')) {
    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => 100,
        'orderby' => 'popularity',
        'return'  => 'objects',
    ));

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible()) {
            continue;
        }

        $product_id = $product->get_id();
        $strength_mg = function_exists('get_field') ? get_field('strength_mg', $product_id) : '';
        $strength_text = $strength_mg ? (string) $strength_mg : $product->get_attribute('pa_strength');
        $strength_number = $strength_text ? (float) preg_replace('/[^0-9.]/', '', strtolower($strength_text)) : 0;
        $strength_words = strtolower((string) $strength_text);

        if ($strength_number >= 15 || strpos($strength_words, 'extra') !== false) {
            $bucket = 'extra';
        } elseif ($strength_number >= 10 || strpos($strength_words, 'strong') !== false) {
            $bucket = 'strong';
        } elseif ($strength_number >= 5 || strpos($strength_words, 'medium') !== false || strpos($strength_words, 'balanced') !== false) {
            $bucket = 'balanced';
        } else {
            $bucket = 'light';
        }

        if (count($ladder_levels[$bucket]['products']) >= 4) {
            continue;
        }

        $strength_label = $strength_text ? strtoupper((string) $strength_text) : '';

        if ($strength_label && stripos($strength_label, 'mg') === false && preg_match('/\d/', $strength_label)) {
            $strength_label .= 'MG';
        }

        $ladder_levels[$bucket]['products'][] = array(
            'id'       => $product_id,
            'name'     => get_the_title($product_id),
            'url'      => get_permalink($product_id),
            'image'    => get_the_post_thumbnail_url($product_id, 'woocommerce_thumbnail'),
            'price'    => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
            'flavour'  => $product->get_attribute('pa_flavour'),
            'strength' => $strength_label,
            'stock'    => $product->is_in_stock(),
        );
    }
}
?>

<main id="site-main" class="strength-ladder">
    <section class="strength-ladder__hero section">
        <div class="container">
            <div class="strength-ladder__hero-grid">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Strength Ladder', 'kangoo'); ?></span>
                    <h1><?php esc_html_e('Choose your pouch strength with confidence', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Use the ladder to understand how Kangoo groups nicotine pouch strengths, then jump straight into products that match the level you want.', 'kangoo'); ?></p>
                </div>

                <div class="strength-ladder__scale" aria-label="<?php esc_attr_e('Strength scale', 'kangoo'); ?>">
                    <?php foreach ($ladder_levels as $level_key => $level) : ?>
                        <a href="#strength-<?php echo esc_attr($level_key); ?>">
                            <span><?php echo esc_html($level['range']); ?></span>
                            <strong><?php echo esc_html($level['label']); ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="kangoo-seo-strip section" aria-label="<?php esc_attr_e('Strength page buying links', 'kangoo'); ?>">
        <div class="container">
            <div class="kangoo-seo-strip__inner">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Strength guide', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Popular nicotine pouch strength pages', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Use the ladder to understand light, balanced, strong and extra strong pouches, then move into focused landing pages for stronger products or all nicotine pouches.', 'kangoo'); ?></p>
                </div>
                <div class="kangoo-seo-strip__links">
                    <?php foreach ($ladder_seo_links as $ladder_seo_link) : ?>
                        <a href="<?php echo esc_url($ladder_seo_link['url']); ?>"><?php echo esc_html($ladder_seo_link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="strength-ladder__levels section">
        <div class="container">
            <div class="strength-ladder__stack">
                <?php foreach ($ladder_levels as $level_key => $level) : ?>
                    <article id="strength-<?php echo esc_attr($level_key); ?>" class="strength-level strength-level--<?php echo esc_attr($level_key); ?>">
                        <div class="strength-level__copy">
                            <span><?php echo esc_html($level['range']); ?></span>
                            <h2><?php echo esc_html($level['label']); ?></h2>
                            <p><?php echo esc_html($level['summary']); ?></p>
                            <div class="strength-level__best">
                                <strong><?php esc_html_e('Best for', 'kangoo'); ?></strong>
                                <p><?php echo esc_html($level['best_for']); ?></p>
                            </div>
                        </div>

                        <div class="strength-level__products">
                            <?php if (!empty($level['products'])) : ?>
                                <?php foreach ($level['products'] as $product) : ?>
                                    <a class="strength-product" href="<?php echo esc_url($product['url']); ?>">
                                        <span class="strength-product__image">
                                            <?php if ($product['image']) : ?>
                                                <img src="<?php echo esc_url($product['image']); ?>" alt="">
                                            <?php endif; ?>
                                        </span>
                                        <span class="strength-product__body">
                                            <strong><?php echo esc_html($product['name']); ?></strong>
                                            <span><?php echo esc_html(trim($product['strength'] . ' - ' . $product['flavour'], ' -')); ?></span>
                                            <span class="strength-product__price"><?php echo wp_kses_post($product['price']); ?></span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="strength-level__empty">
                                    <?php esc_html_e('No products are currently assigned to this strength band.', 'kangoo'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="strength-ladder__cta">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Still unsure?', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Let Kangoo match you to a pouch', 'kangoo'); ?></h2>
                </div>
                <a class="btn btn--primary" href="<?php echo esc_url($finder_url); ?>">
                    <?php esc_html_e('Use pouch finder', 'kangoo'); ?>
                </a>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
