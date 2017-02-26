<?php
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Better_Messages_Files' ) ):

    class BP_Better_Messages_Files
    {

        public static function instance()
        {
            static $instance = null;

            if ( null === $instance ) {
                $instance = new BP_Better_Messages_Files();
            }

            return $instance;
        }


        public function __construct()
        {
            add_action( 'wp_ajax_bp_better_messages_attach_file', array( $this, 'handle_upload' ) );
            add_action( 'wp_ajax_bp_better_messages_deattach_file', array( $this, 'handle_delete' ) );

            add_action( 'bp_messages_after_reply_form', array( $this, 'upload_form' ), 10, 1 );

            /**
             * Modify message before save
             */
            add_action( 'messages_message_before_save', array( $this, 'add_files_to_message' ) );
            add_action( 'messages_message_after_save', array( $this, 'add_files_to_message_meta' ) );

            add_filter( 'bp_better_messages_pre_format_message', array( $this, 'nice_files' ), 90, 3 );
            add_filter( 'bp_after_messages_new_message_parse_args', array( $this, 'empty_message_fix' ), 10, 1 );
        }

        public function empty_message_fix( $args )
        {
            if ( !empty( $args[ 'content' ] ) ) return $args;
            if ( !empty( $this->get_unused_attachments( $args[ 'thread_id' ], $args[ 'sender_id' ] ) ) ) $args[ 'content' ] = ' ';

            return $args;
        }

        public function nice_files( $message, $message_id, $context )
        {
            if ( $context !== 'stack' ) return $message;

            global $processedUrls;

            $attachments = bp_messages_get_meta( $message_id, 'attachments', true );

            if ( !empty( $attachments ) ) {

                $images = array();
                $videos = array();
                $audios = array();
                $files = array();

                foreach ( $attachments as $attachment_id => $url ) {
                    $_attachment = get_post( $attachment_id );
                    if ( !$_attachment ) {
                        continue;
                    } else if ( strpos( $_attachment->post_mime_type, 'image/' ) === 0 ) {
                        $images[$attachment_id] = array(
                                'url' => $url,
                                'thumb' => wp_get_attachment_image_url( $attachment_id, array(200, 200) )
                        );
                        $message = str_replace( array( $url . "\n", "\n" . $url, $url ), '', $message );
                    } else if (strpos( $_attachment->post_mime_type, 'video/') === 0 ) {
                        $videos[$attachment_id] = $url;
                        $message = str_replace( array( $url . "\n", "\n" . $url, $url ), '', $message );
                    }else if (strpos( $_attachment->post_mime_type, 'audio/') === 0 ) {
                        $audios[$attachment_id] = $url;
                        $message = str_replace( array( $url . "\n", "\n" . $url, $url ), '', $message );
                    } else {
                        $files[$attachment_id] = $url;
                        $message = str_replace( array( $url . "\n", "\n" . $url, $url ), '', $message );
                    }
                }


                if ( !empty( $videos ) ) {
                    $message .= '<div class="videos">';
                    foreach ( $videos as $video ) {
                        $ext = pathinfo( $video, PATHINFO_EXTENSION );
                        $video = do_shortcode('[video '.$ext.'="'.$video.'"][/video]');
                        $video = str_replace('style="', 'style="width: 100% !important;height: 100% !important;', $video);
                        $processedUrls[ $message_id ][] = '<div class="video"><div class="video-container">' . $video . '</div></div>';
                        $message .= '%%link_' . count( $processedUrls[ $message_id ] ) . '%%';
                    }
                    $message .= '</div>';
                }

                if ( !empty( $images ) ) {
                    $message .= '<div class="images images-'. count($images) .'">';
                    foreach ( $images as $image ) {
                        $processedUrls[ $message_id ][] = '<a href="' . $image['url'] . '" target="_blank" class="image" style="background-image: url('.$image['thumb'].');"></a>';
                        $message .= '%%link_' . count( $processedUrls[ $message_id ] ) . '%%';
                    }
                    $message .= '</div>';
                }

                if ( !empty( $audios ) ) {
                    $message .= '<div class="audios">';
                    foreach ( $audios as $audio ) {
                        $ext = pathinfo( $audio, PATHINFO_EXTENSION );
                        $processedUrls[ $message_id ][] = do_shortcode('[audio '.$ext.'="'.$audio.'"]');
                        $message .= '%%link_' . count( $processedUrls[ $message_id ] ) . '%%';
                    }
                    $message .= '</div>';
                }

                if ( !empty( $files ) ) {
                    $message .= '<div class="files">';
                    foreach ( $files as $attachment_id => $file ) {
                        $path = get_attached_file( $attachment_id );
                        $size = size_format(filesize($path));
                        $ext = pathinfo( $file, PATHINFO_EXTENSION );
                        $name = basename( $file );
                        $icon = 'file-o';
                        if( in_array($ext, $this->get_archive_extensions())) $icon = 'file-archive-o';
                        if( in_array($ext, $this->get_text_extensions())) $icon = 'file-text-o';
                        if( $ext == 'pdf' ) $icon = 'file-pdf-o';
                        if( strpos($ext, 'doc') === 0 ) $icon = 'file-word-o';
                        if( strpos($ext, 'xls') === 0 ) $icon = 'file-excel-o';

                        $processedUrls[ $message_id ][] = '<a href="' . $file . '" target="_blank" class="file file-' . $ext . '"><i class="fa fa-'.$icon.'" aria-hidden="true"></i>' . $name . '<span class="size">('.$size.')</span></a>';
                        $message .= '%%link_' . count( $processedUrls[ $message_id ] ) . '%%';
                    }
                    $message .= '</div>';
                }
            }

            return $message;
        }

        public function get_archive_extensions(){
            return array(
                "7z",
                "a",
                "apk",
                "ar",
                "cab",
                "cpio",
                "deb",
                "dmg",
                "egg",
                "epub",
                "iso",
                "jar",
                "mar",
                "pea",
                "rar",
                "s7z",
                "shar",
                "tar",
                "tbz2",
                "tgz",
                "tlz",
                "war",
                "whl",
                "xpi",
                "zip",
                "zipx"
            );
        }

        public function get_text_extensions(){
            return array(
                "txt", "rtf"
            );
        }

        public function add_files_to_message( $message )
        {
            $attachments = $this->get_unused_attachments( $message->thread_id, get_current_user_id() );

            foreach ( $attachments as $attachment ) {
                $message->message .= "\n" . wp_get_attachment_url( $attachment->ID );
            }

        }

        public function add_files_to_message_meta( $message )
        {
            $attachments = $this->get_unused_attachments( $message->thread_id, get_current_user_id() );

            $attachment_meta = array();

            foreach ( $attachments as $attachment ) {
                $attachment_meta[ $attachment->ID ] = wp_get_attachment_url( $attachment->ID );
                add_post_meta( $attachment->ID, 'bp-better-messages-message-id', $message->id, true );
            }

            bp_messages_add_meta( $message->id, 'attachments', $attachment_meta, true );

        }

        public function upload_form( $thread_id )
        {
            $user_id = get_current_user_id();

            if ( !$this->user_can_upload( $user_id, $thread_id ) ) return false;

            $extensions = array();

            foreach ( array_keys( get_allowed_mime_types( $user_id ) ) as $extension ) {
                foreach ( explode( '|', $extension ) as $ext ) {
                    $extensions[] = $ext;
                }
            }

            $maxSize = wp_max_upload_size() / 1024 / 1024;

            $attachments = $this->get_unused_attachments( $thread_id, $user_id );

            $files = array();

            if ( !empty( $attachments ) ) {
                foreach ( $attachments as $attachment ) {
                    $url = wp_get_attachment_thumb_url( $attachment->ID );
                    $path = get_attached_file( $attachment->ID );

                    $files[] = array(
                        'id'   => $attachment->ID,
                        'name' => basename( $path ),
                        'size' => filesize( $path ),
                        'type' => $attachment->post_mime_type,
                        'file' => $url,
                        'url'  => $url
                    );
                }
            }
            ?>
            <form action="" class="files" method="post" enctype="multipart/form-data">
                <input type="file" name="files[]" id="files" multiple="multiple">
            </form>
            <span class="clearfix"></span>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $(window).off('paste');

                    var options = {
                        showThumbs: true,
                        changeInput: '<div class="jFiler-input-dragDrop" style="display:none;"><div class="jFiler-input-inner"><div class="jFiler-input-icon"><i class="icon-jfi-cloud-up-o"></i></div><div class="jFiler-input-text"><h3><?php esc_attr_e( 'Drag&Drop files here', 'bp-better-messages' ); ?></h3> <span style="display:inline-block; margin: 15px 0"><?php esc_attr_e( 'or', 'bp-better-messages' ); ?></span></div><a class="jFiler-input-choose-btn btn-custom blue-light"><?php esc_attr_e( 'Browse Files', 'bp-better-messages' ); ?></a></div></div>',
                        theme: "dragdropbox",
                        fileMaxSize: <?php echo (int)$maxSize; ?>,
                        extensions: <?php echo json_encode( $extensions ); ?>,
                        templates: {
                            box: '<ul class="jFiler-items-list jFiler-items-grid"></ul>',
                            item: '<li class="jFiler-item">\
                            <div class="jFiler-item-container">\
                                <div class="jFiler-item-inner">\
                                    <div class="jFiler-item-thumb">\
                                        <div class="jFiler-item-status"></div>\
                                        <div class="jFiler-item-thumb-overlay">\
    										<div class="jFiler-item-info">\
    											<div style="display:table-cell;vertical-align: middle;">\
    												<span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name}}</b></span>\
    												<span class="jFiler-item-others">{{fi-size2}}</span>\
    											</div>\
    										</div>\
    									</div>\
                                        {{fi-image}}\
                                    </div>\
                                    <div class="jFiler-item-assets jFiler-row">\
                                        <ul class="list-inline pull-left">\
                                            <li>{{fi-progressBar}}</li>\
                                        </ul>\
                                        <ul class="list-inline pull-right">\
                                            <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                        </ul>\
                                    </div>\
                                </div>\
                            </div>\
                        </li>',
                            itemAppend: '<li class="jFiler-item">\
                                <div class="jFiler-item-container">\
                                    <div class="jFiler-item-inner">\
                                        <div class="jFiler-item-thumb">\
                                            <div class="jFiler-item-status"></div>\
                                            <div class="jFiler-item-thumb-overlay">\
        										<div class="jFiler-item-info">\
        											<div style="display:table-cell;vertical-align: middle;">\
        												<span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name}}</b></span>\
        												<span class="jFiler-item-others">{{fi-size2}}</span>\
        											</div>\
        										</div>\
        									</div>\
                                            {{fi-image}}\
                                        </div>\
                                        <div class="jFiler-item-assets jFiler-row">\
                                            <ul class="list-inline pull-left">\
                                                <li><span class="jFiler-item-others">{{fi-icon}}</span></li>\
                                            </ul>\
                                            <ul class="list-inline pull-right">\
                                                <li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li>\
                                            </ul>\
                                        </div>\
                                    </div>\
                                </div>\
                            </li>',
                            progressBar: '<div class="bar"></div>',
                            itemAppendToEnd: false,
                            removeConfirmation: true,
                            _selectors: {
                                list: '.jFiler-items-list',
                                item: '.jFiler-item',
                                progressBar: '.bar',
                                remove: '.jFiler-item-trash-action'
                            }
                        },
                        dragDrop: {},
                        uploadFile: {
                            url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                            data: {
                                'action': 'bp_better_messages_attach_file',
                                'thread_id': "<?php echo $thread_id; ?>",
                                'nonce': "<?php echo wp_create_nonce( 'file-upload-' . $thread_id ); ?>"
                            },
                            type: 'POST',
                            enctype: 'multipart/form-data',
                            beforeSend: function () {
                            },
                            success: function (data, el, l, p, o, s, cid, textStatus, jqXHR) {
                                var parent = el.find(".jFiler-jProgressBar").parent();
                                el.find(".jFiler-jProgressBar").fadeOut("slow", function () {
                                    $("<div class=\"jFiler-item-others text-success\"><i class=\"icon-jfi-check-circle\"></i> Success</div>").hide().appendTo(parent).fadeIn("slow");
                                });

                                el.attr('data-id', data.result);
                            },
                            error: function (el, l, p, o, s, cid, jqXHR, textStatus, errorThrown) {
                                var parent = el.find(".jFiler-jProgressBar").parent();
                                el.find(".jFiler-jProgressBar").fadeOut("slow", function () {
                                    $("<div class=\"jFiler-item-others text-error\"><i class=\"icon-jfi-minus-circle\"></i> Error</div>").hide().appendTo(parent).fadeIn("slow");
                                });

                                var response = jqXHR['responseJSON'];
                                BBPMShowError(response.error);
                            },
                            statusCode: null,
                            onProgress: null,
                            onComplete: null
                        },
                        onRemove: function (itemEl, file, id, listEl, boxEl, newInputEl, inputEl) {
                            var fileId = file.id;
                            if (typeof fileId == 'undefined') fileId = itemEl.attr('data-id');

                            $.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                                action: 'bp_better_messages_deattach_file',
                                file_id: fileId,
                                thread_id: "<?php echo $thread_id; ?>",
                                nonce: "<?php echo wp_create_nonce( 'file-delete-' . $thread_id ); ?>"
                            }, function (result) {
                                console.log(result);
                            });
                        },
                        dialogs: {
                            alert: function (text) {
                                BBPMShowError(text);
                            },
                            confirm: function (text, callback) {
                                confirm(text) ? callback() : null;
                            }
                        },
                        captions: {
                            button: "<?php esc_attr_e( 'Choose Files', 'bp-better-messages' ); ?>",
                            feedback: "<?php esc_attr_e( 'Choose files To Upload', 'bp-better-messages' ); ?>",
                            feedback2: "<?php esc_attr_e( 'files were chosen', 'bp-better-messages' ); ?>",
                            drop: "<?php esc_attr_e( 'Drop file here to Upload', 'bp-better-messages' ); ?>",
                            removeConfirmation: "<?php esc_attr_e( 'Are you sure you want to remove this file?', 'bp-better-messages' ); ?>",
                            errors: {
                                filesLimit: "<?php printf( esc_attr__( 'Only {{fi-limit}} files are allowed to be uploaded.', 'bp-better-messages' ), '{{fi-limit}}' ); ?>",
                                filesType: "<?php esc_attr_e( 'This file type are not allowed to be uploaded.', 'bp-better-messages' ); ?>",
                                filesSize: "<?php printf( esc_attr__( '%s is too large! Please upload file up to %s MB.', 'bp-better-messages' ), '{{fi-name}}', '{{fi-fileMaxSize}}' ); ?>",
                                filesSizeAll: "<?php printf( esc_attr__( 'Files you\'ve choosed are too large! Please upload files up to %s MB.', 'bp-better-messages' ), '{{fi-maxSize}}' ); ?>",
                                folderUpload: "<?php esc_attr_e( 'You are not allowed to upload folders.', 'bp-better-messages' ); ?>"
                            }
                        }
                    };

                    <?php if ( !empty( $files ) ) echo 'options["files"] = ' . json_encode( $files ) . ';'; ?>

                    var filer = $('#files').filer(options);
                    var filerApi = filer.prop("jFiler");

                    insertButton();

                    $(document).on('bp-better-messages-message-sent', function () {
                        $('.jFiler-input-dragDrop').slideUp();
                        filerApi.reset();
                    });

                    function insertButton() {
                        var initiated = $('#reply .emojionearea').prepend('<span class="upload-btn"><i class="fa fa-paperclip" aria-hidden="true"></i></span>');
                        if (initiated.length == 0) {
                            setTimeout(insertButton, 1000);
                        } else {
                            $('#reply .emojionearea .upload-btn').on('click touchstart', function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                $('.jFiler-input-dragDrop').slideToggle();
                            })
                        }
                    }
                });
            </script>
            <?php
        }

        public function get_unused_attachments( $thread_id, $user_id )
        {
            return get_posts( array(
                'post_type'   => 'attachment',
                'post_status' => 'any',
                'author'      => $user_id,
                'numberposts' => -1,
                'meta_query'  => array(
                    array(
                        'key'   => 'bp-better-messages-attachment',
                        'value' => true
                    ),
                    array(
                        'key'   => 'bp-better-messages-thread-id',
                        'value' => $thread_id
                    ),
                    array(
                        'key'     => 'bp-better-messages-message-id',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ) );
        }

        public function handle_delete()
        {
            $user_id = get_current_user_id();
            $attachment_id = intval( $_POST[ 'file_id' ] );
            $thread_id = intval( $_POST[ 'thread_id' ] );
            $attachment = get_post( $attachment_id );


            // Security verify 1
            if ( !BP_Messages_Thread::check_access( $thread_id, $user_id ) ||
                !wp_verify_nonce( $_POST[ 'nonce' ], 'file-delete-' . $thread_id ) ||
                ( (int)$attachment->post_author !== $user_id ) || !$attachment
            ) {
                wp_send_json( false );
                exit;
            }

            // Security verify 2
            if ( (int)get_post_meta( $attachment->ID, 'bp-better-messages-thread-id', true ) !== $thread_id ) {
                wp_send_json( false );
                exit;
            }

            // Looks like we can delete it now!
            $result = wp_delete_attachment( $attachment->ID, true );
            if ( $result ) {
                wp_send_json( true );
            } else {
                wp_send_json( false );
            }

            exit;
        }

        public function handle_upload()
        {

            $result = array(
                'result' => false,
                'error'  => ''
            );

            $thread_id = intval( $_POST[ 'thread_id' ] );

            if ( !empty( $_FILES[ 'files' ] ) || wp_verify_nonce( $_POST[ 'nonce' ], 'file-upload-' . $thread_id ) ) {


                $can_upload = $this->user_can_upload( get_current_user_id(), $thread_id );

                if ( !$can_upload ) {
                    $result[ 'error' ] = __( 'You can`t upload files.', 'bp-better-messages' );
                    status_header( 403 );
                    wp_send_json( $result );
                    exit;
                }

                // The nonce was valid and the user has the capabilities, it is safe to continue.

                // These files need to be included as dependencies when on the front end.
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                foreach ( $_FILES[ 'files' ] as $key => $val ) {
                    $_FILES[ 'files' ][ $key ] = $val[ 0 ];
                }

                // Remember, 'my_image_upload' is the name of our file input in our form above.
                $attachment_id = media_handle_upload( 'files', $_POST[ 'post_id' ] );

                if ( is_wp_error( $attachment_id ) ) {
                    // There was an error uploading the image.
                    status_header( 400 );
                    $result[ 'error' ] = $attachment_id->get_error_message();
                } else {
                    // The image was uploaded successfully!
                    add_post_meta( $attachment_id, 'bp-better-messages-attachment', true, true );
                    add_post_meta( $attachment_id, 'bp-better-messages-thread-id', $thread_id, true );
                    add_post_meta( $attachment_id, 'bp-better-messages-upload-time', time(), true );
                    status_header( 200 );
                    $result[ 'result' ] = $attachment_id;
                }
            } else {
                status_header( 406 );
                $result[ 'error' ] = __( 'Your request is empty.', 'bp-better-messages' );
            }

            wp_send_json( $result );
            exit;
        }

        public function user_can_upload( $user_id, $thread_id )
        {
            return apply_filters( 'bp_better_messages_user_can_upload_files', BP_Messages_Thread::check_access( $thread_id, $user_id ), $user_id, $thread_id );
        }

    }

endif;


function BP_Better_Messages_Files()
{
    return BP_Better_Messages_Files::instance();
}
