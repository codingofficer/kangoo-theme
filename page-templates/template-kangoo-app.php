<?php
/**
 * Template Name: Kangoo App
 */

defined('ABSPATH') || exit;

get_header();

$app_release = function_exists('kangoo_get_app_apk_release') ? kangoo_get_app_apk_release() : array();
$default_apk_url = !empty($app_release['apk_url']) ? $app_release['apk_url'] : home_url('/app/android/kangoo-pouches-v0.6.0.apk');
$detected_app_version = !empty($app_release['version']) ? $app_release['version'] : '0.6.0';
$play_url = function_exists('get_field') ? (string) get_field('kangoo_app_google_play_url') : '';
$apk_url = $default_apk_url;
$app_version = function_exists('get_field') ? (string) get_field('kangoo_app_version') : '';
$app_size = function_exists('get_field') ? (string) get_field('kangoo_app_size') : '';
$app_updated = function_exists('get_field') ? (string) get_field('kangoo_app_updated') : '';

$primary_download_url = $play_url ?: $apk_url;
$download_href = $primary_download_url;
$app_version = $app_version ?: $detected_app_version;
$app_size = $app_size ?: '0.4 MB';
$app_updated = $app_updated ?: 'May 2026';
$app_home_image = get_theme_file_uri('assets/images/kangoo-app-home.png');
$app_rewards_image = get_theme_file_uri('assets/images/kangoo-app-rewards.png');
$app_splash_image = get_theme_file_uri('assets/images/kangoo-app-splash.png');
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
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
?>

<main id="site-main" class="kangoo-app">
    <section class="kangoo-app__hero section">
        <div class="kangoo-app__scene" aria-hidden="true">
            <div class="kangoo-app-phone kangoo-app-phone--primary">
                <img src="<?php echo esc_url($app_home_image); ?>" alt="" loading="eager" decoding="async">
            </div>

            <div class="kangoo-app-phone kangoo-app-phone--secondary">
                <img src="<?php echo esc_url($app_rewards_image); ?>" alt="" loading="eager" decoding="async">
            </div>

        </div>

        <div class="container">
            <div class="kangoo-app__hero-copy">
                <span class="eyebrow"><?php esc_html_e('Kangoo Pouches App', 'kangoo'); ?></span>
                <h1><?php esc_html_e('Your pouch shop, faster on Android.', 'kangoo'); ?></h1>
                <p><?php esc_html_e('Download the Kangoo Pouches Android app for live product search, rewards, reorder tools, and a secure WooCommerce checkout handoff.', 'kangoo'); ?></p>
                <div class="kangoo-app__actions">
                    <a class="btn btn--primary" href="<?php echo esc_url($download_href); ?>" download><?php esc_html_e('Download APP', 'kangoo'); ?></a>
                    <a class="btn btn--secondary" href="#app-install"><?php esc_html_e('How to install', 'kangoo'); ?></a>
                </div>
                <dl class="kangoo-app__meta" aria-label="<?php esc_attr_e('App details', 'kangoo'); ?>">
                    <div>
                        <dt><?php esc_html_e('Platform', 'kangoo'); ?></dt>
                        <dd><?php esc_html_e('Android', 'kangoo'); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Status', 'kangoo'); ?></dt>
                        <dd><?php esc_html_e('Ready to install', 'kangoo'); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Account', 'kangoo'); ?></dt>
                        <dd><?php esc_html_e('Kangoo login', 'kangoo'); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section class="kangoo-app__features section">
        <div class="container">
            <div class="section-header section-header--left">
                <span class="eyebrow"><?php esc_html_e('Built for quicker shopping', 'kangoo'); ?></span>
                <h2><?php esc_html_e('Everything customers need before checkout.', 'kangoo'); ?></h2>
            </div>

            <div class="kangoo-app-feature-grid">
                <article class="kangoo-app-feature">
                    <span>01</span>
                    <h3><?php esc_html_e('Pouch Finder inside the app', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Guide customers by strength, flavour, and experience without making them browse every product manually.', 'kangoo'); ?></p>
                </article>
                <article class="kangoo-app-feature">
                    <span>02</span>
                    <h3><?php esc_html_e('Fast reorder flow', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Bring back previous favourites, pack choices, and recent orders so repeat customers can move quickly.', 'kangoo'); ?></p>
                </article>
                <article class="kangoo-app-feature">
                    <span>03</span>
                    <h3><?php esc_html_e('Rewards and offers', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Make Kangoo Rewards easier to understand with points, savings, and offer reminders in one place.', 'kangoo'); ?></p>
                </article>
                <article class="kangoo-app-feature">
                    <span>04</span>
                    <h3><?php esc_html_e('Stock and delivery updates', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Low stock messages, delivery status, and back-in-stock prompts keep shoppers informed before they miss out.', 'kangoo'); ?></p>
                </article>
            </div>
        </div>
    </section>

    <section id="app-download" class="kangoo-app__download section">
        <div class="container">
            <div class="kangoo-app-download">
                <div>
                    <span class="eyebrow"><?php esc_html_e('Download', 'kangoo'); ?></span>
                    <h2><?php esc_html_e('Install the Kangoo Pouches app.', 'kangoo'); ?></h2>
                    <p><?php esc_html_e('The Android APK is live. Download it directly, install it on your device, then sign in with the same Kangoo account used on the website.', 'kangoo'); ?></p>
                </div>

                <div class="kangoo-app-download__panel">
                    <a class="kangoo-app-store-button" href="<?php echo esc_url($download_href); ?>" download>
                        <span><?php esc_html_e('Android APK', 'kangoo'); ?></span>
                        <strong><?php esc_html_e('Download now', 'kangoo'); ?></strong>
                    </a>

                    <ul class="kangoo-app-download__details">
                        <li><span><?php esc_html_e('Version', 'kangoo'); ?></span><strong><?php echo esc_html($app_version); ?></strong></li>
                        <li><span><?php esc_html_e('Size', 'kangoo'); ?></span><strong><?php echo esc_html($app_size); ?></strong></li>
                        <li><span><?php esc_html_e('Updated', 'kangoo'); ?></span><strong><?php echo esc_html($app_updated); ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="app-install" class="kangoo-app__install section">
        <div class="container">
            <div class="section-header section-header--left">
                <span class="eyebrow"><?php esc_html_e('Install steps', 'kangoo'); ?></span>
                <h2><?php esc_html_e('How customers install it.', 'kangoo'); ?></h2>
            </div>

            <ol class="kangoo-app-steps">
                <li>
                    <span>1</span>
                    <h3><?php esc_html_e('Tap the download link', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Use the Android APK download button on this page to save the app installer.', 'kangoo'); ?></p>
                </li>
                <li>
                    <span>2</span>
                    <h3><?php esc_html_e('Allow browser installs', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Because this APK is installed outside Google Play, Android may ask you to allow installs from your browser or file manager.', 'kangoo'); ?></p>
                </li>
                <li>
                    <span>3</span>
                    <h3><?php esc_html_e('Tap install anyway', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('If Android shows an extra warning, choose the install anyway option after confirming the download came from Kangoo Pouches.', 'kangoo'); ?></p>
                </li>
                <li>
                    <span>4</span>
                    <h3><?php esc_html_e('Sign in to Kangoo', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('Customers can use their Kangoo account to access orders, rewards, and saved details.', 'kangoo'); ?></p>
                </li>
                <li>
                    <span>5</span>
                    <h3><?php esc_html_e('Install future updates', 'kangoo'); ?></h3>
                    <p><?php esc_html_e('The app can notify you when a new APK is available. Download the new version from this page to update.', 'kangoo'); ?></p>
                </li>
            </ol>
        </div>
    </section>

    <section class="kangoo-app__faq section">
        <div class="container container--narrow">
            <div class="section-header">
                <span class="eyebrow"><?php esc_html_e('Questions', 'kangoo'); ?></span>
                <h2><?php esc_html_e('App details shoppers may check first.', 'kangoo'); ?></h2>
            </div>

            <div class="faq-list">
                <details open>
                    <summary><?php esc_html_e('Is the Kangoo app available on iPhone?', 'kangoo'); ?></summary>
                    <p><?php esc_html_e('The current app release is for Android. iPhone customers can continue using the Kangoo Pouches website.', 'kangoo'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Can customers still order from the website?', 'kangoo'); ?></summary>
                    <p><?php esc_html_e('Yes. The website remains the main store, and the app gives Android customers a faster mobile route.', 'kangoo'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Will rewards work in the app?', 'kangoo'); ?></summary>
                    <p><?php esc_html_e('Yes. Customers can sign in to view their Kangoo Rewards balance and recent points activity.', 'kangoo'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('Why does Android show an install warning?', 'kangoo'); ?></summary>
                    <p><?php esc_html_e('Android warns when an app is installed from outside Google Play. This is normal for direct APK downloads. Only install the file from the Kangoo Pouches website.', 'kangoo'); ?></p>
                </details>
                <details>
                    <summary><?php esc_html_e('How do app updates work?', 'kangoo'); ?></summary>
                    <p><?php esc_html_e('Until the app is on Google Play, updates are manual. The app checks for new versions and links customers back to the latest APK download.', 'kangoo'); ?></p>
                </details>
            </div>

            <div class="kangoo-app__final-actions">
                <a class="btn btn--primary" href="<?php echo esc_url($download_href); ?>" download><?php esc_html_e('Download APP', 'kangoo'); ?></a>
                <a class="btn btn--secondary" href="<?php echo esc_url($finder_url); ?>"><?php esc_html_e('Try Pouch Finder', 'kangoo'); ?></a>
                <a class="btn btn--secondary" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Shop pouches', 'kangoo'); ?></a>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>
