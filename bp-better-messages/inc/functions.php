<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Functions' ) ):

    class BP_Better_Messages_Functions
    {

        public static function instance()
        {
            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Functions();
            }

            return $instance;
        }

        public function get_threads( $user_id = 0 )
        {
            global $wpdb, $bp;

            $threads = $wpdb->get_results( $wpdb->prepare( "
                SELECT thread_id, unread_count
                FROM   {$bp->messages->table_name_recipients}
                WHERE  `user_id` = %d
                AND    `is_deleted` = 0
            ", $user_id ) );

            foreach ( $threads as $index => $thread ) {
                $recipients = array();
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread->thread_id ) );

                foreach ( (array)$results as $recipient ) {
                    if ( get_current_user_id() == $recipient->user_id ) continue;
                    $recipients[] = $recipient->user_id;
                }

                $threads[ $index ]->recipients = $recipients;

                $last_message = $wpdb->get_row( $wpdb->prepare( "
                    SELECT id, sender_id as user_id, subject, message, date_sent
                    FROM  `{$wpdb->base_prefix}bp_messages_messages` 
                    WHERE `thread_id` = %d
                    ORDER BY `date_sent` DESC 
                    LIMIT 0, 1
                ", $thread->thread_id ) );

                $user = get_userdata( $last_message->user_id );
                $threads[ $index ]->subject = $last_message->subject;
                $threads[ $index ]->message = BP_Better_Messages()->functions->format_message( $last_message->message, $last_message->id, 'site' );
                $threads[ $index ]->name = $user->display_name;
                $threads[ $index ]->date_sent = $last_message->date_sent;
                $threads[ $index ]->avatar = bp_core_fetch_avatar( 'type=full&html=false&item_id=' . $user->ID );
                $threads[ $index ]->user_id = intval( $user->ID );
                $threads[ $index ]->unread_count = intval( $threads[ $index ]->unread_count );
                $threads[ $index ]->recipients = $recipients;
                $threads[ $index ]->html = BP_Better_Messages()->functions->render_thread( $threads[ $index ] );
            }

            usort( $threads, function ( $item1, $item2 ) {
                if ( strtotime( $item1->date_sent ) == strtotime( $item2->date_sent ) ) return 0;

                return ( strtotime( $item1->date_sent ) < strtotime( $item2->date_sent ) ) ? 1 : -1;
            } );

            return $threads;
        }

        public function get_stacks( $thread_id )
        {
            global $wpdb;

            if ( !BP_Messages_Thread::is_valid( $thread_id ) ) return array();

            $stacks = array();

            $messages = $wpdb->get_results( $wpdb->prepare( "
            SELECT id, thread_id, sender_id, message, date_sent
            FROM  {$wpdb->base_prefix}bp_messages_messages
            WHERE `thread_id` = %d
            ORDER BY `date_sent` ASC
            ", $thread_id ) );

            $lastUser = 0;
            foreach ( $messages as $index => $message ) {
                if ( $message->sender_id != $lastUser ) {
                    $lastUser = $message->sender_id;

                    $stacks[] = array(
                        'id'        => $message->id,
                        'user_id'   => $message->sender_id,
                        'user'      => get_userdata( $message->sender_id ),
                        'thread_id' => $message->thread_id,
                        'messages'  => array(
                            array(
                                'id'        => $message->id,
                                'message'   => self::format_message( $message->message, $message->id ),
                                'date'      => $message->date_sent,
                                'timestamp' => strtotime( $message->date_sent ),
                                'stared'    => bp_messages_is_message_starred( $message->id )
                            )
                        )
                    );
                } else {
                    $stacks[ count( $stacks ) - 1 ][ 'messages' ][] = array(
                        'id'        => $message->id,
                        'message'   => self::format_message( $message->message, $message->id ),
                        'date'      => $message->date_sent,
                        'timestamp' => strtotime( $message->date_sent ),
                        'stared'    => bp_messages_is_message_starred( $message->id )
                    );
                }
            }

            return $stacks;

        }

        public function get_participants( $thread_id )
        {

            $thread = new BP_Messages_Thread();
            $recipients = $thread->get_recipients( $thread_id );

            $participants = array(
                'links' => array(),
                'names' => array()
            );

            foreach ( $recipients as $recipient ) {
                if ( $recipient->user_id == get_current_user_id() ) continue;
                $user = get_userdata( $recipient->user_id );
                $participants[ 'links' ][] = '<a href="' . bp_core_get_userlink( $recipient->user_id, false, true ) . '" class="user">' . get_avatar( $recipient->user_id, 20 ) . $user->display_name . '</a>';
                $participants[ 'names' ][ $recipient->user_id ] = $user->display_name;
            }

            return $participants;

        }

        public function get_link( $user_id = false )
        {
            if ( $user_id == false ) {
                $user_id = get_current_user_id();
            }

            return bp_core_get_user_domain( $user_id ) . 'bp-messages/';
        }

        public function get_starred_count()
        {
            global $wpdb;
            $user_id = get_current_user_id();

            return $wpdb->get_var( "
                SELECT
                  COUNT({$wpdb->base_prefix}bp_messages_messages.id) AS count
                FROM {$wpdb->base_prefix}bp_messages_meta
                  INNER JOIN {$wpdb->base_prefix}bp_messages_messages
                    ON {$wpdb->base_prefix}bp_messages_meta.message_id = {$wpdb->base_prefix}bp_messages_messages.id
                  INNER JOIN {$wpdb->base_prefix}bp_messages_recipients
                    ON {$wpdb->base_prefix}bp_messages_recipients.thread_id = {$wpdb->base_prefix}bp_messages_messages.thread_id
                WHERE {$wpdb->base_prefix}bp_messages_meta.meta_key = 'starred_by_user'
                AND {$wpdb->base_prefix}bp_messages_meta.meta_value = $user_id
                AND {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0
                AND {$wpdb->base_prefix}bp_messages_recipients.user_id = $user_id
            " );
        }

        public function get_starred_stacks()
        {
            global $wpdb;

            $user_id = get_current_user_id();
            $messages = $wpdb->get_results( $wpdb->prepare( "
                SELECT
                  {$wpdb->base_prefix}bp_messages_messages.*
                FROM {$wpdb->base_prefix}bp_messages_meta
                  INNER JOIN {$wpdb->base_prefix}bp_messages_messages
                    ON {$wpdb->base_prefix}bp_messages_meta.message_id = {$wpdb->base_prefix}bp_messages_messages.id
                  INNER JOIN {$wpdb->base_prefix}bp_messages_recipients
                    ON {$wpdb->base_prefix}bp_messages_recipients.thread_id = {$wpdb->base_prefix}bp_messages_messages.thread_id
                WHERE {$wpdb->base_prefix}bp_messages_meta.meta_key = 'starred_by_user'
                AND {$wpdb->base_prefix}bp_messages_meta.meta_value = %d
                AND {$wpdb->base_prefix}bp_messages_recipients.is_deleted = 0
                AND {$wpdb->base_prefix}bp_messages_recipients.user_id = %d
            ", $user_id, $user_id ) );

            $stacks = array();

            $lastUser = 0;
            foreach ( $messages as $index => $message ) {
                if ( $message->sender_id != $lastUser ) {
                    $lastUser = $message->sender_id;

                    $stacks[] = array(
                        'id'        => $message->id,
                        'user_id'   => $message->sender_id,
                        'user'      => get_userdata( $message->sender_id ),
                        'thread_id' => $message->thread_id,
                        'messages'  => array(
                            array(
                                'id'        => $message->id,
                                'message'   => self::format_message( $message->message, $message->id ),
                                'date'      => $message->date_sent,
                                'timestamp' => strtotime( $message->date_sent ),
                                'stared'    => bp_messages_is_message_starred( $message->id )
                            )
                        )
                    );
                } else {
                    $stacks[ count( $stacks ) - 1 ][ 'messages' ][] = array(
                        'id'        => $message->id,
                        'message'   => self::format_message( $message->message, $message->id ),
                        'date'      => $message->date_sent,
                        'timestamp' => strtotime( $message->date_sent ),
                        'stared'    => bp_messages_is_message_starred( $message->id )
                    );
                }
            }

            return $stacks;
        }

        public function format_message( $message = '', $message_id = 0, $context = 'stack' )
        {
            global $processedUrls;

            if ( !isset( $processedUrls ) ) $processedUrls = array();

            $message = apply_filters( 'bp_better_messages_pre_format_message', $message, $message_id, $context );

            // Removing slashes
            $message = wp_unslash( $message );


            if ( $context == 'site' ) {
                $message = mb_strimwidth( $message, 0, 50, '...' );
            } else {
                // New line to html <br>
                $message = nl2br( $message );
            }

            //Removing new emojies, while we dont support them yet
            $message = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $message );

            $message = apply_filters( 'bp_better_messages_after_format_message', $message, $message_id, $context );

            if ( isset( $processedUrls[ $message_id ] ) && !empty( $processedUrls[ $message_id ] ) ) {
                foreach ( $processedUrls[ $message_id ] as $index => $link ) {
                    $message = str_replace( '%%link_' . ( $index + 1 ) . '%%', $link, $message );
                }
            }

            return $this->clean_string( $message );
        }

        public function get_thread_count( $thread_id, $user_id )
        {
            global $wpdb, $bp;

            return $wpdb->get_var( $wpdb->prepare( "
            SELECT unread_count 
            FROM   {$bp->messages->table_name_recipients}
            WHERE  `thread_id` = %d
            AND    `user_id`   = %d
            ", $thread_id, $user_id ) );
        }

        public function render_thread( $thread, $user_id = false )
        {
            if ( $user_id == false ) {
                $user_id = get_current_user_id();
            }

            ob_start();
            ?>
            <div class="thread <?php if ( $thread->unread_count > 0 ) echo 'unread'; ?>"
                 data-id="<?php echo $thread->thread_id; ?>"
                 data-href="<?php echo add_query_arg( 'thread_id', $thread->thread_id, BP_Better_Messages()->functions->get_link( $user_id ) ); ?>">
                <div class="pic <?php if ( count( $thread->recipients ) > 1 ) echo 'group'; ?>">
                    <?php
                    if ( count( $thread->recipients ) > 1 ) {
                        $i = 0;
                        foreach ( $thread->recipients as $recipient ) {
                            $i++;
                            echo '<a href="' . bp_core_get_userlink( $recipient, false, true ) . '">' . get_avatar( $recipient, 25 ) . '</a>';
                            if ( $i == 4 ) break;
                        }
                        if ( $i < 4 ) echo get_avatar( $user_id, 25 );
                    } else {
                        $link = bp_core_get_userlink( array_values( $thread->recipients )[ 0 ], false, true );
                        $avatar = get_avatar( array_values( $thread->recipients )[ 0 ], 50 );
                        echo '<a href="' . $link . '">' . $avatar . '</a>';
                    } ?>
                </div>
                <div class="info">
                    <?php
                    if ( count( $thread->recipients ) == 1 ) {
                        $name = bp_core_get_user_displayname( array_values( $thread->recipients )[ 0 ] ); ?>
                        <h4 class="name"><?php echo $name; ?></h4>
                    <?php } ?>
                    <h4><?php echo $thread->subject; ?></h4>
                    <p><?php
                        if ( ( $thread->user_id !== $user_id ) && ( count( $thread->recipients ) > 1 ) )
                            echo get_avatar( $thread->user_id, 20 );
                        echo $thread->message; ?>
                    </p>
                </div>
                <div class="time">
                    <span class="delete" data-nonce="<?php echo wp_create_nonce( 'delete_' . $thread->thread_id ); ?>"><i class="fa fa-times" aria-hidden="true"></i></span>
                    <span data-livestamp="<?php echo strtotime( $thread->date_sent ); ?>"></span>
                    <span class="unread-count"><?php if ( $thread->unread_count > 0 ) echo '+' . $thread->unread_count; ?></span>
                </div>

                <div class="deleted">
                    <?php _e( 'Thread was deleted.', 'bp-better-messages' ); ?>
                    <a class="undelete" data-nonce="<?php echo wp_create_nonce( 'un_delete_' . $thread->thread_id ); ?>" href="#"><?php _e( 'Recover?', 'bp-better-messages' ); ?></a>
                </div>
            </div>
            <?php
            return $this->clean_string( ob_get_clean() );
        }

        public function clean_string( $string )
        {
            $string = str_replace( PHP_EOL, ' ', $string );
            $string = preg_replace( '/[\r\n]+/', "\n", $string );
            $string = preg_replace( '/[ \t]+/', ' ', $string );

            return trim($string);
        }

        public function clean_site_url( $url )
        {

            $url = strtolower( $url );

            $url = str_replace( '://www.', '://', $url );

            $url = str_replace( array( 'http://', 'https://' ), '', $url );

            $port = parse_url( $url, PHP_URL_PORT );

            if ( $port ) {
                // strip port number
                $url = str_replace( ':' . $port, '', $url );
            }

            return sanitize_text_field( $url );
        }
    }

endif;

/**
 * @return BP_Better_Messages_Functions instance | null
 */
function BP_Better_Messages_Functions()
{
    return BP_Better_Messages_Functions::instance();
}