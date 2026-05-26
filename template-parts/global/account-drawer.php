<div id="account-drawer" class="account-drawer" aria-hidden="true">
    <div class="account-drawer__overlay" data-account-close></div>

    <aside class="account-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="account-drawer-title">
        <button type="button" class="account-drawer__close" aria-label="<?php esc_attr_e('Close account panel', 'kangoo'); ?>" data-account-close>
            ×
        </button>

        <div class="account-drawer__inner">
            <div class="account-drawer__auth" data-account-auth>
                <h2 id="account-drawer-title" class="account-drawer__title">
                    <?php esc_html_e('Do you have an account with us?', 'kangoo'); ?>
                </h2>

                <div class="account-tabs" role="tablist" aria-label="<?php esc_attr_e('Account tabs', 'kangoo'); ?>">
                    <button
                        type="button"
                        class="account-tabs__button is-active"
                        data-account-tab="login"
                        role="tab"
                        aria-selected="true"
                    >
                        <span class="account-tabs__label"><?php esc_html_e('Yes', 'kangoo'); ?></span>
                        <span class="account-tabs__sub"><?php esc_html_e('I have an account', 'kangoo'); ?></span>
                    </button>

                    <button
                        type="button"
                        class="account-tabs__button"
                        data-account-tab="register"
                        role="tab"
                        aria-selected="false"
                    >
                        <span class="account-tabs__label"><?php esc_html_e('No', 'kangoo'); ?></span>
                        <span class="account-tabs__sub"><?php esc_html_e("I'm new here", 'kangoo'); ?></span>
                    </button>
                </div>

                <div class="account-drawer__message" data-account-message aria-live="polite"></div>

                <div class="account-pane is-active" data-account-pane="login">
                    <form class="account-form" data-account-form="login" novalidate>
                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('Email or Username', 'kangoo'); ?></span>
                            <input type="text" name="login" autocomplete="username" required>
                        </label>

                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('Password', 'kangoo'); ?></span>
                            <input type="password" name="password" autocomplete="current-password" required>
                        </label>

                        <label class="account-checkbox">
                            <input type="checkbox" name="remember" value="1">
                            <span><?php esc_html_e('Keep me signed in', 'kangoo'); ?></span>
                        </label>

                        <button type="submit" class="btn btn--primary account-submit" data-loading-text="<?php esc_attr_e('Signing in...', 'kangoo'); ?>">
                            <?php esc_html_e('Sign in', 'kangoo'); ?>
                        </button>

                        <a class="account-link" href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                            <?php esc_html_e('Forgot password?', 'kangoo'); ?>
                        </a>
                    </form>
                </div>

                <div class="account-pane" data-account-pane="register">
                    <form class="account-form" data-account-form="register" novalidate>
                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('First name', 'kangoo'); ?></span>
                            <input type="text" name="first_name" autocomplete="given-name" required>
                        </label>

                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('Last name', 'kangoo'); ?></span>
                            <input type="text" name="last_name" autocomplete="family-name" required>
                        </label>

                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('Email', 'kangoo'); ?></span>
                            <input type="email" name="email" autocomplete="email" required>
                        </label>

                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('Password', 'kangoo'); ?></span>
                            <input type="password" name="password" autocomplete="new-password" required>
                        </label>

                        <label class="account-field">
                            <span class="account-field__label"><?php esc_html_e('Confirm password', 'kangoo'); ?></span>
                            <input type="password" name="confirm_password" autocomplete="new-password" required>
                        </label>

                        <button type="submit" class="btn btn--primary account-submit" data-loading-text="<?php esc_attr_e('Creating account...', 'kangoo'); ?>">
                            <?php esc_html_e('Create account', 'kangoo'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="account-drawer__logged-in is-hidden" data-account-logged-in>
                <h2 class="account-drawer__title"><?php esc_html_e('Welcome back', 'kangoo'); ?></h2>
                <p class="account-drawer__welcome">
                    <?php esc_html_e('You are signed in.', 'kangoo'); ?>
                </p>

                <div class="account-summary" data-account-summary></div>

                <div class="account-actions">
                    <a href="<?php echo esc_url(kangoo_account_get_redirect_url()); ?>" class="btn btn--primary">
                        <?php esc_html_e('Go to my account', 'kangoo'); ?>
                    </a>

                    <button type="button" class="btn btn--ghost" data-account-logout>
                        <?php esc_html_e('Sign out', 'kangoo'); ?>
                    </button>
                </div>
            </div>
        </div>
    </aside>
</div>