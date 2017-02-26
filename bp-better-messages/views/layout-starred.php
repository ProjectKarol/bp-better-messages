<?php
defined( 'ABSPATH' ) || exit;
$stacks = BP_Better_Messages()->functions->get_starred_stacks();
?>
<div class="bp-messages-wrap">

    <div class="chat-header">
        <a href="<?php echo BP_Better_Messages()->functions->get_link(); ?>" class="back ajax"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
    </div>

    <?php if ( !empty( $stacks ) ) { ?>
        <div class="scroller scrollbar-inner starred">
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
                                    <li data-thread="<?php echo $stack[ 'thread_id' ]; ?>" data-time="<?php echo $message[ 'timestamp' ]; ?>" data-id="<?php echo $message[ 'id' ]; ?>">
                                    <span class="favorite <?php if ( $message[ 'stared' ] ) echo 'active'; ?>"><i
                                                class="fa" aria-hidden="true"></i></span>
                                        <?php echo $message[ 'message' ]; ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } else { ?>
        <p class="empty">
            <?php _e( 'Nothing found', 'bp-better-messages' ); ?>
        </p>
    <?php } ?>

    <script type="text/javascript">
        jQuery('.bp-better-messages-unread').text(<?php echo messages_get_unread_count( get_current_user_id() ); ?>);
    </script>

    <div class="preloader"></div>
</div>