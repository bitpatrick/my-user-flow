<?php

class Administration_Panel
{

    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings_fields'));
    }

    /** 
     * Registers the settings fields needed by the plugin. 
     */
    public function register_settings_fields()
    {
        // Create settings fields for the two keys used by reCAPTCHA 
        register_setting('general', 'personalize-login-recaptcha-site-key');
        register_setting('general', 'personalize-login-recaptcha-secret-key');
        add_settings_field(
            'personalize-login-recaptcha-site-key',
            '<label for="personalize-login-recaptcha-site-key">' . __('reCAPTCHA site key', 'personalize-login') . '</label>',
            array($this, 'render_recaptcha_site_key_field'),
            'general'
        );
        add_settings_field(
            'personalize-login-recaptcha-secret-key',
            '<label for="personalize-login-recaptcha-secret-key">' . __('reCAPTCHA secret key', 'personalize-login') . '</label>',
            array($this, 'render_recaptcha_secret_key_field'),
            'general'
        );
    }
    public function render_recaptcha_site_key_field()
    {
        $value = get_option('personalize-login-recaptcha-site-key', '');
        echo '<input type="text" id="personalize-login-recaptcha-site-key" name="personalize-login-recaptcha-site-key" value="' . esc_attr($value) . '" />';
    }
    public function render_recaptcha_secret_key_field()
    {
        $value = get_option('personalize-login-recaptcha-secret-key', '');
        echo '<input type="text" id="personalize-login-recaptcha-secret-key" name="personalize-login-recaptcha-secret-key" value="' . esc_attr($value) . '" />';
    }
}
