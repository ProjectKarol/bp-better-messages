<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Premium_Messages
 *
 * This used only when user using WebSocket version to communicate site with websocket server
 */
class BP_Better_Messages_Premium
{

    public $site_id;
    public $secret_key;

    public static function instance()
    {

        // Store the instance locally to avoid private static replication
        static $instance = null;

        // Only run these methods if they haven't been run previously
        if ( null === $instance ) {
            $instance = new BP_Better_Messages_Premium;
            $instance->setup_globals();
            $instance->setup_actions();
        }

        // Always return the instance
        return $instance;

        // The last metroid is in captivity. The galaxy is at peace.
    }

    public function setup_globals()
    {
        $this->site_id = BP_Better_Messages()->functions->clean_site_url( home_url( '' ) );
        $this->secret_key = BP_Better_Messages()->options->settings[ 'license_key' ];
    }

    public function setup_actions()
    {
        add_action( 'messages_message_sent', array( $this, 'on_message_sent' ) );
        add_action( 'template_redirect', array( $this, 'read_threads' ) );
    }

    public function read_threads()
    {
        if ( !is_user_logged_in() ) return false;

        global $wpdb;

        $threads = array();

        $user_id = get_current_user_id();

        if ( !empty( $_COOKIE ) ) {
            foreach ( $_COOKIE as $key => $value ) {
                if ( strpos( $key, 'bp-better-messages-thread-' ) !== false ) {
                    $id = intval( str_replace( 'bp-better-messages-thread-', '', $key ) );
                    $threads[ $id ] = sanitize_text_field( $value );
                    setcookie( $key, null, -1, '/' );
                }
            }
        }

        if ( !empty( $threads ) ) {
            foreach ( $threads as $id => $time ) {
                $unread = $wpdb->get_var( $wpdb->prepare( "
                SELECT
                  COUNT({$wpdb->base_prefix}bp_messages_messages.id) AS count
                FROM {$wpdb->base_prefix}bp_messages_messages
                WHERE {$wpdb->base_prefix}bp_messages_messages.thread_id = %d
                AND {$wpdb->base_prefix}bp_messages_messages.date_sent > %s
                ", $id, $time ) );

                $wpdb->update( "{$wpdb->base_prefix}bp_messages_recipients", array(
                    'unread_count' => $unread
                ), array(
                    'user_id'   => $user_id,
                    'thread_id' => $id
                ), array( '%d' ), array( '%d', '%d' ) );
            }
        }

    }

    public function on_message_sent( $message )
    {
        $user_id = get_current_user_id();
        $recipients = array();

        /**
         * Copy message so we can play with it
         */
        $message_copy = clone $message;

        $message->message = convert_smilies( $message->message );
        // All recipients
        $dummy_recipients = array();

        foreach ( $message->recipients as $recipient ) {
            if ( is_object( $recipient ) ) {
                $dummy_recipients[ $recipient->user_id ] = $recipient->user_id;
            } else {
                $dummy_recipients[ $recipient ] = $recipient;
            }
        }


        $dummy_recipients[ $message->sender_id ] = $message->sender_id;

        $message_copy->unread_count = BP_Better_Messages()->functions->get_thread_count( $message_copy->thread_id, $user_id );
        $message_copy->user_id = $message_copy->sender_id;

        $message_copy->recipients = $dummy_recipients;
        unset( $message_copy->recipients[ $message->sender_id ] );

        $recipients[] = array(
            'user_id'      => $user_id,
            'total_unread' => messages_get_unread_count( $user_id ),
            'html'         => BP_Better_Messages()->functions->render_thread( $message_copy, $user_id )
        );

        foreach ( $message->recipients as $recipient ) {
            if ( is_object( $recipient ) ) {
                $_user_id = $recipient->user_id;
            } else {
                $_user_id = $recipient;
            }

            $message_copy->recipients = $dummy_recipients;
            unset( $message_copy->recipients[ $_user_id ] );

            $message_copy->unread_count = BP_Better_Messages()->functions->get_thread_count( $message_copy->thread_id, $_user_id );

            $recipients[] = array(
                'user_id'      => $_user_id,
                'total_unread' => messages_get_unread_count( $_user_id ),
                'html'         => BP_Better_Messages()->functions->render_thread( $message_copy, $_user_id )
            );
        }

        $data = array(
            'site_id'    => $this->site_id,
            'from'       => $user_id,
            'recipients' => $recipients,
            'message'    => array(
                'thread_id'    => $message->thread_id,
                'id'           => $message->id,
                'date'         => $message->date_sent,
                'message'      => BP_Better_Messages()->functions->format_message( $message->message, $message->id ),
                'content_site' => BP_Better_Messages()->functions->format_message( $message->message, $message->id, 'site' ),
                'avatar'       => get_avatar( $message->sender_id, 40 ),
                'link'         => bp_core_get_userlink( $message->sender_id, false, true ),
                'name'         => bp_core_get_user_displayname( $message->sender_id ),
                'subject'      => $message->subject,
                'timestamp'    => strtotime( $message->date_sent ),
                'user_id'      => $message->sender_id
            ),
            'secret_key' => sha1( $this->site_id . $this->secret_key )
        );


        wp_remote_post( 'https://realtime.wordplus.org/send', array(
            'blocking' => false,
            'body'     => $data
        ) );
    }

}

function BP_Better_Messages_Premium()
{
    return BP_Better_Messages_Premium::instance();
}