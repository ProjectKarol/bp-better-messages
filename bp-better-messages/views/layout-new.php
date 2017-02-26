<div class="bp-messages-wrap">
    <div class="chat-header">
        <a href="<?php echo BP_Better_Messages()->functions->get_link(); ?>" class="new-message ajax" title="<?php _e( 'New Thread', 'bp-better-messages' ); ?>"><i class="fa fa-times" aria-hidden="true"></i></a>
    </div>
    <div class="new-message">
        <form>
            <div>
                <label><?php _e( "Send To (Username or Friend's Name)", 'bp-better-messages' ); ?></label>
                <div id="send-to" class="input"></div>
                <span class="clearfix"></span>
            </div>
            <div>
                <label for="subject-input"><?php _e( 'Subject', 'bp-better-messages' ); ?></label>
                <input type="text" tabindex="3" name="subject" class="subject-input" id="subject-input" autocomplete="off">
                <span class="clearfix"></span>
            </div>
            <div>
                <label for="message-input"><?php _e( 'Message', 'bp-better-messages' ); ?></label>

                <textarea name="message" placeholder="<?php esc_attr_e( "Write your message", 'bp-better-messages' ); ?>" id="message-input" autocomplete="off"></textarea>
                <span class="clearfix"></span>
            </div>

            <!--Poczatek przerobek-->
<?php   if (  !is_super_admin() && (pmpro_hasMembershipLevel(array('1','2','3','4'))) ) :  ?>
    <button type="submit"><?php _e( 'Send Message', 'bp-better-messages' ); ?></button>
           
<?php else : ?>
    <a href="<?php echo get_site_url(); ?>/konto-czlonkowskie/poziomy-czlonkostwa/"><btn  ><?php _e( 'Send Message', 'bp-better-messages' ); ?></bt></a>
<?php endif; ?>
  <!--Koniec przrobek-->
            <?php if ( isset( $_GET[ 'to' ] ) && !empty( $_GET[ 'to' ] ) ) {
                echo '<input type="hidden" name="to" value="' . $_GET[ 'to' ] . '">';
            } ?>

            <input type="hidden" name="action" value="bp_messages_new_thread">
            <?php wp_nonce_field( 'newThread' ); ?>
        </form>

    </div>

    <script type="text/javascript">
        jQuery('.bp-better-messages-unread').text(<?php echo messages_get_unread_count( get_current_user_id() ); ?>);
    </script>

    <div class="preloader"></div>
</div>