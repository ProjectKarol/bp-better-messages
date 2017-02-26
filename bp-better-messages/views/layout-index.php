<?php
defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();
$threads = BP_Better_Messages()->functions->get_threads( $user_id );
$favorited = BP_Better_Messages()->functions->get_starred_count();
?>

<div class="bp-messages-wrap">
    <div class="chat-header">
        <a href="<?php echo add_query_arg( 'new-message', '' ); ?>" class="new-message ajax" title="<?php _e( 'New Thread', 'bp-better-messages' ); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a>
        <a href="<?php echo add_query_arg( 'starred', '' ); ?>" class="starred-messages ajax" title="<?php _e( 'Starred', 'bp-better-messages' ); ?>"><i class="fa fa-star" aria-hidden="true"></i> <?php echo $favorited; ?>
        </a>
    </div>
    <?php if ( !empty( $threads ) ) { ?>
        <div class="scroller scrollbar-inner">
            <div class="threads-list">
                <?php foreach ( $threads as $thread ) {
                    echo BP_Better_Messages()->functions->render_thread( $thread );
                } ?>
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