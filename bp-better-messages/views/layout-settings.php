<?php
/**
 * Settings page
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
    <h1><?php _e( 'BP Better Messages Settings', 'bp-better-messages' ); ?></h1>
    <form action="" method="POST">
        <?php wp_nonce_field( 'bp-better-messages-settings' ); ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <?php _e( 'Refresh mechanism', 'bp-better-messages' ); ?>
                </th>
                <td>
                    <fieldset>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php _e( 'Refresh mechanism', 'bp-better-messages' ); ?></span></legend>
                            <label><input type="radio" name="mechanism"
                                          value="ajax" <?php checked( $this->settings[ 'mechanism' ], 'ajax' ); ?>> <?php _e( 'AJAX', 'bp-better-messages' ); ?>
                            </label><br>
                            <label><input type="radio" name="mechanism"
                                          value="websocket" <?php checked( $this->settings[ 'mechanism' ], 'websocket' ); ?>> <?php _e( 'WebSocket', 'bp-better-messages' ); ?>
                            </label>
                        </fieldset>
                    </fieldset>
                </td>
            </tr>

            <tr valign="top" class="websocket"
                style="<?php if ( $this->settings[ 'mechanism' ] == 'ajax' ) echo 'display:none;'; ?>">
                <th scope="row" valign="top">
                    <?php _e( 'License Key', 'bp-better-messages' ); ?>
                    <p style="font-size: 10px;"><?php _e( 'Enter your license key', 'bp-better-messages' ); ?></p>
                </th>
                <td>
                    <input name="license_key" type="text" class="regular-text" value="<?php esc_attr_e( $this->settings[ 'license_key' ] ); ?>"/>
                </td>
            </tr>
            <?php if ( !empty( $this->settings[ 'license_key' ] ) ) { ?>
                <tr valign="top" class="websocket" style="<?php if ( $this->settings[ 'mechanism' ] == 'ajax' ) echo 'display:none;'; ?>">
                    <th scope="row" valign="top">
                        <?php _e( 'Activate License', 'bp-better-messages' ); ?>
                    </th>
                    <td>
                        <?php if ( isset( $this->settings[ 'license_status' ] ) && $this->settings[ 'license_status' ] !== false && $this->settings[ 'license_status' ] == 'valid' ) { ?>
                            <span style="color:green;"><?php _e( 'active', 'bp-better-messages' ); ?></span>
                            <input type="submit" class="button-secondary" name="license_deactivate" value="<?php _e( 'Deactivate License', 'bp-better-messages' ); ?>"/>
                        <?php } else { ?>
                            <input type="submit" class="button-secondary" name="license_activate" value="<?php _e( 'Activate License', 'bp-better-messages' ); ?>"/>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            <tr class="ajax"
                style="<?php if ( $this->settings[ 'mechanism' ] == 'websocket' ) echo 'display:none;'; ?>">
                <th scope="row">
                    <?php _e( 'Thread Refresh Interval', 'bp-better-messages' ); ?>
                    <p style="font-size: 10px;"><?php _e( 'Ajax check interval on open thread', 'bp-user-reviews' ); ?></p>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e( 'Thread Refresh Interval', 'bp-better-messages' ); ?></span></legend>
                        <label><input type="number" name="thread_interval" value="<?php echo esc_attr( $this->settings[ 'thread_interval' ] ); ?>"></label>
                    </fieldset>
                </td>
            </tr>

            <tr class="ajax"
                style="<?php if ( $this->settings[ 'mechanism' ] == 'websocket' ) echo 'display:none;'; ?>">
                <th scope="row">
                    <?php _e( 'Site Refresh Interval', 'bp-better-messages' ); ?>
                    <p style="font-size: 10px;"><?php _e( 'Ajax check interval on other sites pages', 'bp-user-reviews' ); ?></p>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e( 'Thread Refresh Interval', 'bp-better-messages' ); ?></span></legend>
                        <label><input type="number" name="site_interval" value="<?php echo esc_attr( $this->settings[ 'site_interval' ] ); ?>"></label>
                    </fieldset>
                </td>
            </tr>

            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="save" id="submit" class="button button-primary"
                   value="<?php _e( 'Save Changes', 'bp-better-messages' ); ?>">
        </p>
    </form>
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('input[name="mechanism"]').change(function () {
            var mechanism = $('input[name="mechanism"]:checked').val();

            $('.ajax, .websocket').hide();
            $('.' + mechanism).show();
        });
    });
</script>