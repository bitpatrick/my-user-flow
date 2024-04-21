<div class="login-form-container">

    <form method="post" action="<?php echo wp_login_url(); ?>">

        <?php if ($attributes['password_updated']) : ?>
            <p class="login-info">
                <?php _e('Your password has been changed. You can sign in now.', 'personalize-login'); ?>
            </p>
        <?php endif; ?>

        <?php if ($attributes['lost_password_sent']) : ?>
            <p class="login-info">
                <?php _e('Check your email for a link to reset your password.', 'personalize-login'); ?>
            </p>
        <?php endif; ?>

        <!-- Show errors if there are any -->
        <?php if (count($attributes['errors']) > 0) : ?>
            <?php foreach ($attributes['errors'] as $error) : ?>
                <p class="login-error">
                    <?php echo $error; ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Show registered message if user just registered with successfull -->
        <?php if ($attributes['registered']) : ?>
            <p class="login-info">
                <?php
                printf(
                    __('You have successfully registered to <strong>%s</strong>. We have emailed your password to the email address you entered.', 'personalize-login'),
                    get_bloginfo('name')
                );
                ?>
            </p>
        <?php endif; ?>

        <!-- Show logged out message if user just logged out -->
        <?php if ($attributes['logged_out']) : ?>
            <p class="login-info">
                <?php _e('You have signed out. Would you like to sign in again?', 'personalize-login'); ?>
            </p>
        <?php endif; ?>

        <p class="login-username">
            <label for="user_login"><?php _e('Email', 'personalize-login'); ?></label>
            <input type="text" name="log" id="user_login">
        </p>
        <p class="login-password">
            <label for="user_pass"><?php _e('Password', 'personalize-login'); ?></label>
            <input type="password" name="pwd" id="user_pass">
        </p>
        <p class="login-submit">
            <input type="submit" value="<?php _e('Sign In', 'personalize-login'); ?>">
        </p>

        <p class="remember-forgot">
            <label for="remember-me"><input id="remember-me" type="checkbox">Remember Me</label>
        </p>
    </form>

    <!-- Button to go to registration page -->
    <p class="register-link">
        <?php _e('Not a member yet?', 'personalize-login'); ?>
        <a href="<?php echo wp_registration_url(); ?>" class="button">
            <?php _e('Register here', 'personalize-login'); ?>
        </a>
    </p>

    <p class="register-link">
        <?php _e('Forgot password?', 'personalize-login'); ?>
        <a href="<?php echo wp_lostpassword_url(); ?>" class="button">
            <?php _e('Click here for reset!', 'personalize-login'); ?>
        </a>
    </p>

    <button onclick="window.location.href='<?php echo wp_login_url() . '?action=oauth_login&provider=github'; ?>';" class="social-buttons__button button">
        <svg width="20" height="20" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg" class="social-icons social-icons__apple social-icons--enabled">
            <g clip-path="url(#clip0_2014_1339)">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M9.47169 0C4.23409 0 0 4.26531 0 9.54207C0 13.7601 2.71293 17.3305 6.47648 18.5942C6.94702 18.6892 7.11938 18.3889 7.11938 18.1363C7.11938 17.9151 7.10387 17.1568 7.10387 16.3668C4.46907 16.9356 3.9204 15.2293 3.9204 15.2293C3.49697 14.1234 2.86958 13.8392 2.86958 13.8392C2.00721 13.2546 2.9324 13.2546 2.9324 13.2546C3.88899 13.3178 4.39094 14.2341 4.39094 14.2341C5.2376 15.6874 6.60192 15.2768 7.15079 15.024C7.22911 14.4078 7.48018 13.9813 7.74677 13.7444C5.64533 13.5232 3.43435 12.7017 3.43435 9.03644C3.43435 7.99377 3.81047 7.1407 4.40645 6.47725C4.31242 6.24034 3.98302 5.26067 4.50067 3.94948C4.50067 3.94948 5.30042 3.69666 7.10367 4.92895C7.87571 4.72008 8.6719 4.61382 9.47169 4.61293C10.2714 4.61293 11.0867 4.72363 11.8395 4.92895C13.643 3.69666 14.4427 3.94948 14.4427 3.94948C14.9604 5.26067 14.6308 6.24034 14.5367 6.47725C15.1484 7.1407 15.509 7.99377 15.509 9.03644C15.509 12.7017 13.2981 13.5073 11.1809 13.7444C11.526 14.0445 11.8238 14.6131 11.8238 15.5137C11.8238 16.7933 11.8083 17.8203 11.8083 18.1361C11.8083 18.3889 11.9809 18.6892 12.4512 18.5944C16.2148 17.3303 18.9277 13.7601 18.9277 9.54207C18.9432 4.26531 14.6936 0 9.47169 0Z" fill="#24292F"></path>
            </g>
            <defs>
                <clipPath id="clip0_2014_1339">
                    <rect width="19" height="18.6122" fill="white"></rect>
                </clipPath>
            </defs>
        </svg>
        <span class="social-buttons__service-name">Continua su GitHub</span>
    </button>

</div>