<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="site-header__inner">
        <div class="container">

            <div class="site-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <nav class="site-nav" aria-label="<?php esc_attr_e('Primary Menu', 'kangoo'); ?>">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'site-nav__menu',
                    'fallback_cb'    => false,
                ));
                ?>
            </nav>

            <div class="site-header__actions">
				<?php if (function_exists('wc_get_cart_url') && function_exists('WC')) : ?>
					<div class="site-header__cart" id="header-cart-trigger">

					  <span class="cart-icon">
						🛒
					  </span>

					  <span class="cart-badge">
						<?php echo (int) WC()->cart->get_cart_contents_count(); ?>
					  </span>

					</div>
				<?php endif; ?>
            </div>

        </div>
    </div>
</header>
	
<div id="cart-drawer" class="cart-drawer">
  <div class="cart-drawer__overlay"></div>

  <div class="cart-drawer__panel">
    <div class="cart-drawer__header">
      <h3>Your cart</h3>
      <button class="cart-drawer__close">×</button>
    </div>

    <div class="cart-drawer__content">
      <?php woocommerce_mini_cart(); ?>
    </div>

	<div class="cart-drawer__footer">
	  <a href="<?php echo wc_get_cart_url(); ?>" class="btn btn--ghost">View cart</a>
	  <a href="<?php echo wc_get_checkout_url(); ?>" class="btn btn--primary">Checkout</a>
	</div>
  </div>
</div>
