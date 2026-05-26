<?php
defined('ABSPATH') || exit;

$rewards_url = home_url('/kangoo-rewards/');
?>

<section class="home-rewards-cta" aria-label="<?php esc_attr_e('Kangoo Rewards', 'kangoo'); ?>">
    <div class="container">
        <a class="home-rewards-cta__inner" href="<?php echo esc_url($rewards_url); ?>">
            <span class="home-rewards-cta__label"><?php esc_html_e('Kangoo Rewards', 'kangoo'); ?></span>
            <strong><?php esc_html_e('Rewards for regular Kangoo customers.', 'kangoo'); ?></strong>
            <span class="home-rewards-cta__text"><?php esc_html_e('Collect points as you shop, then redeem them from your account or the Kangoo app when you are ready.', 'kangoo'); ?></span>
        </a>
    </div>
</section>
