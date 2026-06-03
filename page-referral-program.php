<?php
get_header();

$target = function_exists('kangoo_referrals_qualification_spend') ? kangoo_referrals_qualification_spend() : 30.0;
$reward_amount = function_exists('kangoo_referrals_reward_amount') ? kangoo_referrals_reward_amount() : 10.0;
$discount_percent = function_exists('kangoo_referrals_friend_discount_percent') ? kangoo_referrals_friend_discount_percent() : 15;
$account_url = function_exists('wc_get_account_endpoint_url') && function_exists('kangoo_referrals_endpoint_slug')
    ? wc_get_account_endpoint_url(kangoo_referrals_endpoint_slug())
    : home_url('/my-account/refer-a-friend/');
$terms_url = home_url('/terms-and-conditions/');
$icon_base = get_template_directory_uri() . '/assets/images/referrals/';
$kangoo_icon = get_template_directory_uri() . '/assets/images/kangoo-icon-white.png';
$demo_rows = array(
    array('date' => '3 Jun 2026', 'friend' => 'Annie Smith', 'spend' => kangoo_plain_wc_price(18) . ' / ' . kangoo_plain_wc_price($target), 'status' => 'Pending', 'reward' => kangoo_plain_wc_price($reward_amount), 'progress' => 60),
    array('date' => '12 May 2026', 'friend' => 'Michael Brown', 'spend' => kangoo_plain_wc_price(42.50), 'status' => 'Paid', 'reward' => kangoo_plain_wc_price($reward_amount), 'progress' => 100),
    array('date' => '28 Apr 2026', 'friend' => 'Connor Wilson', 'spend' => kangoo_plain_wc_price(31.20), 'status' => 'Paid', 'reward' => kangoo_plain_wc_price($reward_amount), 'progress' => 100),
);
?>

<main class="referral-program">
    <section class="referral-program__hero">
        <div class="container referral-program__hero-inner">
            <div class="referral-program__hero-copy">
                <span class="referral-program__eyebrow"><?php esc_html_e('Kango Pouches Referral Program', 'kangoo'); ?></span>
                <h1><?php echo esc_html(sprintf(__('Give %1$d%% off. Earn %2$s cash.', 'kangoo'), $discount_percent, kangoo_plain_wc_price($reward_amount))); ?></h1>
                <p><?php echo wp_kses_post(sprintf(__('Share your referral link with friends. They get %1$d%% off their first order, and you earn %2$s once they spend %3$s or more in completed orders.', 'kangoo'), $discount_percent, kangoo_plain_wc_price($reward_amount), kangoo_plain_wc_price($target))); ?></p>
                <div class="referral-program__actions">
                    <a class="btn btn--primary" href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Get your referral link', 'kangoo'); ?></a>
                    <a class="btn btn--secondary" href="<?php echo esc_url($terms_url); ?>"><?php esc_html_e('View full terms', 'kangoo'); ?></a>
                </div>
            </div>
            <img class="referral-program__mascot" src="<?php echo esc_url($kangoo_icon); ?>" alt="" loading="eager">
        </div>
    </section>

    <section class="referral-program__band">
        <div class="container referral-program__steps">
            <article>
                <span aria-hidden="true"><img src="<?php echo esc_url($icon_base . 'share.png'); ?>" alt="" loading="lazy"></span>
                <strong><?php esc_html_e('1. Invite', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Send your referral link or code to friends.', 'kangoo'); ?></p>
            </article>
            <article>
                <span aria-hidden="true"><img src="<?php echo esc_url($icon_base . 'gifts.png'); ?>" alt="" loading="lazy"></span>
                <strong><?php esc_html_e('2. Friend saves', 'kangoo'); ?></strong>
                <p><?php echo esc_html(sprintf(__('They get %d%% off their first order.', 'kangoo'), $discount_percent)); ?></p>
            </article>
            <article>
                <span aria-hidden="true"><img src="<?php echo esc_url($icon_base . 'earn.png'); ?>" alt="" loading="lazy"></span>
                <strong><?php esc_html_e('3. You earn', 'kangoo'); ?></strong>
                <p><?php echo wp_kses_post(sprintf(__('You get %1$s once they reach %2$s in completed spend.', 'kangoo'), kangoo_plain_wc_price($reward_amount), kangoo_plain_wc_price($target))); ?></p>
            </article>
        </div>
    </section>

    <section class="referral-program__section">
        <div class="container referral-program__showcase">
            <div class="referral-program__section-heading">
                <span class="referral-program__eyebrow"><?php esc_html_e('Example dashboard', 'kangoo'); ?></span>
                <h2><?php esc_html_e('See how referral progress is tracked', 'kangoo'); ?></h2>
                <p><?php esc_html_e('This demo data shows what customers will see in their account once friends start ordering through a referral link.', 'kangoo'); ?></p>
            </div>

            <div class="referral-program__stats" aria-label="<?php esc_attr_e('Example referral stats', 'kangoo'); ?>">
                <article>
                    <img src="<?php echo esc_url($icon_base . 'referral.png'); ?>" alt="" loading="lazy">
                    <span><?php esc_html_e('Successful Referrals', 'kangoo'); ?></span>
                    <strong>8</strong>
                </article>
                <article>
                    <img src="<?php echo esc_url($icon_base . 'wall-clock.png'); ?>" alt="" loading="lazy">
                    <span><?php esc_html_e('Pending Rewards', 'kangoo'); ?></span>
                    <strong><?php echo esc_html(kangoo_plain_wc_price(30)); ?></strong>
                </article>
                <article>
                    <img src="<?php echo esc_url($icon_base . 'wallet.png'); ?>" alt="" loading="lazy">
                    <span><?php esc_html_e('Total Earned', 'kangoo'); ?></span>
                    <strong><?php echo esc_html(kangoo_plain_wc_price(80)); ?></strong>
                </article>
            </div>

            <div class="referral-program__demo-panel">
                <article class="referral-program__progress">
                    <span class="referral-program__avatar" aria-hidden="true">AS</span>
                    <div>
                        <strong><?php esc_html_e('Annie Smith', 'kangoo'); ?></strong>
                        <p><?php echo esc_html(sprintf(__('%1$s / %2$s completed', 'kangoo'), kangoo_plain_wc_price(18), kangoo_plain_wc_price($target))); ?></p>
                        <div class="referral-program__progress-track" aria-hidden="true"><span style="width: 60%"></span></div>
                        <small><?php echo esc_html(sprintf(__('%s remaining until reward unlocks', 'kangoo'), kangoo_plain_wc_price(12))); ?></small>
                    </div>
                    <span class="referral-program__status referral-program__status--pending"><?php esc_html_e('Pending', 'kangoo'); ?></span>
                </article>

                <table class="referral-program__table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Referred friend', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Their spend', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Status', 'kangoo'); ?></th>
                            <th><?php esc_html_e('Reward', 'kangoo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demo_rows as $row) : ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Date', 'kangoo'); ?>"><?php echo esc_html($row['date']); ?></td>
                                <td data-label="<?php esc_attr_e('Referred friend', 'kangoo'); ?>"><?php echo esc_html($row['friend']); ?></td>
                                <td data-label="<?php esc_attr_e('Their spend', 'kangoo'); ?>"><?php echo esc_html($row['spend']); ?></td>
                                <td data-label="<?php esc_attr_e('Status', 'kangoo'); ?>"><span class="referral-program__status referral-program__status--<?php echo esc_attr(strtolower($row['status'])); ?>"><?php echo esc_html($row['status']); ?></span></td>
                                <td data-label="<?php esc_attr_e('Reward', 'kangoo'); ?>"><?php echo esc_html($row['reward']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="referral-program__section referral-program__section--rules">
        <div class="container referral-program__rules">
            <article>
                <strong><?php esc_html_e('Completed orders only', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Spend only counts once orders are completed and not cancelled, refunded, charged back, disputed, or reversed.', 'kangoo'); ?></p>
            </article>
            <article>
                <strong><?php esc_html_e('Genuine referrals', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Self-referrals, duplicate accounts, and suspected abuse are excluded from the program.', 'kangoo'); ?></p>
            </article>
            <article>
                <strong><?php esc_html_e('Reviewed before payment', 'kangoo'); ?></strong>
                <p><?php esc_html_e('Approved rewards are normally paid within 30 days of qualification and approval.', 'kangoo'); ?></p>
            </article>
        </div>
    </section>

    <section class="referral-program__cta">
        <div class="container referral-program__cta-inner">
            <div>
                <h2><?php esc_html_e('Ready to invite a friend?', 'kangoo'); ?></h2>
                <p><?php esc_html_e('Log in to your account to copy your personal referral link and start sharing.', 'kangoo'); ?></p>
            </div>
            <a class="btn btn--primary" href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Open referral dashboard', 'kangoo'); ?></a>
        </div>
    </section>
</main>

<?php get_footer(); ?>
