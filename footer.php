<?php
/* FILE: footer.php */
?>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="site-footer__brand">
                <h3 class="site-footer__title"><?php bloginfo('name'); ?></h3>
                <p class="site-footer__text">
                    Kangoo Pouches is a UK online store for nicotine pouches, caffeine pouches and sample packs, built for speed, clarity and modern product discovery.
                </p>
                <address class="site-footer__address">
                    <strong>Business Address:</strong>
                    Kangoo Pouches<br>
                    Suite 8, Cragside House<br>
                    52 Heaton Road<br>
                    Newcastle upon Tyne<br>
                    NE6 1SE<br>
                    United Kingdom
                </address>
                <p class="site-footer__note">
                    Fast UK delivery, tracked shipping information, and easy product discovery for first-time customers.
                </p>
            </div>

            <div class="site-footer__group">
                <h3 class="site-footer__title">Browse</h3>
                <?php if (has_nav_menu('footer')) : ?>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'container'      => false,
                        'menu_class'     => 'site-footer__menu',
                        'fallback_cb'    => false,
                    ));
                    ?>
                <?php else : ?>
                    <ul class="site-footer__menu">
                        <li><a href="<?php echo esc_url(home_url('/shop/')); ?>">Shop all products</a></li>
                        <li><a href="<?php echo esc_url(home_url('/kangoo-app/')); ?>">Kangoo App</a></li>
                        <li><a href="<?php echo esc_url(home_url('/product-category/nicotine-pouches/')); ?>">Nicotine pouches</a></li>
                        <li><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a></li>
                        <li><a href="<?php echo esc_url(home_url('/sitemap/')); ?>">Sitemap</a></li>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="site-footer__group">
                <h3 class="site-footer__title">Support</h3>
                <ul class="site-footer__menu">
                    <li><a href="<?php echo esc_url(home_url('/delivery-information/')); ?>">Delivery &amp; shipping</a></li>
                    <li><a href="<?php echo esc_url(home_url('/returns-and-refunds/')); ?>">Returns &amp; refunds</a></li>
                    <li><a href="<?php echo esc_url(home_url('/kangoo-rewards/')); ?>">Kangoo Rewards</a></li>
                    <li><a href="<?php echo esc_url(home_url('/referral-program/')); ?>">Referral Program</a></li>
                    <li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a></li>
                    <li><a href="<?php echo esc_url(home_url('/sitemap/')); ?>">Sitemap</a></li>
                    <li><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact us</a></li>
                </ul>
            </div>

            <div class="site-footer__group">
                <h3 class="site-footer__title">Legal</h3>
                <ul class="site-footer__menu">
                    <li><a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a></li>
                    <li><a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>">Terms &amp; conditions</a></li>
                    <li><a href="<?php echo esc_url(home_url('/cookie-policy/')); ?>">Cookie Policy</a></li>
                    <li><a href="<?php echo esc_url(home_url('/18-plus-policy/')); ?>">18+ Policy</a></li>
                </ul>
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
