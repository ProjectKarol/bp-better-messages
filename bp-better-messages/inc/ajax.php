<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Ajax' ) ):

    class BP_Better_Messages_Ajax
    {

        public static function instance()
        {

            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Ajax();
            }

            return $instance;
        }

        public function __construct()
        {
            /**
             * Ajax checker actions
             */
            add_action( 'wp_ajax_bp_messages_thread_check_new', array( $this, 'thread_check_new' ) );
            add_action( 'wp_ajax_bp_messages_check_new', array( $this, 'check_new' ) );


            /**
             * New thread actions
             */
            add_action( 'wp_ajax_bp_messages_new_thread', array( $this, 'new_thread' ) );
            add_action( 'wp_ajax_bp_messages_send_message', array( $this, 'send_message' ) );
            add_action( 'wp_ajax_bp_messages_autocomplete', array( $this, 'bp_messages_autocomplete_results' ) );

            /**
             * Thread actions
             */
            add_action( 'wp_ajax_bp_messages_favorite', array( $this, 'favorite' ) );
            add_action( 'wp_ajax_bp_messages_delete_thread', array( $this, 'delete_thread' ) );
            add_action( 'wp_ajax_bp_messages_un_delete_thread', array( $this, 'un_delete_thread' ) );


            add_action( 'wp_ajax_bp_messages_last_activity_refresh', array( $this, 'last_activity_refresh' ) );

        }

        public function last_activity_refresh()
        {
            $user_id = get_current_user_id();
            bp_update_user_last_activity( $user_id );
            exit;
        }

        public function thread_check_new()
        {
            global $wpdb;

            $user_id = get_current_user_id();
            $bp = buddypress();

            $response = array();

            $last_check = date( "Y-m-d H:i:s", 0 );

            if ( isset( $_POST[ 'last_check' ] ) ) {
                $last_check = date( "Y-m-d H:i:s", intval( $_POST[ 'last_check' ] ) );
            }

            $last_message = date( "Y-m-d H:i:s", intval( $_POST[ 'last_message' ] ) );
            $thread_id = intval( $_POST[ 'thread_id' ] );

            if ( !BP_Messages_Thread::check_access( $thread_id ) ) die();

            setcookie( 'bp-messages-last-check', time(), time() + ( 86400 * 31 ), '/' );

            $messages = $wpdb->get_results( $wpdb->prepare( "
            SELECT id, sender_id as user_id, subject, message as content, date_sent as date
            FROM  `{$wpdb->base_prefix}bp_messages_messages` 
            WHERE `thread_id`  = %d
            AND   `date_sent`  > %s
            ORDER BY `date_sent` ASC
            ", $thread_id, $last_message ) );

            foreach ( $messages as $index => $message ) {
                $user = get_userdata( $message->user_id );
                $messages[ $index ]->message = BP_Better_Messages()->functions->format_message( $message->content, $message->id );
                $messages[ $index ]->timestamp = strtotime( $message->date );
                $messages[ $index ]->avatar = get_avatar( $message->user_id, 40 );
                $messages[ $index ]->name = $user->display_name;
                $messages[ $index ]->link = bp_core_get_userlink( $message->user_id, false, true );
            }

            $response[ 'messages' ] = $messages;

            $threads = $wpdb->get_results( "
                SELECT thread_id, unread_count 
                FROM   {$bp->messages->table_name_recipients}
                WHERE  `user_id`      = {$user_id}
                AND    `is_deleted`   = 0
                AND    `unread_count` > 0
                AND    `thread_id`    != {$thread_id}
            " );

            foreach ( $threads as $index => $thread ) {
                $recipients = array();
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread->thread_id ) );

                foreach ( (array)$results as $recipient ) {
                    if ( get_current_user_id() == $recipient->user_id ) continue;
                    $recipients[] = $recipient->user_id;
                }

                $message = $wpdb->get_row( $wpdb->prepare( "
                SELECT id, sender_id as user_id, subject, message as content, date_sent
                FROM  `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id`  = %d
                AND   `sender_id`  != %d
                AND   `date_sent`  >= %s
                ORDER BY `date_sent` DESC 
                LIMIT 0, 1", $thread->thread_id, $user_id, $last_check ) );

                if ( !$message ) {
                    unset( $threads[ $index ] );
                    continue;
                }

                $user = get_userdata( $message->user_id );
                $threads[ $index ]->subject = $message->subject;
                $threads[ $index ]->message = BP_Better_Messages()->functions->format_message( $message->content, $message->id, 'site' );
                $threads[ $index ]->name = $user->display_name;
                $threads[ $index ]->date_sent = $message->date_sent;
                $threads[ $index ]->avatar = bp_core_fetch_avatar( 'type=full&html=false&item_id=' . $user->ID );
                $threads[ $index ]->user_id = intval( $user->ID );
                $threads[ $index ]->unread_count = intval( $threads[ $index ]->unread_count );
                $threads[ $index ]->recipients = $recipients;
                $threads[ $index ]->html = BP_Better_Messages()->functions->render_thread( $threads[ $index ] );
            }

            usort( $threads, function ( $item1, $item2 ) {
                if ( strtotime( $item1->message->date_sent ) == strtotime( $item2->message->date_sent ) ) return 0;

                return ( strtotime( $item1->message->date_sent ) < strtotime( $item2->message->date_sent ) ) ? 1 : -1;
            } );

            $response[ 'threads' ] = $threads;

            messages_mark_thread_read( $thread_id );

            $response[ 'total_unread' ] = messages_get_unread_count( $user_id );

            wp_send_json( $response );

            exit;
        }

        public function check_new()
        {
            global $wpdb;

            $user_id = get_current_user_id();
            $bp = buddypress();

            $last_check = date( "Y-m-d H:i:s", 0 );

            if ( isset( $_POST[ 'last_check' ] ) ) {
                $last_check = date( "Y-m-d H:i:s", absint( $_POST[ 'last_check' ] ) );
            }

            setcookie( 'bp-messages-last-check', time(), time() + ( 86400 * 31 ), '/' );

            $threads = $wpdb->get_results( $wpdb->prepare( "
                SELECT thread_id, unread_count 
                FROM   {$bp->messages->table_name_recipients}
                WHERE  `user_id`      = %d
                AND    `is_deleted`   = 0
                AND    `unread_count` > 0
            ", $user_id ) );

            foreach ( $threads as $index => $thread ) {
                $recipients = array();
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread->thread_id ) );

                foreach ( (array)$results as $recipient ) {
                    if ( get_current_user_id() == $recipient->user_id ) continue;
                    $recipients[] = $recipient->user_id;
                }

                $message = $wpdb->get_row( $wpdb->prepare( "
                SELECT id, sender_id as user_id, subject, message as content, date_sent
                FROM  `{$wpdb->base_prefix}bp_messages_messages` 
                WHERE `thread_id`  = %d
                AND   `sender_id`  != %d
                AND   `date_sent`  >= %s
                ORDER BY `id` DESC 
                LIMIT 0, 1", $thread->thread_id, $user_id, $last_check ) );

                if ( !$message ) {
                    unset( $threads[ $index ] );
                    continue;
                }

                $user = get_userdata( $message->user_id );
                $threads[ $index ]->subject = $message->subject;
                $threads[ $index ]->message = BP_Better_Messages()->functions->format_message( $message->content, $message->id, 'site' );
                $threads[ $index ]->name = $user->display_name;
                $threads[ $index ]->date_sent = $message->date_sent;
                $threads[ $index ]->avatar = bp_core_fetch_avatar( 'type=full&html=false&item_id=' . $user->ID );
                $threads[ $index ]->user_id = intval( $user->ID );
                $threads[ $index ]->unread_count = intval( $threads[ $index ]->unread_count );
                $threads[ $index ]->recipients = $recipients;
                $threads[ $index ]->html = BP_Better_Messages()->functions->render_thread( $threads[ $index ] );
            }

            usort( $threads, function ( $item1, $item2 ) {
                if ( strtotime( $item1->message->date_sent ) == strtotime( $item2->message->date_sent ) ) return 0;

                return ( strtotime( $item1->message->date_sent ) < strtotime( $item2->message->date_sent ) ) ? 1 : -1;
            } );

            $response[ 'threads' ] = $threads;

            $response[ 'total_unread' ] = messages_get_unread_count( $user_id );

            wp_send_json( $response );

            exit;
        }

        public function favorite()
        {

            $message_id = absint( $_POST[ 'message_id' ] );
            $thread_id = absint( $_POST[ 'thread_id' ] );
            $type = sanitize_text_field( $_POST[ 'type' ] );

            $result = bp_messages_star_set_action( array(
                'action'     => $type,
                'message_id' => $message_id,
                'thread_id'  => $thread_id
            ) );

            wp_send_json( $result );

            exit;
        }

        public function send_message()
        {

            $thread_id = intval( $_POST[ 'thread_id' ] );

            if ( !wp_verify_nonce( $_POST[ '_wpnonce' ], 'sendMessage_' . $thread_id ) ) {
                $errors[] = __( 'Security error while sending message', 'bp-better-messages' );

                status_header( 403 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            } else {

                status_header( 200 );

                $sent = messages_new_message( array(
                    'content'    => esc_textarea( $_POST[ 'message' ] ),
                    'thread_id'  => $thread_id,
                    'error_type' => 'wp_error'
                ) );

                if ( !is_wp_error( $sent ) ) {

                    wp_send_json( array(
                        'result'   => $sent,
                        'redirect' => false
                    ) );

                } else {
                    $errors[] = $sent->get_error_message();

                    wp_send_json( array(
                        'result'   => false,
                        'errors'   => $errors,
                        'redirect' => false
                    ) );
                }

            }

            exit;
        }

        public function new_thread()
        {
            $errors = array();

            if ( !wp_verify_nonce( $_POST[ '_wpnonce' ], 'newThread' ) ) {
                $errors[] = __( 'Security error while starting new thread', 'bp-better-messages' );

                status_header( 403 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );


            } else {
                status_header( 200 );

                $args = array(
                    'subject'    => sanitize_text_field( $_POST[ 'subject' ] ),
                    'content'    => esc_textarea( $_POST[ 'message' ] ),
                    'error_type' => 'wp_error'
                );

                if ( isset( $_POST[ 'recipients' ] ) && is_array( $_POST[ 'recipients' ] ) && !empty( $_POST[ 'recipients' ] ) ) {
                    foreach ( $_POST[ 'recipients' ] as $one ) {
                        $args[ 'recipients' ][] = sanitize_text_field( $one );
                    }
                }

                $sent = messages_new_message( $args );

                if ( !is_wp_error( $sent ) ) {

                    status_header( 200 );

                    wp_send_json( array(
                        'result'    => true,
                        'errors'    => $errors,
                        'thread_id' => $sent
                    ) );

                } else {
                    $errors[] = $sent->get_error_message();

                    wp_send_json( array(
                        'result'   => false,
                        'errors'   => $errors,
                        'redirect' => false
                    ) );
                }
            }

            exit;
        }

        /**
         * AJAX handler for autocomplete.
         *
         * Displays friends only, unless BP_MESSAGES_AUTOCOMPLETE_ALL is defined.
         *
         * @since 1.0.0
         */
        public function bp_messages_autocomplete_results()
        {

            /**
             * Filters the max results default value for ajax messages autocomplete results.
             *
             * @since 1.0.0
             *
             * @param int $value Max results for autocomplete. Default 10.
             */
            $limit = isset( $_GET[ 'limit' ] ) ? absint( $_GET[ 'limit' ] ) : (int)apply_filters( 'bp_autocomplete_max_results', 10 );
            $term = isset( $_GET[ 'q' ] ) ? sanitize_text_field( $_GET[ 'q' ] ) : '';

            // Include everyone in the autocomplete, or just friends?
            if ( bp_is_current_component( BP_BETTER_MESSAGES_SLUG ) ) {
                $only_friends = ( buddypress()->messages->autocomplete_all === false );
            } else {
                $only_friends = true;
            }

            $suggestions = bp_core_get_suggestions( array(
                'limit'        => $limit,
                'only_friends' => $only_friends,
                'term'         => $term,
                'type'         => 'members',
            ) );

            if ( $suggestions && !is_wp_error( $suggestions ) ) {
                $response = array();

                foreach ( $suggestions as $index => $suggestion ) {
                    $response[] = array(
                        'id'    => $suggestion->ID,
                        'label' => $suggestion->name,
                        'value' => $suggestion->ID
                    );
                }

                wp_send_json( $response );
            }

            exit;
        }

        public function delete_thread()
        {

            $errors = array();

            $thread_id = intval( $_POST[ 'thread_id' ] );

            if ( !wp_verify_nonce( $_POST[ 'nonce' ], 'delete_' . $thread_id ) || !BP_Messages_Thread::check_access( $thread_id ) ) {
                $errors[] = __( 'Security error while deleting thread', 'bp-better-messages' );

                status_header( 200 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            } else {

                wp_send_json( array(
                    'result'   => BP_Messages_Thread::delete( $thread_id ),
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            }

            die();
        }

        public function un_delete_thread()
        {
            global $wpdb;

            $errors = array();

            $thread_id = intval( $_POST[ 'thread_id' ] );
            $user_id = bp_loggedin_user_id();

            $has_access = (bool)$wpdb->get_var( $wpdb->prepare( "
                SELECT COUNT(*)
                FROM {$wpdb->base_prefix}bp_messages_recipients
                WHERE `thread_id`  = %d
                AND   `user_id`    = %d
                AND   `is_deleted` = 1
            ", $thread_id, $user_id ) );

            if ( !wp_verify_nonce( $_POST[ 'nonce' ], 'un_delete_' . $thread_id ) || !$has_access ) {
                $errors[] = __( 'Security error while recovering thread', 'bp-better-messages' );

                status_header( 200 );

                wp_send_json( array(
                    'result'   => false,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            } else {

                $restored = $wpdb->update( $wpdb->base_prefix . 'bp_messages_recipients', array(
                    'is_deleted' => 0
                ), array(
                    'thread_id' => $thread_id,
                    'user_id'   => $user_id
                ) );

                wp_send_json( array(
                    'result'   => $restored,
                    'errors'   => $errors,
                    'redirect' => false
                ) );

            }

            die();
        }
    }
endif;
function BP_Better_Messages_Ajax()
{
    return BP_Better_Messages_Ajax::instance();
}
