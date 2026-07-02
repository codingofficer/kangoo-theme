<?php
defined('ABSPATH') || exit;

$city = function_exists('kangoo_delivery_city_current') ? kangoo_delivery_city_current() : null;

if (!$city) {
    get_template_part('404');
    return;
}

$link_groups = function_exists('kangoo_delivery_city_link_groups') ? kangoo_delivery_city_link_groups() : array();
$popular_city_links = function_exists('kangoo_delivery_city_popular_links') ? kangoo_delivery_city_popular_links($city['slug']) : array();
$shop_url = function_exists('kangoo_delivery_city_term_url') ? kangoo_delivery_city_term_url('nicotine-pouches', '/product-category/nicotine-pouches/') : home_url('/product-category/nicotine-pouches/');
$trial_url = function_exists('kangoo_delivery_city_term_url') ? kangoo_delivery_city_term_url('99p-pouches', '/product-category/99p-pouches/') : home_url('/product-category/99p-pouches/');

get_header();
?>

<main id="primary" class="kangoo-delivery-city">
    <section class="section kangoo-delivery-city__hero">
        <div class="container">
            <div class="kangoo-delivery-city__hero-inner">
                <div class="kangoo-delivery-city__copy">
                    <span class="eyebrow"><?php esc_html_e('Online UK delivery', 'kangoo'); ?></span>
                    <h1><?php echo esc_html($city['title']); ?></h1>
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            __('Kangoo Pouches is an online UK retailer delivering tobacco-free nicotine pouches to %1$s and across the UK. Browse live brand, strength, flavour and offer pages before choosing a product.', 'kangoo'),
                            $city['city']
                        ));
                        ?>
                    </p>
                    <div class="kangoo-delivery-city__actions">
                        <a class="btn btn--primary" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Shop nicotine pouches', 'kangoo'); ?></a>
                        <a class="btn btn--secondary" href="<?php echo esc_url($trial_url); ?>"><?php esc_html_e('View 79p offers', 'kangoo'); ?></a>
                    </div>
                </div>

                <aside class="kangoo-delivery-city__note" aria-label="<?php esc_attr_e('Online retailer note', 'kangoo'); ?>">
                    <strong><?php esc_html_e('Online-only buying route', 'kangoo'); ?></strong>
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            __('Kangoo Pouches does not operate a physical shop in %s. This page exists to help shoppers in the area find the right online delivery route.', 'kangoo'),
                            $city['city']
                        ));
                        ?>
                    </p>
                    <span><?php echo esc_html($city['note']); ?></span>
                </aside>
            </div>
        </div>
    </section>

    <section class="section kangoo-delivery-city__routes" aria-labelledby="kangoo-delivery-routes-title">
        <div class="container">
            <div class="seo-module">
                <div class="seo-module__head">
                    <h2 id="kangoo-delivery-routes-title">
                        <?php
                        echo esc_html(sprintf(
                            __('Buy nicotine pouches online for %s delivery', 'kangoo'),
                            $city['city']
                        ));
                        ?>
                    </h2>
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            __('Use these Kangoo Pouches pages to compare brands, strengths, flavours and current trial offers. Product pages remain the source of truth for live price, stock and checkout details for %s orders.', 'kangoo'),
                            $city['city']
                        ));
                        ?>
                    </p>
                </div>

                <div class="seo-guide-grid kangoo-delivery-city__link-grid">
                    <?php foreach ($link_groups as $group) : ?>
                        <article>
                            <h3><?php echo esc_html($group['title']); ?></h3>
                            <?php if (!empty($group['summary'])) : ?>
                                <p><?php echo esc_html($group['summary']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($group['links'])) : ?>
                                <div class="seo-link-chips">
                                    <?php foreach ($group['links'] as $link) : ?>
                                        <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section kangoo-delivery-city__delivery-note" aria-labelledby="kangoo-delivery-note-title">
        <div class="container">
            <div class="seo-module seo-module--intro">
                <div class="seo-module__head">
                    <h2 id="kangoo-delivery-note-title">
                        <?php
                        echo esc_html(sprintf(
                            __('Online delivery to %s, not a physical-location page', 'kangoo'),
                            $city['city']
                        ));
                        ?>
                    </h2>
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            __('This page is intentionally written as an online delivery page for %1$s and %2$s. Kangoo Pouches does not claim a premises, collection point or city-specific address in %1$s.', 'kangoo'),
                            $city['city'],
                            $city['area']
                        ));
                        ?>
                    </p>
                </div>
                <div class="kangoo-delivery-city__proof">
                    <span><?php esc_html_e('Age-checked checkout', 'kangoo'); ?></span>
                    <span><?php esc_html_e('Live category pricing', 'kangoo'); ?></span>
                    <span><?php esc_html_e('UK delivery options', 'kangoo'); ?></span>
                    <span><?php esc_html_e('Tobacco-free pouch brands', 'kangoo'); ?></span>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($city['faqs'])) : ?>
        <section class="section kangoo-delivery-city__faq" aria-labelledby="kangoo-delivery-faq-title">
            <div class="container">
                <div class="seo-module seo-module--faq">
                    <div class="seo-module__head">
                        <h2 id="kangoo-delivery-faq-title">
                            <?php
                            echo esc_html(sprintf(
                                __('%s nicotine pouch delivery FAQs', 'kangoo'),
                                $city['city']
                            ));
                            ?>
                        </h2>
                        <p><?php esc_html_e('Short answers about buying from Kangoo Pouches online for UK delivery.', 'kangoo'); ?></p>
                    </div>
                    <div class="seo-faq-list">
                        <?php foreach ($city['faqs'] as $faq) : ?>
                            <details>
                                <summary><?php echo esc_html($faq['question']); ?></summary>
                                <p><?php echo esc_html($faq['answer']); ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($popular_city_links)) : ?>
        <section class="section kangoo-delivery-city__areas" aria-labelledby="kangoo-delivery-areas-title">
            <div class="container">
                <div class="seo-module">
                    <div class="seo-module__head">
                        <h2 id="kangoo-delivery-areas-title"><?php esc_html_e('Popular delivery areas', 'kangoo'); ?></h2>
                        <p><?php esc_html_e('Browse other Kangoo Pouches online delivery pages for major UK cities.', 'kangoo'); ?></p>
                    </div>
                    <div class="seo-link-chips">
                        <?php foreach ($popular_city_links as $link) : ?>
                            <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php
get_footer();
