<?php

class Template_Engine
{

    public function __construct()
    {

        // login form
        add_shortcode('custom-login-form', array($this, 'render_login_form'));

        // add register custom form
        add_shortcode('custom-register-form', array($this, 'render_register_form'));

        // add reset password custom page
        add_shortcode('custom-password-lost-form', array($this, 'render_password_lost_form'));

        add_shortcode('custom-password-reset-form', array($this, 'render_password_reset_form'));

        // recaptcha
        add_action('wp_print_footer_scripts', array($this, 'add_captcha_js_to_footer'));
    }

    /** 
     * A shortcode for rendering the login form. 
     * 
     * @param array $attributes Shortcode attributes. 
     * @param string $content The text content for shortcode. Not used. 
     * 
     * @return string The shortcode output 
     */
    public function render_login_form($attributes, $content = null)
    {
        // Parse shortcode attributes 
        $default_attributes = array('show_title' => false);
        $attributes = shortcode_atts($default_attributes, $attributes);
        $show_title = $attributes['show_title'];
        if (is_user_logged_in()) {
            return __('You are already signed in.', 'personalize-login');
        }

        // Pass the redirect parameter to the WordPress login functionality: by default, 
        // don't specify a redirect, but if a valid redirect URL has been passed as 
        // request parameter, use it. 
        $attributes['redirect'] = '';
        if (isset($_REQUEST['redirect_to'])) {
            $attributes['redirect'] = wp_validate_redirect($_REQUEST['redirect_to'], $attributes['redirect']);
        }

        // Error messages 
        $errors = array();
        if (isset($_REQUEST['login'])) {
            $error_codes = explode(',', $_REQUEST['login']);
            require_once 'my-error-rubric.php';
            foreach ($error_codes as $code) {
                $errors[] = Error_Rubric::get_error_message($code);
            }
        }
        $attributes['errors'] = $errors;

        // Check if user just logged out 
        $attributes['logged_out'] = isset($_REQUEST['logged_out']) && $_REQUEST['logged_out'] == true;

        // Check if the user just registered 
        $attributes['registered'] = isset($_REQUEST['registered']);

        // Check if the user just requested a new password 
        $attributes['lost_password_sent'] = isset($_REQUEST['checkemail']) && $_REQUEST['checkemail'] == 'confirm';

        // Check if user just updated password 
        $attributes['password_updated'] = isset($_REQUEST['password']) && $_REQUEST['password'] == 'changed';

        // remove filters as woo commerce filter
        remove_all_filters('lostpassword_url');

        // Render the login form using an external template 
        return $this->get_template_html('login_form', $attributes);
    }

    /** 
     * A shortcode for rendering the new user registration form. 
     * 
     * @param array $attributes Shortcode attributes. 
     * @param string $content The text content for shortcode. Not used. 
     * 
     * @return string The shortcode output 
     */
    public function render_register_form($attributes, $content = null)
    {
        // Parse shortcode attributes 
        $default_attributes = array('show_title' => false);
        $attributes = shortcode_atts($default_attributes, $attributes);
        if (is_user_logged_in()) {
            return __('You are already signed in.', 'personalize-login');
        } elseif (!get_option('users_can_register')) {
            return __('Registering new users is currently not allowed.', 'personalize-login');
        } else {

            // Retrieve possible errors from request parameters 
            $attributes['errors'] = array();
            if (isset($_REQUEST['register-errors'])) {
                $error_codes = explode(',', $_REQUEST['register-errors']);
                require_once 'my-error-rubric.php';
                foreach ($error_codes as $error_code) {
                    $attributes['errors'][] = Error_Rubric::get_error_message($error_code);
                }
            }

            // Retrieve recaptcha key 
            $attributes['recaptcha_site_key'] = get_option('personalize-login-recaptcha-site-key', null);

            return $this->get_template_html('register_form', $attributes);
        }
    }

    /** 
     * A shortcode for rendering the form used to reset a user's password. 
     * 
     * @param array $attributes Shortcode attributes. 
     * @param string $content The text content for shortcode. Not used. 
     * 
     * @return string The shortcode output 
     */
    public function render_password_reset_form($attributes, $content = null)
    {
        // Parse shortcode attributes 
        $default_attributes = array('show_title' => false);
        $attributes = shortcode_atts($default_attributes, $attributes);
        if (is_user_logged_in()) {
            return __('You are already signed in.', 'personalize-login');
        } else {
            if (isset($_REQUEST['login']) && isset($_REQUEST['key'])) {
                $attributes['login'] = $_REQUEST['login'];
                $attributes['key'] = $_REQUEST['key'];
                // Error messages 
                $errors = array();
                if (isset($_REQUEST['error'])) {
                    $error_codes = explode(',', $_REQUEST['error']);
                    foreach ($error_codes as $code) {
                        $errors[] = Error_Rubric::get_error_message($code);
                    }
                }
                $attributes['errors'] = $errors;
                return $this->get_template_html('password_reset_form', $attributes);
            } else {
                return __('Invalid password reset link.', 'personalize-login');
            }
        }
    }

    /** 
     * A shortcode for rendering the form used to initiate the password reset. 
     * 
     * @param array $attributes Shortcode attributes. 
     * @param string $content The text content for shortcode. Not used. 
     * 
     * @return string The shortcode output 
     */
    public function render_password_lost_form($attributes, $content = null)
    {
        // Parse shortcode attributes 
        $default_attributes = array('show_title' => false);
        $attributes = shortcode_atts($default_attributes, $attributes);
        if (is_user_logged_in()) {
            return __('You are already signed in.', 'personalize-login');
        } else {

            // Retrieve possible errors from request parameters 
            $attributes['errors'] = array();
            if (isset($_REQUEST['errors'])) {
                $error_codes = explode(',', $_REQUEST['errors']);
                foreach ($error_codes as $error_code) {
                    $attributes['errors'][] = Error_Rubric::get_error_message($error_code);
                }
            }

            return $this->get_template_html('password_lost_form', $attributes);
        }
    }

    /** 
     * Renders the contents of the given template to a string and returns it. 
     * 
     * @param string $template_name The name of the template to render (without .php) 
     * @param array $attributes The PHP variables for the template 
     * 
     * @return string The contents of the template. 
     */
    private function get_template_html($template_name, $attributes = null)
    {
        if (!$attributes) {
            $attributes = array();
        }
        ob_start();
        do_action('personalize_login_before_' . $template_name);
        require('templates/' . $template_name . '.php');
        do_action('personalize_login_after_' . $template_name);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /** 
     * An action function used to include the reCAPTCHA JavaScript file 
     * at the end of the page. 
     */
    public static function add_captcha_js_to_footer()
    {
        echo "<script src='https://www.google.com/recaptcha/api.js'></script>";
    }
}
