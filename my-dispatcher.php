<?php

/**
 * LOGIN MANAGER
 */
class Dispatcher
{

    public function __construct()
    {
        // login route
        add_action('login_form_login', array($this, 'oauth_authenticate'), 1); // oauth authenticate
        add_action('login_form_login', array($this, 'redirect_to_oauth_login'), 2); // redirect to OAuth login page
        add_action('login_form_login', array($this, 'redirect_to_custom_login'), 3); // redirect to custom login form
        add_filter('authenticate', array($this, 'maybe_redirect_at_authenticate'), 101, 1); // mayber redirect after username-password login

        // register
        add_action('login_form_register', array($this, 'do_register_user'));

        // redirect to custom register page
        add_action('login_form_register', array($this, 'redirect_to_custom_register'));

        // logout route
        add_action('wp_logout', array($this, 'redirect_after_logout'));

        // lost password
        add_action('login_form_lostpassword', array($this, 'redirect_to_custom_lostpassword'));
        add_action('login_form_lostpassword', array($this, 'do_password_lost'));
        

        // reset pwd
        add_action('login_form_rp', array($this, 'redirect_to_custom_password_reset'));
        add_action('login_form_resetpass', array($this, 'redirect_to_custom_password_reset'));

        add_action('login_form_rp', array($this, 'do_password_reset'));
        add_action('login_form_resetpass', array($this, 'do_password_reset'));

        // Test to see if WooCommerce is active (including network activated).
        $plugin_path = trailingslashit(WP_PLUGIN_DIR) . 'woocommerce/woocommerce.php';

        if (
            in_array($plugin_path, wp_get_active_and_valid_plugins())
            || in_array($plugin_path, wp_get_active_network_plugins())
        ) {

            // Custom code here. WooCommerce is active, however it has not 
            // necessarily initialized (when that is important, consider
            // using the `woocommerce_init` action).

            require_once 'my-woocommerce-dispatcher.php';
            $interceptor = new Woocommerce_Dispatcher();
        }
    }

    /********************************************************************
     * LOGIN
     *********************************************************************/

    /** 
     * Redirect the user to the custom login page instead of wp-login.php. 
     */
    function redirect_to_custom_login()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['code'])) {
            $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : null;

            if (is_user_logged_in()) {
                $this->redirect_logged_in_user($redirect_to);
                exit;
            }
            // The rest are redirected to the login page 
            $login_url = home_url('member-login/');
            if (!empty($redirect_to)) {
                $login_url = add_query_arg('redirect_to', $redirect_to, $login_url);
            }
            wp_safe_redirect($login_url);
            exit;
        }
    }

    /** 
     * Redirect the user to the oauth login page instead of wp-login.php. 
     */
    function redirect_to_oauth_login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'oauth_login') {
            if (isset($_GET['provider'])) {

                $provider = $_GET['provider'];

                require_once 'domain/OAuth.php';
                require_once 'domain/GitHubOAuth.php';
                require_once 'domain/OAuthCreator.php';

                $oauth = OAuthCreator::createOAuth($provider);

                // Memorizza l'oggetto OAuth nella sessione
                session_start();
                $_SESSION['oauth'] = $oauth;

                // Recupera l'URL di reindirizzamento
                $redirect_to = $oauth->getLoginPage();

                // Reindirizza l'utente alla pagina di autorizzazione di Github
                header($redirect_to);
                exit;
            }
        }
    }

    /********************************************************************
     * CUSTOM PAGES
     *********************************************************************/

    /** 
     * Redirects the user to the correct page depending on whether he / she 
     * is an admin or not. 
     * 
     * @param string $redirect_to An optional redirect_to URL for admin users 
     */
    private function redirect_logged_in_user($redirect_to = null)
    {
        $user = wp_get_current_user();
        if (user_can($user, 'manage_options')) {
            if ($redirect_to) {
                wp_safe_redirect($redirect_to);
            } else {
                wp_safe_redirect(admin_url());
            }
        } else {
            wp_safe_redirect(home_url('member-account/'));
        }
    }

    /********************************************************************
     * AUTHENTICATION
     *********************************************************************/

    /**
     * OAUTH FLOW AUTHENTICATION
     * 
     * response example from provider
     * access_token=gho_16C7e42F292c6912E7710c838347Ae178B4a&scope=repo%2Cgist&token_type=bearer
     */
    public function oauth_authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {

            require_once 'domain/OAuth.php';
            require_once 'domain/GitHubOAuth.php';
            session_start();

            // Verifica che lo stato corrisponda allo stato memorizzato
            if (!isset($_GET['state']) || $_SESSION['state'] !== $_GET['state']) {
                // Gestisci la situazione come preferisci (reindirizza o mostra un messaggio di errore)
                exit;
            }

            if (isset($_SESSION['oauth'])) {

                /*
                * l'utente ha richiesto la pagina di login oauth
                */
                $oauth = $_SESSION['oauth']; // recupero l'istanza oauth usata dall'utente
                $code = $_GET['code']; // recupero il code inviato dal redirect della pagina di authorization oauth
                $token = $oauth->exchangeAuthCodeForToken($code); // scambio il code per il token di accesso
                $user_info = $oauth->getUserInfo($token); // recupero le info dell'utente
                $user_id = null;
                $redirect_to = null;

                $user_email = $user_info['email']; // recupero l'email dell'utente dalle sue info
                $existing_user_email = email_exists($user_email);

                if (isset($user_email) && $existing_user_email == false) {

                    /**
                     * UTENTE NON ESISTENTE
                     */
                    $username = $user_info['login']; // Nota: 'name' potrebbe non essere unico o potrebbe non soddisfare i requisiti di username di WordPress. 'login' è un'alternativa migliore.
                    $existing_username = username_exists($username);

                    if ($existing_username == false) {

                        /**
                         * CREA UTENTE
                         */
                        $password = wp_generate_password(); // Genera una password casuale
                        $user_id = wp_create_user($username, $user_email, $password); // crea utente

                        if (is_wp_error($user_id)) {
                            /**
                             * ERRORE CREAZIONE UTENTE
                             */
                            // Gestisci l'errore durante la creazione dell'utente
                            $redirect_to = add_query_arg('result', 'error_creating_user', wp_login_url());
                        } else {
                            $redirect_to = add_query_arg('result', 'succesful_creating_user', wp_login_url());
                        }
                    } else {

                        /**
                         * Impossibile creare l'utente con quell'username perché esiste già
                         */
                        $redirect_to = add_query_arg('result', 'existing_username', wp_login_url());
                        wp_safe_redirect($redirect_to);
                        exit;
                    }
                } elseif (isset($user_email)) {

                    /*
                    * Email già presente nel Database
                    */
                    $user = get_user_by('email', $user_email);
                    $user_id = $user->ID;

                    // Reindirizza o esegui altre azioni dopo aver impostato il cookie
                    $redirect_to = add_query_arg('result', 'successful_login', home_url());
                } else {
                    $redirect_to = add_query_arg('result', 'error_oauth_authentication', home_url());
                }

                if (is_numeric($user_id)) {
                    $remember = true;  // Imposta il cookie per un periodo esteso
                    // Imposta il cookie di autenticazione
                    wp_set_auth_cookie($user_id, $remember);
                }

                // Reindirizza alla homepage o a una pagina specifica
                wp_safe_redirect($redirect_to);
                exit; // Assicurati di chiamare exit dopo il reindirizzamento
            }
        }
    }

    /** 
     * Redirect the user after authentication if there were any errors in username-password authentication flow. 
     * 
     * @param Wp_User|Wp_Error $user The signed in user, or the errors that have occurred during login. 
     * @param string $username The user name used to log in. 
     * @param string $password The password used to log in. 
     * 
     * @return Wp_User|Wp_Error The logged in user, or error information if there were errors. 
     */
    function maybe_redirect_at_authenticate($user)
    {
        // Check if the earlier authenticate filter (most likely, 
        // the default WordPress authentication) functions have found errors 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (is_wp_error($user)) {
                /*
                * Si è verificato un errore durante il processo di autenticazione
                */
                $error_codes = join(',', $user->get_error_codes());
                $login_url = home_url('member-login/');
                $login_url = add_query_arg('login', $error_codes, $login_url);
                wp_safe_redirect($login_url);
                exit;
            }
        }
        return $user;
    }

    /********************************************************************
     * LOGOUT
     *********************************************************************/

    /** 
     * Redirect to custom login page after the user has been logged out. 
     */
    public function redirect_after_logout()
    {
        $redirect_url = home_url('member-login/?logged_out=true');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /********************************************************************
     * REGISTRATION
     *********************************************************************/

    /** 
     * Redirects the user to the custom registration page instead 
     * of wp-login.php?action=register. 
     */
    public function redirect_to_custom_register()
    {
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            if (is_user_logged_in()) {
                $this->redirect_logged_in_user();
            } else {
                wp_redirect(home_url('member-register/'));
            }
            exit;
        }
    }

    /** 
     * Handles the registration of a new user. 
     * 
     * Used through the action hook "login_form_register" activated on wp-login.php 
     * when accessed through the registration action. 
     */
    public function do_register_user()
    {
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $redirect_url = home_url('member-register/');

            // Check if user registration is allowed
            if (!get_option('users_can_register')) {
                // Registration closed, display error 
                $redirect_url = add_query_arg('register-errors', 'closed', $redirect_url);
            } elseif (!$this->verify_recaptcha()) {
                // Recaptcha check failed, display error 
                $redirect_url = add_query_arg('register-errors', 'captcha', $redirect_url);
            } else {
                $email = sanitize_email($_POST['email']);
                $first_name = sanitize_text_field($_POST['first_name']);
                $last_name = sanitize_text_field($_POST['last_name']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                // Check if the passwords match
                if ($password !== $confirm_password) {
                    // Passwords do not match, redirect with error
                    $redirect_url = add_query_arg('register-errors', 'password_mismatch', $redirect_url);
                } else {
                    // Passwords match, attempt to register the user
                    $result = $this->register_user($email, $first_name, $last_name, $password);

                    if (is_wp_error($result)) {
                        // Parse errors into a string and append as parameter to redirect 
                        $errors = join(',', $result->get_error_codes());
                        $redirect_url = add_query_arg('register-errors', $errors, $redirect_url);
                    } else {
                        // Success, redirect to login page.
                        $redirect_url = home_url('member-login/');
                        $redirect_url = add_query_arg('registered', $email, $redirect_url);
                    }
                }
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /** 
     * Checks that the reCAPTCHA parameter sent with the registration 
     * request is valid. 
     * 
     * @return bool True if the CAPTCHA is OK, otherwise false. 
     */
    private function verify_recaptcha()
    {
        // This field is set by the recaptcha widget if check is successful 
        if (isset($_POST['g-recaptcha-response'])) {
            $captcha_response = $_POST['g-recaptcha-response'];
        } else {
            return false;
        }
        // Verify the captcha response from Google 
        $response = wp_remote_post(
            'https://www.google.com/recaptcha/api/siteverify',
            array(
                'body' => array(
                    'secret' => get_option('personalize-login-recaptcha-secret-key'),
                    'response' => $captcha_response
                )
            )
        );
        $success = false;
        if ($response && is_array($response)) {
            $decoded_response = json_decode($response['body']);
            $success = $decoded_response->success;
        }
        return $success;
    }

    /** 
     * Validates and then completes the new user signup process if all went well. 
     * 
     * @param string $email The new user's email address 
     * @param string $first_name The new user's first name 
     * @param string $last_name The new user's last name 
     * 
     * @return int|WP_Error The id of the user that was created, or error if failed. 
     */
    private function register_user($email, $first_name, $last_name, $password)
    {
        $errors = new WP_Error();
        // Email address is used as both username and email. It is also the only 
        // parameter we need to validate 
        require_once 'my-error-rubric.php';
        if (!is_email($email)) {
            $errors->add('email', Error_Rubric::get_error_message('email'));
            return $errors;
        }
        if (username_exists($email) || email_exists($email)) {
            $errors->add('email_exists', Error_Rubric::get_error_message('email_exists'));
            return $errors;
        }
        // Generate the password so that the subscriber will have to check email... 
        $user_data = array(
            'user_login'    => $email,
            'user_email'    => $email,
            'user_pass'     => $password,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'nickname'      => $first_name,
        );
        $user_id = wp_insert_user($user_data);

        if (!is_wp_error($user_id)) {

            // Imposta l'utente come non attivo
            add_user_meta($user_id, 'account_activated', false);

            // Genera un token di attivazione univoco
            $activation_code = sha1($user_login . time());

            // Salva il codice di attivazione nei metadati dell'utente
            add_user_meta($user_id, 'activation_code', $activation_code, true);

            // Crea il link di attivazione
            $activation_link = add_query_arg(array(
                'key' => $activation_code,
                'user' => $user_id
            ), home_url('activate'));

            // Invia l'email di attivazione
            $this->send_activation_email($user_email, $activation_link);
        }

        return $user_id;
    }


    private function send_activation_email($user_email, $activation_link)
    {
        $subject = 'Attiva il tuo account';
        $message = 'Clicca su questo link per attivare il tuo account: ' . $activation_link;
        $headers = 'From: Your Name <your-email@example.com>' . "\r\n";

        $mail_sent = wp_mail($user_email, $subject, $message, $headers);

        if (!$mail_sent) {
            // Email non inviata, gestisci questa situazione come preferisci.
            return new WP_Error('email_failed', 'Impossibile inviare l\'email.');
        }
    }

    /********************************************************************
     * LOST PASSWORD
     *********************************************************************/

    /** 
     * Redirects the user to the custom "Forgot your password?" page instead of 
     * wp-login.php?action=lostpassword. 
     */
    public function redirect_to_custom_lostpassword()
    {
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            if (is_user_logged_in()) {
                $this->redirect_logged_in_user();
                exit;
            }
            wp_redirect(home_url('member-password-lost/'));
            exit;
        }
    }

    /** 
     * Initiates password reset. 
     */
    public function do_password_lost()
    {
        if ('POST' == $_SERVER['REQUEST_METHOD']) {

            /**
             * The function's name is a bit misleading: the function doesn't really retrieve the password 
             * but instead checks the data from the form and then prepares the user's account for WordPress 
             * password reset by creating the reset WP password token and emailing it to the user.
             */
            $errors = retrieve_password();

            if (is_wp_error($errors)) {
                // Errors found 
                $redirect_url = home_url('member-password-lost');
                $redirect_url = add_query_arg('errors', join(',', $errors->get_error_codes()), $redirect_url);
            } else {
                // Email sent 
                $redirect_url = home_url('member-login');
                $redirect_url = add_query_arg('checkemail', 'confirm', $redirect_url);
            }
            wp_redirect($redirect_url);
            exit;
        }
    }

    /********************************************************************
     * RESET PWD
     *********************************************************************/

    /** 
     * Redirects to the custom password reset page, or the login page 
     * if there are errors. 
     */
    public function redirect_to_custom_password_reset()
    {
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            // Verify key / login combo 
            $user = check_password_reset_key($_REQUEST['key'], $_REQUEST['login']);
            if (!$user || is_wp_error($user)) {
                if ($user && $user->get_error_code() === 'expired_key') {
                    wp_redirect(home_url('member-login?login=expiredkey'));
                } else {
                    wp_redirect(home_url('member-login?login=invalidkey'));
                }
                exit;
            }
            $redirect_url = home_url('member-password-reset');
            $redirect_url = add_query_arg('login', esc_attr($_REQUEST['login']), $redirect_url);
            $redirect_url = add_query_arg('key', esc_attr($_REQUEST['key']), $redirect_url);
            wp_redirect($redirect_url);
            exit;
        }
    }

    /** 
     * Resets the user's password if the password reset form was submitted. 
     */
    public function do_password_reset()
    {
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $rp_key = $_REQUEST['rp_key'];
            $rp_login = $_REQUEST['rp_login'];
            $user = check_password_reset_key($rp_key, $rp_login);
            if (!$user || is_wp_error($user)) {
                if ($user && $user->get_error_code() === 'expired_key') {
                    wp_redirect(home_url('member-login?login=expiredkey'));
                } else {
                    wp_redirect(home_url('member-login?login=invalidkey'));
                }
                exit;
            }
            if (isset($_POST['pass1'])) {
                if ($_POST['pass1'] != $_POST['pass2']) {
                    // Passwords don't match 
                    $redirect_url = home_url('member-password-reset');
                    $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
                    $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
                    $redirect_url = add_query_arg('error', 'password_reset_mismatch', $redirect_url);
                    wp_redirect($redirect_url);
                    exit;
                }
                if (empty($_POST['pass1'])) {
                    // Password is empty 
                    $redirect_url = home_url('member-password-reset');
                    $redirect_url = add_query_arg('key', $rp_key, $redirect_url);
                    $redirect_url = add_query_arg('login', $rp_login, $redirect_url);
                    $redirect_url = add_query_arg('error', 'password_reset_empty', $redirect_url);
                    wp_redirect($redirect_url);
                    exit;
                }
                // Parameter checks OK, reset password 
                reset_password($user, $_POST['pass1']);
                wp_redirect(home_url('member-login?password=changed'));
            } else {
                echo "Invalid request.";
            }
            exit;
        }
    }
}
