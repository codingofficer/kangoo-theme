<?php
/**
 * Template Name: Pouch Finder
 */

defined('ABSPATH') || exit;

get_header();

$finder_products = array();

if (function_exists('wc_get_products')) {
    $products = wc_get_products(array(
        'status'  => 'publish',
        'limit'   => 48,
        'orderby' => 'popularity',
        'return'  => 'objects',
    ));

    foreach ($products as $product) {
        if (!$product instanceof WC_Product || !$product->is_visible()) {
            continue;
        }

        $product_id = $product->get_id();
        $strength_mg = function_exists('get_field') ? get_field('strength_mg', $product_id) : '';
        $strength_label = $strength_mg ? strtoupper((string) $strength_mg) : $product->get_attribute('pa_strength');

        if ($strength_label && stripos($strength_label, 'mg') === false && preg_match('/\d/', $strength_label)) {
            $strength_label .= 'MG';
        }

        $finder_products[] = array(
            'id'       => $product_id,
            'name'     => get_the_title($product_id),
            'url'      => get_permalink($product_id),
            'image'    => get_the_post_thumbnail_url($product_id, 'woocommerce_thumbnail'),
            'price'    => function_exists('kangoo_get_product_price_html') ? kangoo_get_product_price_html($product) : $product->get_price_html(),
            'brand'    => $product->get_attribute('pa_brand'),
            'flavour'  => $product->get_attribute('pa_flavour'),
            'strength' => $strength_label,
            'mg'       => $strength_mg ? (float) preg_replace('/[^0-9.]/', '', (string) $strength_mg) : 0,
            'stock'    => $product->is_in_stock(),
        );
    }
}

$finder_nicotine_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$finder_trial_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', '99p-pouches', '/product-category/99p-pouches/') : home_url('/product-category/99p-pouches/');
$finder_compare_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-comparison.php', '/compare-pouches/') : home_url('/compare-pouches/');
$finder_strength_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-strength-ladder.php', '/strength-ladder/') : home_url('/strength-ladder/');
$finder_flavour_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-flavour-explorer.php', '/flavour-explorer/') : home_url('/flavour-explorer/');

$finder_seo_links = array(
    array('label' => __('Shop all nicotine pouches', 'kangoo'), 'url' => $finder_nicotine_url),
    array('label' => __('99p pouch trials', 'kangoo'), 'url' => $finder_trial_url),
    array('label' => __('Compare pouches', 'kangoo'), 'url' => $finder_compare_url),
    array('label' => __('Strength ladder', 'kangoo'), 'url' => $finder_strength_url),
    array('label' => __('Flavour explorer', 'kangoo'), 'url' => $finder_flavour_url),
);
?>

<main id="site-main" class="pouch-finder">
    <section class="pouch-finder__hero section">
        <div class="container">
            <div class="pouch-finder__hero-grid">
                <div class="pouch-finder__hero-copy">
                    <span class="eyebrow"><?php esc_html_e('Kangoo Pouch Finder', 'kangoo'); ?></span>
                    <h1><?php esc_html_e('Find your pouch match', 'kangoo'); ?></h1>
                    <p><?php esc_html_e('Answer a few quick questions and get a strength, flavour, and product direction that suits how you want your pouch to feel.', 'kangoo'); ?></p>
                    <div class="pouch-finder__hero-actions">
                        <a class="btn btn--primary" href="#pouch-finder-tool"><?php esc_html_e('Start finder', 'kangoo'); ?></a>
                        <a class="btn btn--secondary" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>"><?php esc_html_e('Browse all pouches', 'kangoo'); ?></a>
                    </div>
                </div>

                <div class="pouch-finder__summary" aria-label="<?php esc_attr_e('Finder benefits', 'kangoo'); ?>">
                    <div>
                        <span><?php esc_html_e('Strength', 'kangoo'); ?></span>
                        <strong><?php esc_html_e('Light to extra strong', 'kangoo'); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e('Flavour', 'kangoo'); ?></span>
                        <strong><?php esc_html_e('Mint, fruit, sweet, citrus', 'kangoo'); ?></strong>
                    </div>
                    <div>
                        <span><?php esc_html_e('Result', 'kangoo'); ?></span>
                        <strong><?php esc_html_e('Best match plus alternatives', 'kangoo'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="kangoo-seo-strip section" aria-label="<?php esc_attr_e('Pouch finder buying links', 'kangoo'); ?>">
        <div class="container">
            <div class="kangoo-seo-strip__inner">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Find, compare, then shop', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Find nicotine pouches by strength, flavour and price', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Use the finder for a quick recommendation, then compare live products, 79p trials and brand pages before choosing your pouch.', 'kangoo'); ?></p>
                </div>
                <div class="kangoo-seo-strip__links">
                    <?php foreach ($finder_seo_links as $finder_seo_link) : ?>
                        <a href="<?php echo esc_url($finder_seo_link['url']); ?>"><?php echo esc_html($finder_seo_link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section id="pouch-finder-tool" class="pouch-finder__tool section">
        <div class="container">
            <div class="pouch-finder__shell" data-pouch-finder>
                <aside class="pouch-finder__progress" aria-label="<?php esc_attr_e('Finder progress', 'kangoo'); ?>">
                    <span class="pouch-finder__step-count" data-step-count><?php esc_html_e('Step 1 of 5', 'kangoo'); ?></span>
                    <div class="pouch-finder__bar"><span data-progress-bar></span></div>
                    <h2 data-step-title><?php esc_html_e('How experienced are you?', 'kangoo'); ?></h2>
                    <p data-step-helper><?php esc_html_e('This helps us keep the strength recommendation sensible.', 'kangoo'); ?></p>
                </aside>

                <form class="pouch-finder__form" data-finder-form>
                    <div class="pouch-finder__step is-active" data-step="0">
                        <label class="finder-option">
                            <input type="radio" name="experience" value="new">
                            <span><?php esc_html_e('New to nicotine pouches', 'kangoo'); ?></span>
                            <small><?php esc_html_e('I want a smoother place to start.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="experience" value="some">
                            <span><?php esc_html_e('I have used them a few times', 'kangoo'); ?></span>
                            <small><?php esc_html_e('I know the feel, but still want guidance.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="experience" value="regular">
                            <span><?php esc_html_e('I use pouches regularly', 'kangoo'); ?></span>
                            <small><?php esc_html_e('I am comfortable with a noticeable kick.', 'kangoo'); ?></small>
                        </label>
                    </div>

                    <div class="pouch-finder__step" data-step="1">
                        <label class="finder-option">
                            <input type="radio" name="strength" value="light">
                            <span><?php esc_html_e('Light and smooth', 'kangoo'); ?></span>
                            <small><?php esc_html_e('Gentle feel, lower intensity.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="strength" value="medium">
                            <span><?php esc_html_e('Balanced', 'kangoo'); ?></span>
                            <small><?php esc_html_e('A clear effect without going too heavy.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="strength" value="strong">
                            <span><?php esc_html_e('Strong', 'kangoo'); ?></span>
                            <small><?php esc_html_e('For a more powerful pouch feel.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="strength" value="extra">
                            <span><?php esc_html_e('Extra strong', 'kangoo'); ?></span>
                            <small><?php esc_html_e('For experienced users only.', 'kangoo'); ?></small>
                        </label>
                    </div>

                    <div class="pouch-finder__step" data-step="2">
                        <label class="finder-option finder-option--compact">
                            <input type="radio" name="flavour" value="mint">
                            <span><?php esc_html_e('Fresh mint', 'kangoo'); ?></span>
                        </label>
                        <label class="finder-option finder-option--compact">
                            <input type="radio" name="flavour" value="fruit">
                            <span><?php esc_html_e('Fruity', 'kangoo'); ?></span>
                        </label>
                        <label class="finder-option finder-option--compact">
                            <input type="radio" name="flavour" value="citrus">
                            <span><?php esc_html_e('Citrus', 'kangoo'); ?></span>
                        </label>
                        <label class="finder-option finder-option--compact">
                            <input type="radio" name="flavour" value="sweet">
                            <span><?php esc_html_e('Sweet or dessert', 'kangoo'); ?></span>
                        </label>
                        <label class="finder-option finder-option--compact">
                            <input type="radio" name="flavour" value="any">
                            <span><?php esc_html_e('Surprise me', 'kangoo'); ?></span>
                        </label>
                    </div>

                    <div class="pouch-finder__step" data-step="3">
                        <label class="finder-option">
                            <input type="radio" name="use_case" value="daily">
                            <span><?php esc_html_e('Daily rotation', 'kangoo'); ?></span>
                            <small><?php esc_html_e('Reliable, repeatable, easy to come back to.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="use_case" value="focus">
                            <span><?php esc_html_e('Work or study', 'kangoo'); ?></span>
                            <small><?php esc_html_e('Clean flavours and controlled strength.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="use_case" value="after-meal">
                            <span><?php esc_html_e('After meals', 'kangoo'); ?></span>
                            <small><?php esc_html_e('Fresh, crisp, and palate-clearing.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="use_case" value="night-out">
                            <span><?php esc_html_e('Social or night out', 'kangoo'); ?></span>
                            <small><?php esc_html_e('Bolder flavour and stronger presence.', 'kangoo'); ?></small>
                        </label>
                    </div>

                    <div class="pouch-finder__step" data-step="4">
                        <label class="finder-option">
                            <input type="radio" name="sensitivity" value="smooth">
                            <span><?php esc_html_e('Keep it smooth', 'kangoo'); ?></span>
                            <small><?php esc_html_e('I prefer comfort over intensity.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="sensitivity" value="noticeable">
                            <span><?php esc_html_e('Noticeable kick', 'kangoo'); ?></span>
                            <small><?php esc_html_e('I want it to feel present.', 'kangoo'); ?></small>
                        </label>
                        <label class="finder-option">
                            <input type="radio" name="sensitivity" value="maximum">
                            <span><?php esc_html_e('Maximum impact', 'kangoo'); ?></span>
                            <small><?php esc_html_e('I am experienced and want high intensity.', 'kangoo'); ?></small>
                        </label>
                    </div>

                    <div class="pouch-finder__actions">
                        <button type="button" class="btn btn--secondary" data-prev-step><?php esc_html_e('Back', 'kangoo'); ?></button>
                        <button type="button" class="btn btn--primary" data-next-step><?php esc_html_e('Next', 'kangoo'); ?></button>
                    </div>
                </form>
            </div>

            <section class="pouch-finder__result" data-finder-result hidden>
                <div class="pouch-finder__result-head">
                    <span class="eyebrow"><?php esc_html_e('Your Kangoo match', 'kangoo'); ?></span>
                    <h2 data-result-title><?php esc_html_e('Balanced fresh match', 'kangoo'); ?></h2>
                    <p data-result-copy></p>
                    <div class="pouch-finder__chips" data-result-chips></div>
                </div>

                <div class="pouch-finder__products" data-result-products></div>

                <div class="pouch-finder__notice">
                    <?php esc_html_e('Nicotine is addictive. This finder is for adults who already use nicotine products and is not medical advice.', 'kangoo'); ?>
                </div>

                <button type="button" class="btn btn--secondary" data-reset-finder><?php esc_html_e('Retake finder', 'kangoo'); ?></button>
            </section>

            <script type="application/json" data-finder-products>
                <?php echo wp_json_encode($finder_products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            </script>
        </div>
    </section>
</main>

<?php get_footer(); ?>
