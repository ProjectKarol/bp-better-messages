<?php
/*
    @wordpress-plugin
    Plugin Name: BP Better Messages
    Plugin URI: https://www.wordplus.org
    Description: Pametaj ze przy aktualizacji nalezy podmienic pliki w "'layout new " oraz "layout thread"
    Version: 1.7.2
    Author: WordPlus
    Author URI: https://www.wordplus.org
    License: GPL2
    Text Domain: bp-better-messages
    Domain Path: /languages
*/
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages' ) ):

    class BP_Better_Messages
    {

        protected $realtime;

        public $version = '1.7.2';

        public $path;

        public $settings;

        /** @var BP_Better_Messages_Options $functions */
        public $options;

        /** @var BP_Better_Messages_Functions $functions */
        public $functions;

        /** @var BP_Better_Messages_Ajax $functions */
        public $ajax;

        /** @var BP_Better_Messages_Premium $functions */
        public $premium = false;

        public static function instance()
        {

            // Store the instance locally to avoid private static replication
            static $instance = null;

            // Only run these methods if they haven't been run previously
            if ( null === $instance ) {
                $instance = new BP_Better_Messages;
                $instance->setup_vars();
                $instance->setup_actions();
                $instance->setup_classes();
            }

            // Always return the instance
            return $instance;

            // The last metroid is in captivity. The galaxy is at peace.
        }

        public function setup_vars()
        {
            $this->realtime = false;
            $this->path = plugin_dir_path( __FILE__ );
        }

        public function setup_actions()
        {
            $this->require_files();
            add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
            add_action( 'init', array( $this, 'load_options' ) );
            add_action( 'init', array( $this, 'load_textDomain' ) );
        }

        public function setup_classes()
        {
            $this->options = BP_Better_Messages_Options();
            $this->functions = BP_Better_Messages_Functions();
            $this->ajax  = BP_Better_Messages_Ajax();
            $this->hooks = BP_Better_Messages_Hooks();
            $this->tab   = BP_Better_Messages_Tab();
            $this->email = BP_Better_Messages_Notifications();

            $this->urls  = BP_Better_Messages_Urls();
            $this->files = BP_Better_Messages_Files();
            $this->urls  = BP_Better_Messages_Emojies();
        }

        /**
         * Require necessary files
         */
        public function require_files()
        {
            require_once( 'inc/functions.php' );
            require_once( 'inc/component.php' );
            require_once( 'inc/ajax.php' );
            require_once( 'inc/hooks.php' );
            require_once( 'inc/options.php' );
            require_once( 'inc/notifications.php' );

            require_once( 'addons/urls.php' );
            require_once( 'addons/files.php' );
            require_once( 'addons/emojies.php' );
        }

        public function load_options()
        {
            $this->settings = $this->options->settings;

            if ( $this->settings[ 'mechanism' ] == 'websocket' && $this->settings[ 'license_status' ] === 'valid' ) {
                $this->realtime = true;
                require_once( 'inc/premium.php' );
                $this->premium = BP_Better_Messages_Premium();
            }
        }

        public function load_textDomain()
        {
            load_plugin_textdomain( 'bp-better-messages', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        public function load_scripts()
        {
            if ( !is_user_logged_in() ) return false;

            wp_register_script( 'emojionearea_js', plugins_url( 'assets/js/emojionearea.js', __FILE__ ) );
            wp_register_script( 'taggle_js', plugins_url( 'assets/js/taggle.min.js', __FILE__ ) );
            wp_register_script( 'scrollbar_js', plugins_url( 'assets/js/jquery.scrollbar.min.js', __FILE__ ) );
            wp_register_script( 'amaran_js', plugins_url( 'assets/js/jquery.amaran.js', __FILE__ ) );
            wp_register_script( 'moment_js', plugins_url( 'assets/js/moment-with-locales.min.js', __FILE__ ) );
            wp_register_script( 'livestamp_js', plugins_url( 'assets/js/livestamp.min.js', __FILE__ ) );
            wp_register_script( 'livestamp_js', plugins_url( 'assets/js/livestamp.min.js', __FILE__ ) );
            wp_register_script( 'store_js', plugins_url( 'assets/js/store.min.js', __FILE__ ) );
            wp_register_script( 'filer_js', plugins_url( 'assets/js/jquery.filer.min.js', __FILE__ ) );

            $dependencies = array(
                'jquery',
                'jquery-ui-autocomplete',
                'emojionearea_js',
                'scrollbar_js',
                'taggle_js',
                'amaran_js',
                'moment_js',
                'store_js',
                'filer_js',
                'wp-mediaelement',
                'livestamp_js'
            );

            $script_variables = array(
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'siteRefresh'   => isset( $this->settings[ 'site_interval' ] ) ? intval( $this->settings[ 'site_interval' ] ) * 1000 : 10000,
                'threadRefresh' => isset( $this->settings[ 'thread_interval' ] ) ? intval( $this->settings[ 'thread_interval' ] ) * 1000 : 3000,
                'threadUrl'     => $this->functions->get_link() . '?thread_id=',
                'assets'        => plugin_dir_url( __FILE__ ) . 'assets/',
                'user_id'       => get_current_user_id(),
                'realtime'      => $this->realtime,
                'total_unread'  => messages_get_unread_count( get_current_user_id() ),
                'strings'       => array(
                    'writing' => __( 'typing...', 'bp-better-messages' )
                )
            );

            if ( $this->realtime ) {
                $dependencies[] = 'socket-io';
                wp_register_script( 'socket-io', 'https://realtime.wordplus.org/socket.io/socket.io.js' );
                $script_variables[ 'site_id' ] = $this->functions->clean_site_url( home_url( '' ) );
                $script_variables[ 'secret_key' ] = sha1( $script_variables[ 'site_id' ] . $this->settings[ 'license_key' ] . get_current_user_id() );
            }

            wp_register_script( 'bp_messages_js', plugins_url( 'assets/js/bp-messages.js', __FILE__ ), $dependencies, $this->version );

            wp_localize_script( 'bp_messages_js', 'BP_Messages', $script_variables );

            wp_enqueue_script( 'bp_messages_js' );

            wp_enqueue_style( 'emojionearea_css', plugins_url( 'assets/css/emojionearea.css', __FILE__ ) );
            wp_enqueue_style( 'font_awesome_css', plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ), false );
            wp_enqueue_style( 'amaran_css', plugins_url( 'assets/css/amaran.min.css', __FILE__ ), false );
            wp_enqueue_style( 'filer_css', plugins_url( 'assets/css/jquery.filer.css', __FILE__ ) );
            wp_enqueue_style('wp-mediaelement');

            wp_enqueue_style( 'bp_messages_css', plugins_url( 'assets/css/bp-messages.css', __FILE__ ), false, $this->version );

            return true;
        }

    }

    function BP_Better_Messages()
    {
        return BP_Better_Messages::instance();
    }

    function BP_Better_Messages_Init()
    {
        if ( class_exists( 'BuddyPress' ) && bp_is_active('messages') ) {
            BP_Better_Messages();
        }
    }

    add_action( 'plugins_loaded', 'BP_Better_Messages_Init', 20 );

    require_once( 'inc/install.php' );
    register_activation_hook( __FILE__, 'bp_better_messages_activation' );
    register_deactivation_hook( __FILE__, 'bp_better_messages_deactivation' );
endif;