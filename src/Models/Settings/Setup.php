<?php

namespace Transferito\Models\Settings;

use Transferito\Models\Core\Api as TransferitoAPI;
use Transferito\Models\Core\Config;

class Setup {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $emptyApiKeys;
    private $api;

    public function __construct()
    {
        if (current_user_can('activate_plugins')) {
            add_action( 'admin_menu', array($this, "createMenuPage"));
            add_action( 'admin_init', array($this, "createOptions"));
            add_action( 'admin_enqueue_scripts', array($this, "loadTransferitoAssets"));
            add_action( 'admin_notices', array($this, "userNotification") );
            add_action( 'plugin_action_links_transferito/transferito.php', array($this, "actionLinks" ));


            add_filter( 'script_loader_tag', array($this, "modifyScripts"), 10, 3 );
            add_filter( 'plugin_row_meta', array($this, "upgradePluginMetaLink"), 100, 4 );

            $this->api = new TransferitoAPI();
            $this->options = get_option('transferito_settings_option');
            $publicKey = isset($this->options['public_transferito_key']) ? $this->options['public_transferito_key'] : '';
            $secretKey = isset($this->options['secret_transferito_key']) ? $this->options['secret_transferito_key'] : '';
            $this->emptyApiKeys = filter_var(!($publicKey && $secretKey), FILTER_VALIDATE_BOOLEAN);

            set_transient('transferito_empty_keys', $this->emptyApiKeys);
        }
    }

    public function actionLinks($links)
    {
        $links = array_merge(
            array(
                '<a href="' . esc_url( admin_url( '/admin.php?page=transferito-main' ) ) . '">' . __( 'Start Migration', 'textdomain' ) . '</a>',
            ),
            $links
        );

        return $links;
    }

    public function upgradePluginMetaLink($plugin_meta, $plugin_file, $plugin_data, $status)
    {
        if ( false !== strpos($plugin_file, 'transferito.php') ) {
            $pro_slug = plugin_basename( __FILE__ );
            $installed_plugins = get_plugins();
            $is_pro_installed = array_key_exists( $pro_slug, $installed_plugins ) || in_array( $pro_slug, $installed_plugins, true );

            $plugin_meta[] = sprintf(
                '<a style="color: #B30507; font-weight: bold;" href="https://transferito.com/pricing/" rel="noopener noreferrer" title="Go Pro" target="_blank">%1$s <span class="dashicons dashicons-external"></span></a>',
                __('Upgrade to Premium', 'text_domain')
            );
        }
        return $plugin_meta;
    }

    public function userNotification()
    {
        $showMessage = get_transient( 'transferito_settings_update_counter' );

        if ($showMessage) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>Your settings have been updated!</p>
            </div>
            <?php
        }

        delete_transient('transferito_settings_update_counter');
    }

    public function createMenuPage()
    {
        add_menu_page(
            __( 'Transferito1', 'textdomain' ),
            'Transferito',
            'manage_options',
            'transferito-main',
            '',
            'none',
            26
        );

        add_submenu_page(
            'transferito-main',
            'Start Migration',
            'Start Migration',
            'manage_options',
            'transferito-main',
            array($this, 'createTransferHTML')
        );

        add_submenu_page(
            'transferito-main',
            'Settings',
            'Settings',
            'manage_options',
            'transferito-settings',
            array($this, 'settingsHTML')
        );


    }

    public function createOptions()
    {
        register_setting(
            'transferito_settings_group', // Option group
            'transferito_settings_option', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'transferito_settings_section', // ID
            '', // Title
            array( $this, 'settingSection' ), // Callback
            'transferito-settings' // Page
        );

        /**
         * API Keys fields
         */
        add_settings_field(
            'public_transferito_key', // ID
            'Public Key', // Title
            array( $this, 'publicKeyField' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'secret_transferito_key', // ID
            'Secret Key', // Title
            array( $this, 'secretKeyField' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_chunk_size', // ID
            'Chunk Size', // Title
            array( $this, 'chunkSizeField' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );


        add_settings_field(
            'transferito_force_upload', // ID
            'Force Upload', // Title
            array( $this, 'forceUploadField' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_force_tar_backup', // ID
            'Force TAR Archive Creation', // Title
            array( $this, 'forceTarArchive' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_include_htaccess', // ID
            'Include htaccess', // Title
            array( $this, 'includeHtaccess' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_use_default_collation', // ID
            'Use Default Collation', // Title
            array( $this, 'useDefaultCollation' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_bypass_exec_archive_creation', // ID
            'Bypass CMD Backup Creation', // Title
            array( $this, 'bypassExecArchiveCreation' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_disable_wordpress_cache', // ID
            'Disable WordPress Object Cache', // Title
            array( $this, 'disableWordPressObjectCache' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );

        add_settings_field(
            'transferito_malcare_waf_plugin_fix', // ID
            'Ignore Malcare WAF', // Title
            array( $this, 'malcareWAFPluginFix' ), // Callback
            'transferito-settings', // Page
            'transferito_settings_section'
        );
    }

    public function modifyScripts($tag, $handle, $src)
    {
        if ($handle === 'transferito_sentry-js') {
            return '<script src="' . $src . '" integrity="sha384-8WK0y5yz2y0ti+wMW84WAgeQI72MHZIGHN3B30ljAcDfexPZRbv3eQ+eqzPKDDqE" crossorigin="anonymous" id="' . $handle . '"></script>';
        }

        return $tag;
    }

    public function loadTransferitoAssets($hook)
    {
        $dashIconFont = plugins_url( '../Views/Assets/css/transferito-font.css', dirname(__FILE__));

        /**
         * To load the icon on every admin page
         */
        wp_register_style('transferito_font_css', $dashIconFont, false, TRANSFERITO_VERSION);
        wp_enqueue_style( 'transferito_font_css' );

        wp_enqueue_style(
            'transferito-google-fonts',
            'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap',
            false
        );


        /**
         * Display on all pages
         */
        if ($hook === 'transferito_page_transferito-settings' || $hook === 'toplevel_page_transferito-main') {
            /**
             * New Styles
             */
            $updatedStyles = plugins_url( '../Views/Assets/css/transferito-styles.min.css', dirname(__FILE__));

            wp_register_style('transferito_updated_css', $updatedStyles, false, TRANSFERITO_VERSION);
            wp_enqueue_style( 'transferito_updated_css' );
        }

        /**
         * Only display on the create migration page
         */
        if ($hook === 'toplevel_page_transferito-main') {
            $script = plugins_url( '../Views/Assets/js/transferito.js', dirname(__FILE__));
            $fontAwesomeScript = 'https://kit.fontawesome.com/e5d967ae32.js';
            $sentryScript = 'https://browser.sentry-cdn.com/7.40.0/bundle.replay.min.js';
            $sentryImplementationScript = plugins_url( '../Views/Assets/js/transferito-sentry.js', dirname(__FILE__));

            wp_register_script('transferito_js', $script, array ('jquery'), TRANSFERITO_VERSION, false);
            wp_register_script('transferito_font-awesome-js', $fontAwesomeScript, array ('jquery'), TRANSFERITO_VERSION, false);
            wp_register_script('transferito_sentry-js', $sentryScript, null, null, false);
            wp_register_script('transferito_sentry-implement-js', $sentryImplementationScript, array('transferito_sentry-js'), null, false);

            wp_enqueue_script( 'transferito_font-awesome-js' );
            wp_enqueue_script( 'transferito_sentry-js' );
            wp_enqueue_script( 'transferito_sentry-implement-js' );
            wp_enqueue_script( 'transferito_js' );

            wp_localize_script('transferito_js', 'transferitoData', [ 'baseUrl' => Config::getBaseApiUrl() ]);
        }
    }

    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['public_transferito_key'] ) )
            $new_input['public_transferito_key'] = sanitize_text_field( $input['public_transferito_key'] );

        if( isset( $input['secret_transferito_key'] ) )
            $new_input['secret_transferito_key'] = sanitize_text_field( $input['secret_transferito_key'] );

        if( isset( $input['transferito_chunk_size'] ) )
            $new_input['transferito_chunk_size'] = sanitize_text_field( $input['transferito_chunk_size'] );

        $new_input['transferito_force_upload'] = isset($input['transferito_force_upload']);
        $new_input['transferito_include_htaccess'] = isset($input['transferito_include_htaccess']);
        $new_input['transferito_force_tar_backup'] = isset($input['transferito_force_tar_backup']);
        $new_input['transferito_use_default_collation'] = isset($input['transferito_use_default_collation']);
        $new_input['transferito_bypass_exec_archive_creation'] = isset($input['transferito_bypass_exec_archive_creation']);
        $new_input['transferito_disable_wordpress_cache'] = isset($input['transferito_disable_wordpress_cache']);
        $new_input['transferito_malcare_waf_plugin_fix'] = isset($input['transferito_malcare_waf_plugin_fix']);

        set_transient( 'transferito_settings_update_counter', 1 );

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function settingSection()
    {
        print '';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function publicKeyField()
    {
        printf(
            '<input type="text" class="transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" id="public_transferito_key" name="transferito_settings_option[public_transferito_key]" value="%s" />',
            isset( $this->options['public_transferito_key'] ) ? esc_attr( $this->options['public_transferito_key']) : ''
        );
    }

    public function secretKeyField()
    {
        printf(
            '<input type="text" class="transferito-input__text-box transferito-form-element transferito-input__text-box--full-width transferito-input__text-box--thin" id="secret_transferito_key" name="transferito_settings_option[secret_transferito_key]" value="%s" />',
            isset( $this->options['secret_transferito_key'] ) ? esc_attr( $this->options['secret_transferito_key']) : ''
        );
    }

    public function chunkSizeField()
    {
        $chunkSize = isset( $this->options['transferito_chunk_size'] ) ? esc_attr( $this->options['transferito_chunk_size']) : '';

        $html = '<select id="transferito_chunk_size" class="transferito-form-element transferito-input__dropdown transferito-input__dropdown--full-width transferito-input__dropdown--large" name="transferito_settings_option[transferito_chunk_size]">';
        $html .= $chunkSize == "" ? '<option selected disabled>Use default chunk size</option>' : '<option disabled>Use default chunk size</option>';
        $html .= $chunkSize == "1" ? '<option selected>1</option>' : '<option>1</option>';
        $html .= $chunkSize == "5" ? '<option selected>5</option>' : '<option>5</option>';
        $html .= $chunkSize == "10" ? '<option selected>10</option>' : '<option>10</option>';
        $html .= $chunkSize == "25" ? '<option selected>25</option>' : '<option>25</option>';
        $html .= $chunkSize == "50" ? '<option selected>50</option>' : '<option>50</option>';
        $html .= '</select>';

        echo $html;
    }

    public function forceUploadField()
    {
        $checked = isset($this->options['transferito_force_upload']) &&  $this->options['transferito_force_upload'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_force_upload" name="transferito_settings_option[transferito_force_upload]"' . $checked . '/>';
    }

    public function includeHtaccess()
    {
        $checked = isset($this->options['transferito_include_htaccess']) &&  $this->options['transferito_include_htaccess'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_include_htaccess" name="transferito_settings_option[transferito_include_htaccess]"' . $checked . '/>';
    }

    public function forceTarArchive()
    {
        $checked = isset($this->options['transferito_force_tar_backup']) &&  $this->options['transferito_force_tar_backup'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_force_tar_backup" name="transferito_settings_option[transferito_force_tar_backup]"' . $checked . '/>';
    }

    public function useDefaultCollation()
    {
        $checked = isset($this->options['transferito_use_default_collation']) &&  $this->options['transferito_use_default_collation'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_use_default_collation" name="transferito_settings_option[transferito_use_default_collation]"' . $checked . '/>';
    }

    public function bypassExecArchiveCreation()
    {
        $checked = isset($this->options['transferito_bypass_exec_archive_creation']) && $this->options['transferito_bypass_exec_archive_creation'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_bypass_exec_archive_creation" name="transferito_settings_option[transferito_bypass_exec_archive_creation]"' . $checked . '/>';
    }

    public function disableWordPressObjectCache()
    {
        $checked = isset($this->options['transferito_disable_wordpress_cache']) && $this->options['transferito_disable_wordpress_cache'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_disable_wordpress_cache" name="transferito_settings_option[transferito_disable_wordpress_cache]"' . $checked . '/>';
    }

    public function malcareWAFPluginFix()
    {
        $checked = isset($this->options['transferito_malcare_waf_plugin_fix']) && $this->options['transferito_malcare_waf_plugin_fix'] ? 'checked' : '';
        echo '<input type="checkbox" id="transferito_malcare_waf_plugin_fix" name="transferito_settings_option[transferito_malcare_waf_plugin_fix]"' . $checked . '/>';
    }

    public function settingsHTML()
    {
        $publicKey = isset($this->options['public_transferito_key']) ? $this->options['public_transferito_key'] : '';
        $secretKey = isset($this->options['secret_transferito_key']) ? $this->options['secret_transferito_key'] : '';
        echo loadTemplate("settings", array(
            'publicKey'     => $publicKey,
            'secretKey'     => $secretKey,
            'hasAPIKeys'    => $this->emptyApiKeys
        ));
    }

    public function createTransferHTML()
    {
        /**
         * Get logged in user's information
         */
        $userData = wp_get_current_user();
        $name = (isset($userData->user_firstname) && $userData->user_firstname !== '')
            ? $userData->user_firstname
            : $userData->display_name;

        /**
         * Pass in the correct template array data
         * If the user has api keys
         */
        $templateData = [
            'userWithoutAPIKeys'    => $this->emptyApiKeys,
            'name'                  => $name,
        ];
        echo loadTemplate("create-transfer", $templateData);
    }

}
