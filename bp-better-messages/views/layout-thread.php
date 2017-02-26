<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;
$participants = BP_Better_Messages()->functions->get_participants( $thread_id );
$stacks = BP_Better_Messages()->functions->get_stacks( $thread_id );
?>
<div class="bp-messages-wrap">

    <div class="chat-header">
        <a href="<?php echo BP_Better_Messages()->functions->get_link(); ?>" class="back ajax"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
        <?php echo implode( ', ', $participants[ 'links' ] ); ?>
    </div>

    <div class="scroller scrollbar-inner thread"
         data-users="<?php echo implode( ',', array_keys( $participants[ 'names' ] ) ); ?>"
         data-id="<?php echo $thread_id; ?>">
        <div class="list">
            <?php foreach ( $stacks as $stack ) { ?>
                <div class="messages-stack" data-user-id="<?php echo $stack[ 'user_id' ]; ?>">
                    <div class="pic"><?php echo get_avatar( $stack[ 'user_id' ], 40 ); ?></div>
                    <div class="content">
                        <div class="info">
                            <div class="name">
                                <a href="<?php echo bp_core_get_userlink( $stack[ 'user_id' ], false, true ); ?>"><?php echo $stack[ 'user' ]->display_name; ?></a>
                            </div>
                            <div class="time" data-livestamp="<?php echo $stack[ 'messages' ][ count( $stack[ 'messages' ] ) - 1 ][ 'timestamp' ]; ?>"></div>
                        </div>
                        <ul class="messages-list">
                            <?php foreach ( $stack[ 'messages' ] as $message ) { ?>
                                <li data-time="<?php echo $message[ 'timestamp' ]; ?>" data-id="<?php echo $message[ 'id' ]; ?>">
                                    <span class="favorite <?php if ( $message[ 'stared' ] ) echo 'active'; ?>"><i class="fa" aria-hidden="true"></i></span>
                                    <?php echo $message[ 'message' ]; ?>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <span class="writing" style="display: none"></span>

    <div class="reply">
        <form action="" id="reply" method="POST">
            <div class="message">
                <textarea placeholder="<?php esc_attr_e( "Write your message", 'bp-better-messages' ); ?>" name="message" autocomplete="off"></textarea>
            </div>
            <div class="send">
                        <!--Poczatek przerobek-->
<?php   if (  !is_super_admin() && (pmpro_hasMembershipLevel(array('1','2','3','4'))) ) :  ?>
     <a href="#"> <button type="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button></a>
           
<?php else : ?>
    <a href="<?php echo get_site_url(); ?>/konto-czlonkowskie/poziomy-czlonkostwa/"><btn  ><i class="fa fa-paper-plane fa-2x" aria-hidden="true"></i></bt></a>
<?php endif; ?>
  <!--Koniec przrobek-->
               
            </div>
            <input type="hidden" name="action" value="bp_messages_send_message">
            <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
            <?php wp_nonce_field( 'sendMessage_' . $thread_id ); ?>
        </form>

        <span class="clearfix"></span>

        <?php do_action( 'bp_messages_after_reply_form', $thread_id ); ?>

    </div>
    <script type="text/javascript">
        var participants = <?php echo json_encode( $participants[ 'names' ] ); ?>;
        jQuery('.bp-better-messages-unread').text(<?php echo messages_get_unread_count(); ?>);
    </script>

    <div class="preloader"></div>
</div>