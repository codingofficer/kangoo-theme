<?php
defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}

$badge = get_field('badge');
$strength = get_field('strength');
?>

<article <?php wc_product_class('product-card', $product); ?>>

    <!-- BADGE -->
    <?php if ($badge && $badge !== 'none'): ?>
        <div class="product-badge product-badge--<?php echo esc_attr($badge); ?>">
            <?php echo ucfirst(str_replace('_', ' ', $badge)); ?>
        </div>
    <?php endif; ?>

    <!-- IMAGE -->
    <a href="<?php the_permalink(); ?>" class="product-card__media">
        <?php echo woocommerce_get_product_thumbnail(); ?>
    </a>

    <!-- CONTENT -->
    <div class="product-card__content">

        <!-- TITLE -->
        <h3 class="product-card__title">
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
        </h3>

        <!-- STRENGTH -->
        <?php if ($strength): ?>
            <div class="product-card__strength">
                <?php echo ucfirst($strength); ?>
            </div>
        <?php endif; ?>

        <!-- PRICE -->
        <div class="product-card__price">
            <?php echo $product->get_price_html(); ?>
        </div>

        <!-- CTA -->
        <div class="product-card__actions">
            <?php
            echo apply_filters(
              'woocommerce_loop_add_to_cart_link',
              sprintf(
                '<a href="%s" data-quantity="1" class="btn btn--primary">Add to cart</a>',
                esc_url($product->add_to_cart_url())
              ),
              $product
            );
            ?>
        </div>

    </div>

</article>