<?php
defined('ABSPATH') || exit;

$context = function_exists('kangoo_get_archive_seo_context') ? kangoo_get_archive_seo_context() : array();

if (empty($context['type']) || empty($context['term']) || !($context['term'] instanceof WP_Term)) {
    return;
}

$term = $context['term'];
$context_type = $context['type'];
$nicotine_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$trial_url = function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', '99p-pouches', '/product-category/99p-pouches/') : home_url('/product-category/99p-pouches/');
$finder_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-finder.php', '/pouch-finder/') : home_url('/pouch-finder/');
$compare_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-pouch-comparison.php', '/compare-pouches/') : home_url('/compare-pouches/');
$strength_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-strength-ladder.php', '/strength-ladder/') : home_url('/strength-ladder/');
$flavour_url = function_exists('kangoo_get_page_url_by_template') ? kangoo_get_page_url_by_template('page-templates/template-flavour-explorer.php', '/flavour-explorer/') : home_url('/flavour-explorer/');

$target_links = array(
    array('label' => __('99p nicotine pouches now from 79p', 'kangoo'), 'url' => $trial_url),
    array('label' => __('Compare nicotine pouches', 'kangoo'), 'url' => $compare_url),
    array('label' => __('Pouch finder', 'kangoo'), 'url' => $finder_url),
    array('label' => __('Strength ladder', 'kangoo'), 'url' => $strength_url),
    array('label' => __('Flavour explorer', 'kangoo'), 'url' => $flavour_url),
);

$brand_links = function_exists('kangoo_get_brand_authority_links') ? kangoo_get_brand_authority_links() : array(
    array('label' => __('ZYN nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'zyn', '/product-category/zyn/') : home_url('/product-category/zyn/')),
    array('label' => __('VELO nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'velo', '/product-category/velo/') : home_url('/product-category/velo/')),
    array('label' => __('PABLO nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'pablo', '/product-category/pablo/') : home_url('/product-category/pablo/')),
    array('label' => __('KILLA nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'killa', '/product-category/killa/') : home_url('/product-category/killa/')),
);

$strength_links = array(
    array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
    array('label' => __('Extra strong nicotine pouches', 'kangoo'), 'url' => home_url('/extra-strong-strength-nicotine-pouches/')),
);

$flavour_links = array(
    array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
    array('label' => __('Berry nicotine pouches', 'kangoo'), 'url' => home_url('/berry-nicotine-pouches/')),
);

$render_link_chips = static function ($links) {
    if (empty($links)) {
        return;
    }
    ?>
    <div class="seo-link-chips">
        <?php foreach ($links as $link) : ?>
            <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
        <?php endforeach; ?>
    </div>
    <?php
};

$render_product_rail = static function ($products, $title, $copy = '') {
    if (empty($products)) {
        return;
    }
    ?>
    <section class="seo-module seo-module--rail" aria-label="<?php echo esc_attr($title); ?>">
        <div class="seo-module__head">
            <h2><?php echo esc_html($title); ?></h2>
            <?php if ($copy) : ?>
                <p><?php echo esc_html($copy); ?></p>
            <?php endif; ?>
        </div>
        <div class="seo-product-rail">
            <?php foreach ($products as $product) : ?>
                <?php
                $summary = function_exists('kangoo_get_product_seo_summary') ? kangoo_get_product_seo_summary($product) : array();

                if (empty($summary)) {
                    continue;
                }
                ?>
                <article class="seo-product-card">
                    <a class="seo-product-card__image" href="<?php echo esc_url($summary['url']); ?>" aria-label="<?php echo esc_attr($summary['name']); ?>">
                        <?php if (!empty($summary['image'])) : ?>
                            <img src="<?php echo esc_url($summary['image']); ?>" alt="">
                        <?php endif; ?>
                    </a>
                    <div class="seo-product-card__body">
                        <div class="seo-product-card__meta">
                            <?php if (!empty($summary['brand'])) : ?>
                                <span><?php echo esc_html($summary['brand']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($summary['strength'])) : ?>
                                <span><?php echo esc_html($summary['strength']); ?></span>
                            <?php endif; ?>
                        </div>
                        <h3><a href="<?php echo esc_url($summary['url']); ?>"><?php echo esc_html($summary['name']); ?></a></h3>
                        <dl>
                            <?php if (!empty($summary['flavour'])) : ?>
                                <div>
                                    <dt><?php esc_html_e('Flavour', 'kangoo'); ?></dt>
                                    <dd><?php echo esc_html($summary['flavour']); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($summary['pack_value'])) : ?>
                                <div>
                                    <dt><?php echo esc_html($summary['pack_label']); ?></dt>
                                    <dd><?php echo esc_html($summary['pack_value']); ?></dd>
                                </div>
                            <?php endif; ?>
                            <div>
                                <dt><?php esc_html_e('Best for', 'kangoo'); ?></dt>
                                <dd><?php echo esc_html($summary['best_for']); ?></dd>
                            </div>
                        </dl>
                        <div class="seo-product-card__footer">
                            <span class="seo-product-card__price"><?php echo wp_kses_post($summary['price_html']); ?></span>
                            <a class="btn btn--secondary" href="<?php echo esc_url($summary['url']); ?>"><?php esc_html_e('View pouch', 'kangoo'); ?></a>
                        </div>
                        <?php if (!empty($summary['stock_note'])) : ?>
                            <p class="seo-product-card__stock"><?php echo esc_html($summary['stock_note']); ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
};

$priority_links = array(
    array('label' => __('Nicotine pouches UK', 'kangoo'), 'url' => $nicotine_url),
    array('label' => __('Price checked nicotine pouches', 'kangoo'), 'url' => $nicotine_url),
    array('label' => __('99p nicotine pouches now from 79p', 'kangoo'), 'url' => $trial_url),
);

if ($context_type === 'nicotine') {
    $priority_links = array_merge(
        $priority_links,
        array_slice($brand_links, 0, 4),
        array(
            array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
            array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
        )
    );
} elseif ($context_type === 'trial') {
    $priority_links = array_merge(
        $priority_links,
        array(
            array('label' => __('Cheap nicotine pouches UK', 'kangoo'), 'url' => $trial_url),
            array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
            array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
        )
    );
} elseif ($context_type === 'brand') {
    $priority_links = array_merge(
        $priority_links,
        array(
            array('label' => __('Compare this brand', 'kangoo'), 'url' => $compare_url),
            array('label' => __('Mint nicotine pouches', 'kangoo'), 'url' => home_url('/mint-nicotine-pouches/')),
            array('label' => __('Strong nicotine pouches', 'kangoo'), 'url' => home_url('/strong-strength-nicotine-pouches/')),
        )
    );
} elseif ($context_type === 'flavour') {
    $priority_links = array_merge(
        $priority_links,
        array(
            array('label' => __('Flavour explorer', 'kangoo'), 'url' => $flavour_url),
            array('label' => __('ZYN nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'zyn', '/product-category/zyn/') : home_url('/product-category/zyn/')),
            array('label' => __('VELO nicotine pouches', 'kangoo'), 'url' => function_exists('kangoo_get_term_url_by_slug') ? kangoo_get_term_url_by_slug('product_cat', 'velo', '/product-category/velo/') : home_url('/product-category/velo/')),
        )
    );
} elseif ($context_type === 'strength') {
    $priority_links = array_merge(
        $priority_links,
        array(
            array('label' => __('Strength ladder', 'kangoo'), 'url' => $strength_url),
            array('label' => __('Extra strong nicotine pouches', 'kangoo'), 'url' => home_url('/extra-strong-strength-nicotine-pouches/')),
            array('label' => __('Pouch finder', 'kangoo'), 'url' => $finder_url),
        )
    );
}

$priority_links = array_values(array_unique($priority_links, SORT_REGULAR));

$trial_products = function_exists('kangoo_get_trial_products') ? kangoo_get_trial_products(6) : array();
?>

<section class="section seo-modules seo-modules--<?php echo esc_attr($context_type); ?>">
    <div class="container">
        <?php if ($context_type === 'nicotine') : ?>
            <?php
            $best_sellers = function_exists('kangoo_get_best_seller_products') ? kangoo_get_best_seller_products(5) : array();
            $comparison_rows = function_exists('kangoo_get_retailer_value_comparison_rows') ? kangoo_get_retailer_value_comparison_rows() : array();
            ?>
            <section class="seo-proof-grid" aria-label="<?php esc_attr_e('Kangoo Pouches nicotine pouch buying proof', 'kangoo'); ?>">
                <article>
                    <strong><?php esc_html_e('99p trials', 'kangoo'); ?></strong>
                    <span><?php esc_html_e('Selected trial pouches while stock lasts.', 'kangoo'); ?></span>
                </article>
                <article>
                    <strong><?php esc_html_e('Multi-buy packs', 'kangoo'); ?></strong>
                    <span><?php esc_html_e('3, 5 and 10-pack options on selected products.', 'kangoo'); ?></span>
                </article>
                <article>
                    <strong><?php esc_html_e('Free delivery over £14.95', 'kangoo'); ?></strong>
                    <span><?php esc_html_e('Discreet UK delivery for online pouch orders.', 'kangoo'); ?></span>
                </article>
                <article>
                    <strong><?php esc_html_e('ZYN, VELO, KILLA, PABLO', 'kangoo'); ?></strong>
                    <span><?php esc_html_e('Popular brands plus rotating sample packs.', 'kangoo'); ?></span>
                </article>
            </section>

            <?php if (!empty($best_sellers)) : ?>
                <section class="seo-module seo-module--table">
                    <div class="seo-module__head">
                        <h2><?php esc_html_e('Popular in-stock nicotine pouches', 'kangoo'); ?></h2>
                        <p><?php esc_html_e('A quick buying view of currently available pouches, with strength, flavour, price and pack value pulled from live Kangoo Pouches product data.', 'kangoo'); ?></p>
                    </div>
                    <div class="seo-scroll-table">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Product', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Brand', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Strength', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Flavour', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Price', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Pack value', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Best for', 'kangoo'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($best_sellers as $product) : ?>
                                    <?php $summary = function_exists('kangoo_get_product_seo_summary') ? kangoo_get_product_seo_summary($product) : array(); ?>
                                    <?php if (!empty($summary)) : ?>
                                        <tr>
                                            <th scope="row"><a href="<?php echo esc_url($summary['url']); ?>"><?php echo esc_html($summary['name']); ?></a></th>
                                            <td><?php echo esc_html($summary['brand']); ?></td>
                                            <td><?php echo esc_html($summary['strength']); ?></td>
                                            <td><?php echo esc_html($summary['flavour']); ?></td>
                                            <td><?php echo wp_kses_post($summary['price_html']); ?></td>
                                            <td><?php echo esc_html($summary['pack_value']); ?></td>
                                            <td><?php echo esc_html($summary['best_for']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php $render_product_rail($trial_products, __('Cheap 99p nicotine pouch trials now from 79p', 'kangoo'), __('Try selected pouches from 79p before building a bigger order. Trial products are limited to one per order while stock lasts.', 'kangoo')); ?>

            <?php if (!empty($comparison_rows)) : ?>
                <section class="seo-module seo-module--comparison">
                    <div class="seo-module__head">
                        <h2><?php esc_html_e('Kangoo Pouches vs supermarket and convenience buying', 'kangoo'); ?></h2>
                        <p><?php esc_html_e('A generic retailer comparison for adult nicotine users choosing where to buy nicotine pouches online or in-store.', 'kangoo'); ?></p>
                    </div>
                    <div class="seo-scroll-table">
                        <table class="seo-retailer-comparison">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Buying factor', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Kangoo Pouches', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Supermarkets', 'kangoo'); ?></th>
                                    <th><?php esc_html_e('Convenience Stores', 'kangoo'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comparison_rows as $row) : ?>
                                    <tr>
                                        <th scope="row"><?php echo esc_html($row['label']); ?></th>
                                        <td><?php echo esc_html($row['kangoo']); ?></td>
                                        <td><?php echo esc_html($row['supermarket']); ?></td>
                                        <td><?php echo esc_html($row['corner']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="seo-methodology"><?php esc_html_e('Representative buying factors checked May 2026; retailer pricing and availability vary.', 'kangoo'); ?></p>
                </section>
            <?php endif; ?>

            <section class="seo-module seo-module--guide">
                <div class="seo-module__head">
                    <h2><?php esc_html_e('Choose by strength, flavour or brand', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Dedicated landing pages make it easier to compare the pouch type you actually want, instead of filtering through one long product list.', 'kangoo'); ?></p>
                </div>
                <div class="seo-guide-grid">
                    <article>
                        <h3><?php esc_html_e('Strength pages', 'kangoo'); ?></h3>
                        <p><?php esc_html_e('Compare strong and extra strong pouches, or use the strength ladder for light, balanced, strong and extra strong guidance.', 'kangoo'); ?></p>
                        <?php $render_link_chips(array_merge($strength_links, array(array('label' => __('Strength ladder', 'kangoo'), 'url' => $strength_url)))); ?>
                    </article>
                    <article>
                        <h3><?php esc_html_e('Flavour pages', 'kangoo'); ?></h3>
                        <p><?php esc_html_e('Mint and berry pages group popular flavour families with direct links to products and the flavour explorer.', 'kangoo'); ?></p>
                        <?php $render_link_chips(array_merge($flavour_links, array(array('label' => __('Flavour explorer', 'kangoo'), 'url' => $flavour_url)))); ?>
                    </article>
                    <article class="seo-guide-grid__brand">
                        <h3><?php esc_html_e('Brand pages', 'kangoo'); ?></h3>
                        <p><?php esc_html_e('Shop ZYN, VELO, PABLO and KILLA pages when you already know the brand you want to compare.', 'kangoo'); ?></p>
                        <?php $render_link_chips($brand_links); ?>
                    </article>
                </div>
            </section>

            <section class="seo-module seo-module--faq">
                <div class="seo-module__head">
                    <h2><?php esc_html_e('Nicotine pouch buying FAQs', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Short answers for shoppers comparing pouch price, strength, delivery and trial options in the UK.', 'kangoo'); ?></p>
                </div>
                <div class="seo-faq-list">
                    <details>
                        <summary><?php esc_html_e('What is the cheapest way to try nicotine pouches at Kangoo Pouches?', 'kangoo'); ?></summary>
                        <p><?php esc_html_e('Start with the 99p pouch range when trial stock is available, then compare regular cans and multi-buy pack pricing for repeat orders.', 'kangoo'); ?></p>
                    </details>
                    <details>
                        <summary><?php esc_html_e('How do Kangoo Pouches multi-buy packs work?', 'kangoo'); ?></summary>
                        <p><?php esc_html_e('Selected regular products offer pack pricing, including 3, 5 and 10-pack options. The product page shows the live pack choices and unit price where available.', 'kangoo'); ?></p>
                    </details>
                    <details>
                        <summary><?php esc_html_e('Which nicotine pouch strength should I choose?', 'kangoo'); ?></summary>
                        <p><?php esc_html_e('Strength is personal. Use the strength ladder or pouch finder to compare light, balanced, strong and extra strong products before ordering.', 'kangoo'); ?></p>
                    </details>
                    <details>
                        <summary><?php esc_html_e('Does Kangoo Pouches offer discreet delivery?', 'kangoo'); ?></summary>
                        <p><?php esc_html_e('Kangoo Pouches orders are shipped with discreet packaging, and free UK delivery is available over £14.95.', 'kangoo'); ?></p>
                    </details>
                </div>
            </section>
        <?php elseif ($context_type === 'trial') : ?>
            <section class="seo-module seo-module--intro">
                <div class="seo-module__head">
                    <h2><?php esc_html_e('Try nicotine pouches from 99p', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('The 99p pouch range is built for adult nicotine users who want to sample selected flavours and strengths before choosing a regular can or multi-buy pack.', 'kangoo'); ?></p>
                </div>
                <div class="seo-guide-grid">
                    <article>
                        <h3><?php esc_html_e('Trial rules', 'kangoo'); ?></h3>
                        <p><?php esc_html_e('99p pouches are limited to one per order while stock lasts. Availability changes as trial stock rotates.', 'kangoo'); ?></p>
                    </article>
                    <article>
                        <h3><?php esc_html_e('What to try first', 'kangoo'); ?></h3>
                        <p><?php esc_html_e('Mint, citrus and berry pouches are useful starting points if you are comparing flavour families and strengths.', 'kangoo'); ?></p>
                    </article>
                    <article>
                        <h3><?php esc_html_e('Next step', 'kangoo'); ?></h3>
                        <p><?php esc_html_e('Use the finder or comparison tool when you want to move from a trial pouch to a regular order.', 'kangoo'); ?></p>
                    </article>
                </div>
                <?php $render_link_chips(array_merge(array(array('label' => __('Shop all nicotine pouches', 'kangoo'), 'url' => $nicotine_url)), $target_links)); ?>
            </section>
            <?php $render_product_rail($trial_products, __('In-stock 99p pouch picks', 'kangoo'), __('Trial pouches are shown stock-first and update with the live product catalogue.', 'kangoo')); ?>
        <?php elseif ($context_type === 'brand') : ?>
            <?php
            $brand_products = function_exists('kangoo_get_seo_products') ? kangoo_get_seo_products(array(
                'limit'       => 6,
                'category'    => array($term->slug),
                'include_99p' => true,
            )) : array();
            $brand_label = strtoupper($term->name);
            ?>
            <section class="seo-module seo-module--intro">
                <div class="seo-module__head">
                    <h2><?php echo esc_html(sprintf(__('Shop %s nicotine pouches in the UK', 'kangoo'), $brand_label)); ?></h2>
                    <p><?php echo esc_html(sprintf(__('%s pouches are grouped here with live stock, strength, flavour and pack pricing details, so adult nicotine users can compare options before ordering.', 'kangoo'), $brand_label)); ?></p>
                </div>
                <?php $render_link_chips(array_merge(array(array('label' => __('Compare this brand', 'kangoo'), 'url' => $compare_url), array('label' => __('Use pouch finder', 'kangoo'), 'url' => $finder_url)), $strength_links, $flavour_links)); ?>
            </section>
            <?php $render_product_rail($brand_products, sprintf(__('%s pouch buying table', 'kangoo'), $brand_label), __('Products are ordered with in-stock pouches first and include strength, flavour and pack value where available.', 'kangoo')); ?>
            <section class="seo-module seo-module--guide">
                <div class="seo-module__head">
                    <h2><?php esc_html_e('More buying guides', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Use these internal guides to compare brands, strengths and pouch styles before choosing a product.', 'kangoo'); ?></p>
                </div>
                <?php
                $brand_profile = function_exists('kangoo_get_brand_authority_profile') ? kangoo_get_brand_authority_profile($term->slug) : array();
                $brand_guide_url = function_exists('kangoo_get_brand_authority_guide_url') ? kangoo_get_brand_authority_guide_url($brand_profile) : '';
                $guide_links = array(
                    array('label' => __('Best nicotine pouches UK', 'kangoo'), 'url' => home_url('/blog/best-nicotine-pouches-uk/')),
                    array('label' => __('Nicotine pouch brands UK', 'kangoo'), 'url' => home_url('/blog/nicotine-pouch-brands-uk-zyn-velo-pablo-killa-nordic-spirit-ubbs-fumi-and-xqs-compared/')),
                    array('label' => __('Strongest nicotine pouches UK', 'kangoo'), 'url' => home_url('/blog/strongest-nicotine-pouches-uk/')),
                    array('label' => __('All nicotine pouches', 'kangoo'), 'url' => $nicotine_url),
                );

                if ($brand_guide_url) {
                    array_unshift($guide_links, array(
                        'label' => sprintf(__('%s brand guide', 'kangoo'), $brand_label),
                        'url'   => $brand_guide_url,
                    ));
                }

                $render_link_chips($guide_links);
                ?>
            </section>
        <?php elseif ($context_type === 'flavour') : ?>
            <?php
            $flavour_products = function_exists('kangoo_get_seo_products') ? kangoo_get_seo_products(array(
                'limit'     => 6,
                'tax_query' => array(
                    array(
                        'taxonomy' => $term->taxonomy,
                        'field'    => 'slug',
                        'terms'    => array($term->slug),
                    ),
                ),
            )) : array();
            ?>
            <section class="seo-module seo-module--intro">
                <div class="seo-module__head">
                    <h2><?php echo esc_html(sprintf(__('%s nicotine pouches', 'kangoo'), $term->name)); ?></h2>
                    <p><?php echo esc_html(sprintf(__('Browse %s pouch options by brand, strength and price. These pages are designed to be cleaner landing pages than filtered URLs, so shoppers and search engines can understand the range.', 'kangoo'), strtolower($term->name))); ?></p>
                </div>
                <?php $render_link_chips(array_merge(array(array('label' => __('Flavour explorer', 'kangoo'), 'url' => $flavour_url), array('label' => __('Compare pouches', 'kangoo'), 'url' => $compare_url)), $flavour_links, $brand_links)); ?>
            </section>
            <?php $render_product_rail($flavour_products, sprintf(__('Popular %s pouches', 'kangoo'), strtolower($term->name)), __('Live product picks from Kangoo Pouches, shown with stock-aware ordering and product data.', 'kangoo')); ?>
        <?php elseif ($context_type === 'strength') : ?>
            <?php
            $strength_products = function_exists('kangoo_get_seo_products') ? kangoo_get_seo_products(array(
                'limit'     => 6,
                'tax_query' => array(
                    array(
                        'taxonomy' => $term->taxonomy,
                        'field'    => 'slug',
                        'terms'    => array($term->slug),
                    ),
                ),
            )) : array();
            $clean_strength = trim(preg_replace('/\s*strength\s*/i', '', $term->name));
            ?>
            <section class="seo-module seo-module--intro">
                <div class="seo-module__head">
                    <h2><?php echo esc_html(sprintf(__('%s nicotine pouches', 'kangoo'), $clean_strength)); ?></h2>
                    <p><?php echo esc_html(sprintf(__('Compare %s pouches by brand, flavour, price and pack value. Nicotine strengths feel different by product, so experienced adult users should choose carefully.', 'kangoo'), strtolower($clean_strength))); ?></p>
                </div>
                <?php $render_link_chips(array_merge(array(array('label' => __('Strength ladder', 'kangoo'), 'url' => $strength_url), array('label' => __('Pouch finder', 'kangoo'), 'url' => $finder_url)), $strength_links, $brand_links)); ?>
            </section>
            <?php $render_product_rail($strength_products, sprintf(__('Popular %s pouches', 'kangoo'), strtolower($clean_strength)), __('Live product picks from Kangoo Pouches, shown with in-stock products first where possible.', 'kangoo')); ?>
        <?php endif; ?>

        <?php if (!empty($priority_links)) : ?>
            <section class="seo-module seo-module--authority-links" aria-label="<?php esc_attr_e('Related Kangoo Pouches pages', 'kangoo'); ?>">
                <div class="seo-module__head">
                    <h2><?php esc_html_e('Explore more nicotine pouch pages', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('Jump between the main nicotine pouch range, price checked trial offers, brand pages, flavour pages and strength guides.', 'kangoo'); ?></p>
                </div>
                <?php $render_link_chips($priority_links); ?>
            </section>
        <?php endif; ?>

        <p class="seo-nicotine-warning">
            <?php esc_html_e('Nicotine is addictive. Kangoo Pouches are for adults who already use nicotine products.', 'kangoo'); ?>
        </p>
    </div>
</section>
