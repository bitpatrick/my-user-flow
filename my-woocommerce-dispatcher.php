<?php

class Woocommerce_Dispatcher
{

    public function __construct()
    {
        add_action('template_redirect', array($this, 'redirect_empty_cart'));
        add_action('template_redirect', array($this, 'redirect_my_account'));
        add_action('template_redirect', array($this, 'redirect_checkout'));
    }

    /**
     * @snippet       Redirect Empty Woo Cart Page
     * @tutorial      Get CustomizeWoo.com FREE
     * @author        Rodolfo Melogli
     * @compatible    WooCommerce 8
     * @community     Join https://businessbloomer.com/club/
     */
    public function redirect_empty_cart()
    {
        if (is_cart() && WC()->cart->is_empty() && !is_user_logged_in()) {
            wp_safe_redirect(wp_login_url());
            exit;
        }
    }

    public function redirect_my_account()
    {
        if (is_account_page() && !is_user_logged_in()) {
            wp_safe_redirect(wp_login_url());
            exit;
        }
    }

    public function redirect_checkout()
    {
        if ((is_checkout() || is_checkout_pay_page()) && !is_user_logged_in()) {
            wp_safe_redirect(wp_login_url());
            exit;
        }
    }

}
