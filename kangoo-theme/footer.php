<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div>
                <h3 class="site-footer__title"><?php bloginfo('name'); ?></h3>
                <p class="site-footer__text">
                    Kango Pouches is a UK online store for nicotine pouches, caffeine pouches and sample packs, built for speed, clarity and modern product discovery.
                </p>
            </div>

            <div>
                <h3 class="site-footer__title">Browse</h3>
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'site-footer__menu',
                    'fallback_cb'    => false,
                ));
                ?>
            </div>
        </div>

        <div class="site-footer__bottom">
            <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?></p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
