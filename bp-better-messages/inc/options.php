<?php
defined( 'ABSPATH' ) || exit;

class BP_Better_Messages_Options
{

    protected $path;
    public $settings;

    public static function instance()
    {

        static $instance = null;

        if ( null === $instance ) {
            $instance = new BP_Better_Messages_Options;
            $instance->setup_globals();
            $instance->setup_actions();
        }

        return $instance;
    }

    public function setup_globals()
    {
        $this->path = BP_Better_Messages()->path . '/views/';

        $defaults = array(
            'mechanism'       => 'ajax',
            'license_key'     => false,
            'license_status'  => false,
            'thread_interval' => 3,
            'site_interval'   => 10
        );

        $args = get_option( 'bp-better-chat-settings', array() );

        $this->settings = wp_parse_args( $args, $defaults );
    }

    public function setup_actions()
    {
        add_action( 'admin_menu', array( $this, 'settings_page' ) );
    }

    /**
     * Settings page
     */
    public function settings_page()
    {
        add_submenu_page(
            'options-general.php',
            __( 'BP Better Messages' ),
            __( 'BP Better Messages' ),
            'manage_options',
            'bp-better-chat-settings',
            array( $this, 'settings_page_html' )
        );
    }

    public function settings_page_html()
    {
        if ( isset( $_POST[ '_wpnonce' ] )
            && !empty( $_POST[ '_wpnonce' ] )
            && wp_verify_nonce( $_POST[ '_wpnonce' ], 'bp-better-messages-settings' )
        ) {
            unset( $_POST[ '_wpnonce' ], $_POST[ '_wp_http_referer' ] );

            if ( isset( $_POST[ 'save' ] ) ) {
                unset( $_POST[ 'save' ] );
                $this->update_settings( $_POST );
            } else if ( isset( $_POST[ 'license_activate' ] ) ) {
                $this->activate_license();
            } else if ( isset( $_POST[ 'license_deactivate' ] ) ) {
                $this->deactivate_license();
            }
        }

        include( $this->path . 'layout-settings.php' );
    }

    public function update_settings( $settings )
    {
        if ( $this->settings[ 'license_key' ] !== $settings[ 'license_key' ] ) {
            $settings[ 'license_status' ] = false;
        }

        foreach ( $settings as $key => $value ) {
            $this->settings[ $key ] = sanitize_text_field( $value );
        }

        update_option( 'bp-better-chat-settings', $this->settings );
    }

    public function activate_license()
    {
        $license = trim( $this->settings[ 'license_key' ] );

        // data to send in our API request
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_name'  => urlencode( 'BP Better Messages' ),
            'url'        => home_url( '' )
        );

        // Call the custom API.
        $response = wp_remote_post( 'https://www.wordplus.org', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

        // make sure the response came back okay
        if ( is_wp_error( $response ) )
            return false;

        // decode the license data
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        $this->settings[ 'license_status' ] = $license_data->license;

        $this->update_settings( $this->settings );
    }

    public function deactivate_license()
    {
        // retrieve the license from the database
        $license = trim( $this->settings[ 'license_key' ] );


        // data to send in our API request
        $api_params = array(
            'edd_action' => 'deactivate_license',
            'license'    => $license,
            'item_name'  => urlencode( 'BP Better Messages' ), // the name of our product in EDD
            'url'        => home_url( '' )
        );

        // Call the custom API.
        $response = wp_remote_post( 'https://www.wordplus.org', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

        // make sure the response came back okay
        if ( is_wp_error( $response ) )
            return false;

        // decode the license data
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        // $license_data->license will be either "deactivated" or "failed"
        if ( $license_data->license == 'deactivated' ) {
            $this->settings[ 'license_status' ] = false;
            $this->update_settings( $this->settings );
        }
    }
}

function BP_Better_Messages_Options()
{
    return BP_Better_Messages_Options::instance();
}