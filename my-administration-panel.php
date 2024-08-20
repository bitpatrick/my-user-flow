
<?php

class Administration_Panel
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_user_flow_submenu'));
        add_action('admin_init', array($this, 'register_settings_fields'));
    }

    /**
     * Aggiunge il sottomenu
     */
    function add_user_flow_submenu()
    {
        add_options_page(
            'User Flow Settings',    // Titolo della pagina
            'User Flow',             // Titolo del sottomenu
            'manage_options',        // Capacità richiesta per visualizzare la voce di menu
            'user-flow-settings',    // Slug della pagina del sottomenu
            array($this, 'user_flow_settings_page') // Funzione callback per visualizzare la pagina
        );
    }

    /**
     * Registra i campi di impostazione necessari per le schede.
     */
    public function register_settings_fields()
    {
        // Registra le schede dinamicamente
        $this->register_tab_settings(
            1,
            'Tab 1',
            'user_flow_recaptcha_section_1',
            'reCAPTCHA Settings',
            array(
                array('personalize-login-recaptcha-site-key', 'reCAPTCHA site key', array($this, 'render_recaptcha_site_key_field')),
                array('personalize-login-recaptcha-secret-key', 'reCAPTCHA secret key', array($this, 'render_recaptcha_secret_key_field')),
            )
        );

        $this->register_tab_settings(
            2,
            'Tab 2',
            'user_flow_section_2',
            'Custom Settings',
            array(
                array('field1', 'Field 1', array($this, 'misha_text_field')),
                array('field2', 'Field 2', array($this, 'misha_text_field')),
            )
        );

        $this->register_tab_settings(
            'oauth',
            'OAuth',
            'user_flow_oauth_section',
            'OAuth Settings',
            array(
                array('show_github_button', 'Show GitHub Button', array($this, 'render_github_button_checkbox')),
            )
        );
    }

    /**
     * Metodo privato per registrare dinamicamente una scheda
     */
    private function register_tab_settings($tab_number, $tab_name, $section_id, $section_title, $fields)
    {
        $tab = is_numeric($tab_number) ? 'tab' . $tab_number : $tab_number;
        $page_slug = 'user_flow_page_slug_' . $tab;
        $option_group = 'user_flow_option_group_' . $tab;

        // Aggiunge una nuova sezione per la tab
        add_settings_section(
            $section_id, // ID univoco della sezione
            __($section_title, 'personalize-login'), // Titolo della sezione
            function() use ($section_title) { echo "<p>Configure your {$section_title} settings below.</p>"; }, // Callback per la descrizione della sezione
            $page_slug // Slug della pagina
        );

        // Registra i campi personalizzati per la tab
        foreach ($fields as $field) {
            register_setting($option_group, $field[0]);
            add_settings_field($field[0], $field[1], $field[2], $page_slug, $section_id, array('name' => $field[0]));
        }
    }

    /**
     * Visualizza la pagina delle impostazioni con le schede.
     */
    function user_flow_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo get_admin_page_title() ?></h1>
            <?php
            $tabs = array(
                'tab1' => 'Tab 1',
                'tab2' => 'Tab 2',
                'oauth' => 'OAuth', // Aggiungi il nuovo tab OAuth
            );
            $current_tab = isset($_GET['tab']) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : array_key_first($tabs);
            ?>
            <form method="post" action="options.php">
                <!-- Barra di navigazione delle schede -->
                <nav class="nav-tab-wrapper">
                    <?php
                    foreach ($tabs as $tab => $name) {
                        // Classe CSS per la scheda attuale
                        $current = $tab === $current_tab ? ' nav-tab-active' : '';
                        // URL della scheda
                        $url = add_query_arg(array('page' => 'user-flow-settings', 'tab' => $tab), admin_url('options-general.php'));
                        // Stampa il link della scheda
                        echo "<a class=\"nav-tab{$current}\" href=\"{$url}\">{$name}</a>";
                    }
                    ?>
                </nav>

                <!-- Contenuto delle schede -->
                <?php
                settings_fields("user_flow_option_group_" . $current_tab); // Nome del gruppo di opzioni
                do_settings_sections("user_flow_page_slug_" . $current_tab); // Slug della pagina
                submit_button(); // Pulsante "Save Changes"
                ?>
            </form>
        </div>
        <?php
    }

    public function render_recaptcha_section_description()
    {
        echo '<p>' . __('Enter your reCAPTCHA site and secret keys below.', 'personalize-login') . '</p>';
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

    // Funzione di callback personalizzata per stampare il campo di testo
    function misha_text_field($args)
    {
        printf(
            '<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
            $args['name'],
            $args['name'],
            get_option($args['name'])
        );
    }

    // Funzione di callback per stampare la checkbox GitHub
    function render_github_button_checkbox()
    {
        $value = get_option('show_github_button', ''); // Recupera il valore salvato
        echo '<input type="checkbox" name="show_github_button" value="1"' . checked(1, $value, false) . '> Show GitHub Button';
    }
}
