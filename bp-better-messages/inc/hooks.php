<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Hooks' ) ):

    class BP_Better_Messages_Hooks
    {

        public static function instance()
        {

            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Hooks();
            }

            return $instance;
        }

        public function __construct()
        {
            add_action( 'bp_init', array( $this, 'redirect_standard_component' ) );
            add_action( 'admin_bar_menu', array( $this, 'remove_standard_topbar' ), 999 );

            add_filter( 'bp_get_send_private_message_link', array( $this, 'pm_link' ), 20, 1 );
            add_filter( 'bp_get_message_thread_view_link', array( $this, 'thread_link' ), 20, 2 );

            add_filter( 'cron_schedules', array( $this, 'cron_intervals' ) );
            add_filter( 'get_avatar_data', array( $this, 'avatar_filter' ), 20, 2 );
        }

        public function avatar_filter( $args, $id_or_email )
        {

            if ( is_numeric( $id_or_email ) ) {
                $args[ 'extra_attr' ] .= ' data-user-id="' . intval( $id_or_email ) . '"';
            }

            return $args;
        }

        function cron_intervals( $schedules )
        {
            /*
             * Cron for our new mailer!
             */
            $schedules[ 'fifteen_minutes' ] = array(
                'interval' => 60 * 15,
                'display'  => esc_html__( 'Every Fifteen Minutes' ),
            );

            return $schedules;
        }

        public function pm_link( $link )
        {
            return BP_Better_Messages()->functions->get_link() . '?new-message&to=' . bp_core_get_username( bp_displayed_user_id() );
        }

        public function thread_link( $thread_link, $thread_id )
        {
            return BP_Better_Messages()->functions->get_link() . 'bp-messages/?thread_id=' . $thread_id;
        }

        public function redirect_standard_component()
        {
            if ( bp_is_messages_component() ) {
                wp_redirect( BP_Better_Messages()->functions->get_link() );
                exit;
            }

        }

        public function remove_standard_topbar( $wp_admin_bar )
        {
            $wp_admin_bar->remove_node( 'my-account-messages' );
        }

    }

endif;

function BP_Better_Messages_Hooks()
{
    return BP_Better_Messages_Hooks::instance();
}
