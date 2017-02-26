/* global BP_Messages */
(function ($) {
    var checkerTimer, // Timer variable
        thread,  // Current thread_id or false
        threads, // True if we are on thread list screen or false
        preventSound,
        openThreads,
        bpMessagesWrap,
        online = []; // Variable to avoid multiple sound notifications

    $(document).ready(function () {
        bpMessagesWrap = $(".bp-messages-wrap");

        reInit();

        if (store.enabled) {
            openThreads = store.get('bp-better-messages-open-threads') || {};
            setInterval(updateOpenThreads, 1000);
        }

        if (BP_Messages['realtime'] == "1") {
            realTimeInit();
        }

        setInterval(function () {
            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_last_activity_refresh'
            });
        }, 300000);

        var notification = BP_Messages.assets + 'sounds/notification';

        preventSound = false;

        $('<audio id="bp-messages-notification" style="display:none;">'
          + '<source src="' + notification + '.mp3" />'
          + '<source src="' + notification + '.ogg" />'
          + '<embed src="' + notification + '.mp3" hidden="true" autostart="false" loop="false" />'
          + '</audio>'
        ).appendTo('body');

        /**
         * Go to thread from thread list
         */
        bpMessagesWrap.on('click touchstart', '.threads-list .thread:not(.blocked)', function (event) {

            if (!$(event.target).parent().is('a')) {
                event.preventDefault();
                var href = $(this).attr('data-href');
                ajaxRefresh(href);
            }

        });

        /**
         * Delete thread! :)
         */
        bpMessagesWrap.on('click touchstart', '.threads-list .thread span.delete', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var thread = $(this).parent().parent();
            var thread_id = $(thread).attr('data-id');
            var height = $(thread).height();

            var nonce = $(this).attr('data-nonce');

            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_delete_thread',
                'thread_id': thread_id,
                'nonce': nonce
            }, function (data) {
                if (!data.result) {
                    BBPMShowError(data['errors'][0]);
                } else {
                    $(thread).addClass('blocked');
                    $(thread).find('.deleted').show().css({
                        'height': height,
                        'line-height': height + 'px'
                    });
                }
            });
        });

        /**
         * Delete thread! :)
         */
        bpMessagesWrap.on('click touchstart', '.threads-list .thread a.undelete', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var thread = $(this).parent().parent();
            var thread_id = $(thread).attr('data-id');
            $(thread).removeClass('blocked');

            var nonce = $(this).attr('data-nonce');

            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_un_delete_thread',
                'thread_id': thread_id,
                'nonce': nonce
            }, function (data) {
                if (!data.result) {
                    BBPMShowError(data['errors'][0]);
                } else {
                    $(thread).removeClass('blocked');
                    $(thread).find('.deleted').hide();
                }
            });
        });

        /**
         * Messages actions
         */
        bpMessagesWrap.on('click touchstart', '.messages-list li .favorite', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var message_id = $(this).parentsUntil('.messages-list', 'li').attr('data-id');
            var type = 'star';
            if ($(this).hasClass('active')) type = 'unstar';

            $(this).toggleClass('active');

            $.post(BP_Messages.ajaxUrl, {
                'action': 'bp_messages_favorite',
                'message_id': message_id,
                'thread_id': thread,
                'type': type
            }, function (bool) {
            });
        });

        /*
         * Reply submit
         */
        bpMessagesWrap.on('submit', 'form#reply', function (event) {
            event.preventDefault();
            event.stopPropagation();

            $.post(BP_Messages.ajaxUrl, $(this).serialize(), function (data) {

                if (typeof data.result == 'undefined') return;

                if (data.result) {
                    refreshThread();
                    $(document).trigger("bp-better-messages-message-sent");
                } else {
                    BBPMShowError(data['errors'][0]);
                }

            });

            $(this).find('textarea, .emojionearea-editor').html('');
        });

        /**
         * New Thread Submit
         */
        bpMessagesWrap.on('submit', '.new-message form', function (event) {
            event.preventDefault();
            event.stopPropagation();

            $.post(BP_Messages.ajaxUrl, $(this).serialize(), function (data) {
                if (data.result) {
                    ajaxRefresh(BP_Messages.threadUrl + data['thread_id']);
                } else {
                    BBPMShowError(data['errors'][0]);
                }
            });
        });

        /**
         * Switches screens without page reloading
         */
        bpMessagesWrap.on('click touchstart', 'a.ajax', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var href = $(this).attr('href');

            ajaxRefresh(href);
        });

        /**
         * Send message on Enter
         */
        bpMessagesWrap.on('keydown', '.reply .emojionearea-editor', function (event) {
            if ( ! event.shiftKey && event.keyCode == 13 ) {
                event.preventDefault();
                $(this).blur();
                bpMessagesWrap.find('.reply form#reply').trigger("submit");
                $(this).focus();
            }
        });

        bpMessagesWrap.on('change', '.new-message .send-to-input', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var href = $(this).attr('href');
            ajaxRefresh(href);
        });

        bpMessagesWrap.on('click touchstart', '.scroll-wrapper.starred .messages-list li', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var thread_id = $(this).attr('data-thread');
            var message_id = $(this).attr('data-id');
            ajaxRefresh(BP_Messages.threadUrl + thread_id + '&message_id=' + message_id);
        });

    });


    function realTimeInit() {
        var writingTimeout;
        var socket = io.connect('https://realtime.wordplus.org/');

        /**
         * Trying to connect
         */
        socket.on('connect', function () {
            socket.emit('authentication', BP_Messages['site_id'], BP_Messages['user_id'], BP_Messages['secret_key']);
        });

        /**
         * Online users
         */
        socket.on('onlineUsers', function (user_ids) {
            online = user_ids;
            onlineInit();
        });

        socket.on('userOnline', function (user_id) {
            if (online.indexOf(user_id) === -1) {
                online.push(user_id);
                $('.bbpm-avatar[data-user-id="' + user_id + '"]').addClass('online');
            }
        });

        socket.on('userOffline', function (user_id) {
            if (online.indexOf(user_id) > -1) {
                online.splice(online.indexOf(user_id), 1);
                $('.bbpm-avatar[data-user-id="' + user_id + '"]').removeClass('online');
            }
        });

        /**
         * Messaging
         */
        socket.on('message', function (message) {

            if (thread == message['thread_id']) {
                createCookie('bp-better-messages-thread-' + message['thread_id'], message['date'], 1);
                renderMessage(message);
            } else if (threads) {
                if (typeof openThreads[this.thread_id] == 'undefined')
                    updateThreads(message);
            } else {
                if (BP_Messages['user_id'] != message.user_id) showMessage(message.thread_id, message.content_site, message.name, message.avatar);
            }

            $('.bp-better-messages-unread').text(message.total_unread);
        });

        /**
         * Writing notifications
         */
        socket.on('writing', function (writing) {
            if (thread && typeof writing[thread] == 'object') {
                clearTimeout(writingTimeout);

                var writers = [];
                $.each(writing[thread], function () {
                    writers.push(participants[this]);
                });

                updateWritingPosition();

                $('span.writing').html(writers.join(', ') + ' ' + BP_Messages['strings']['writing']).show();


                writingTimeout = setTimeout(function () {
                    $('span.writing').hide();
                }, 1500);
            }
        });

        var writing = 0;
        bpMessagesWrap.on('keyup', '.reply .emojionearea-editor', function (event) {
            if (thread && event.which <= 90 && event.which >= 48) {
                if (writing == false || ( writing + 1000 ) < Date.now()) {
                    writing = Date.now();
                    socket.emit('writing', thread, $('.scroll-content.thread').attr('data-users'));
                }
            }
        });
    }

    /**
     * Function to determine where we now and what we need to do
     */
    function reInit() {
        thread = false;
        threads = false;
        clearTimeout(checkerTimer);
        // Only initialize new media elements.
        $( '.wp-audio-shortcode, .wp-video-shortcode' )
            .not( '.mejs-container' )
            .filter(function () {
                return ! $( this ).parent().hasClass( '.mejs-mediaelement' );
            })
            .mediaelementplayer();

        $('.bp-better-messages-unread').text(BP_Messages.total_unread);

        updateOpenThreads();

        if (BP_Messages['realtime'] == "1") onlineInit();

        if ($(".bp-messages-wrap > .thread").length > 0) {
            thread = $(".bp-messages-wrap > .thread").attr('data-id');
        } else if ($(".bp-messages-wrap .threads-list").length > 0) {
            threads = true;
        }

        if (thread) {
            checkForHeightChanges();
            checkerTimer = setTimeout(refreshThread, BP_Messages.threadRefresh);
        } else {
            checkerTimer = setTimeout(refreshSite, BP_Messages.siteRefresh);
        }


        $(".bp-messages-wrap .reply .message textarea, .bp-messages-wrap .new-message #message-input").emojioneArea();

        jQuery('.scrollbar-inner').scrollbar();

        if ($('#send-to').length > 0) {
            var cache = [];
            var tags = [];

            var to = $('input[name="to"]');

            if (to.length > 0) {
                $(to).each(function () {
                    tags.push($(this).val());
                    $(this).remove();
                });
            }

            var sentTo = new Taggle('send-to', {
                tags: tags,
                placeholder: '',
                tabIndex: 2,
                hiddenInputName: 'recipients[]'
            });

            var container = sentTo.getContainer();
            var input = sentTo.getInput();

            $(input).autocomplete({
                source: function (request, response) {
                    var term = request.term;
                    if (term in cache) {
                        response(cache[term]);
                        return;
                    }

                    $.getJSON(BP_Messages.ajaxUrl + "?q=" + term + "&limit=10&action=bp_messages_autocomplete&cookie=" + getAutocompleteCookies(), request, function (data, status, xhr) {
                        cache[term] = data;
                        response(data);
                    });
                },
                minLength: 2,
                appendTo: container,
                position: {at: "left bottom", of: container},
                open: function (event, ui) {
                    var autocomplete = $("#send-to .ui-autocomplete");
                    var oldTop = parseInt(autocomplete.css('top'));
                    var newTop = oldTop - 3;
                    autocomplete.css("top", newTop);
                },
                select: function (event, data) {
                    event.preventDefault();
                    //Add the tag if user clicks
                    if (event.which === 1) {
                        sentTo.add(data.item.value);
                    }
                }
            });
        }
    }


    var sameHeight = 0;
    var lastHeight = 0;

    function checkForHeightChanges()
    {
        scrollBottom();

        if($(".bp-messages-wrap .list").length == 0) return;

        if ($(".bp-messages-wrap .list")[0].offsetHeight != lastHeight)
        {
            sameHeight = 0;
            lastHeight = $(".bp-messages-wrap .list")[0].offsetHeight;
            scrollBottom();
        } else {
            sameHeight++;
        }

        if(sameHeight < 10) setTimeout(checkForHeightChanges, 500);

    }

    function scrollBottom() {
        if($(".bp-messages-wrap .list").length == 0) return;

        var scroll = $(".bp-messages-wrap .list")[0].offsetHeight;
        lastHeight = scroll;
        if (getParameterByName('message_id').length > 0) {
            var message_id = getParameterByName('message_id');
            var message = $(".bp-messages-wrap .messages-list li[data-id='" + message_id + "']");

            if (message.length > 0) {
                scroll = message[0].offsetTop - message[0].offsetHeight - 100;
            }
        }

        $(".bp-messages-wrap .scroller").scrollTop(parseInt(scroll) + 100);
    }

    /**
     * Check for new messages on all sites page
     */
    function refreshSite() {
        if (BP_Messages['realtime'] == "1") return;

        var last_check = readCookie('bp-messages-last-check');
        clearInterval(checkerTimer);

        $.post(BP_Messages.ajaxUrl, {
            'action': 'bp_messages_check_new',
            'last_check': last_check
        }, function (response) {

            if (response.threads.length > 0) {
                $.each(response.threads, function () {
                    var message = this;
                    if (threads) {
                        updateThreads(message);
                    } else {
                        showMessage(message.thread_id, message['message'], message['name'], message['avatar']);
                    }
                });
            }

            $('.bp-better-messages-unread').text(response.total_unread);

            checkerTimer = setTimeout(refreshSite, BP_Messages.siteRefresh);
        });
    }


    /**
     * Check for new messages on open thread screen
     */
    function refreshThread() {

        if (BP_Messages['realtime'] == "1") return;

        var last_check = readCookie('bp-messages-last-check');
        var last_message = $('.messages-stack:last-child .messages-list li:last-child').attr('data-time');

        clearInterval(checkerTimer);

        $.post(BP_Messages.ajaxUrl, {
            'action': 'bp_messages_thread_check_new',
            'last_check': last_check,
            'thread_id': thread,
            'last_message': last_message
        }, function (response) {

            $.each(response.messages, function () {
                renderMessage(this);
            });

            $.each(response.threads, function () {
                showMessage(this.thread_id, this['message'], this['name'], this['avatar']);
            });

            $('.bp-better-messages-unread').text(response.total_unread);

            checkerTimer = setInterval(refreshThread, BP_Messages.threadRefresh);

        });
    }

    function updateOpenThreads() {

        if ( ! store.enabled )  return false;

        openThreads = store.get('bp-better-messages-open-threads') || {};

        if (thread != false) {
            openThreads[thread] = Date.now();
        }

        $.each(openThreads, function (index) {
            if ((this + 2000) < Date.now()) delete openThreads[index];
        });

        store.set('bp-better-messages-open-threads', openThreads);

    }

    /**
     * Simple function to avoid page reloading
     *
     * @param url
     */
    function ajaxRefresh(url) {
        window.history.pushState("", "", url);

        $('.bp-messages-wrap .preloader').show();

        $.get(url, function (html) {
            var newWrapper = $(html).find('.bp-messages-wrap').html();
            $('.bp-messages-wrap').html(newWrapper);
            reInit();
        });
    }

    /**
     * Online avatars init
     */
    function onlineInit() {
        $('.bp-messages-wrap img.avatar[data-user-id]').each(function () {
            var user_id = $(this).attr('data-user-id');
            var parent = false;

            if ($(this).parent().hasClass('bbpm-avatar')) parent = $(this).parent();

            if (!parent) {
                var width = $(this).attr('width');
                var height = $(this).attr('height');
                var marginTop = $(this).css('marginTop');
                var marginLeft = $(this).css('marginLeft');
                var marginBottom = $(this).css('marginBottom');
                var marginRight = $(this).css('marginRight');
                $(this).css({
                    marginTop: 0,
                    marginLeft: 0,
                    marginRight: 0,
                    marginBottom: 0
                });
                $(this).wrap('<span class="avatar bbpm-avatar" data-user-id="' + user_id + '"></span>');
                parent = $(this).parent();
                parent.css({
                    marginTop: marginTop,
                    marginLeft: marginLeft,
                    marginRight: marginRight,
                    marginBottom: marginBottom,
                    width: width,
                    height: height
                });
            }

            if (online.indexOf(user_id) > -1) {
                $(parent).addClass('online');
            } else {
                $(parent).removeClass('online');
            }

        });
    }

    /**
     * Refreshes threads on thread list screen
     * @param message
     */
    function updateThreads(message) {

        var thread_id = message['thread_id'];
        $(".bp-messages-wrap .threads-list .thread[data-id='" + thread_id + "']").remove();
        $(".bp-messages-wrap .threads-list").prepend(message['html']);

        if (BP_Messages.user_id != message.user_id) playSound();

        onlineInit();
    }

    /**
     * Properly placing new message on thread screen
     * @param message
     */
    function renderMessage(message) {
        var stack = $('.messages-stack:last-child');
        var same_message = $('.messages-list li[data-id="' + message.id + '"]');

        if (same_message.length == 0 && stack.length > 0) {
            if (stack.attr('data-user-id') == message.user_id) {
                stack.find('.messages-list').append('<li data-time="' + message.timestamp + '" data-id="' + message.id + '"><span class="favorite"><i class="fa" aria-hidden="true"></i></span>' + message.message + '</li>');
            } else {
                $('.bp-messages-wrap .list').append(
                    '<div class="messages-stack" data-user-id="' + message.user_id + '">' +
                    '<div class="pic">' + message.avatar + '</div>' +
                    '<div class="content">' +
                    '<div class="info">' +
                    '<div class="name">' +
                    '<a href="' + message.link + '">' + message.name + '</a>' +
                    '</div>' +
                    '<div class="time" data-livestamp="' + message.timestamp + '"></div>' +
                    '</div>' +
                    '<ul class="messages-list">' +
                    '</ul>' +
                    '</div>' +
                    '</div>'
                );

                $('.messages-stack:last-child .messages-list').append('<li data-time="' + message.timestamp + '" data-id="' + message.id + '"><span class="favorite"><i class="fa" aria-hidden="true"></i></span>' + message.message + '</li>');
            }


            $(".bp-messages-wrap .scroller").scrollTop($(".bp-messages-wrap .list").height() + 100);

            $(".wp-audio-shortcode, .wp-video-shortcode").not(".mejs-container").filter(function(){return!$(this).parent().hasClass(".mejs-mediaelement")}).mediaelementplayer();

            if (BP_Messages.user_id != message.user_id) playSound();
        }

        onlineInit();
    }

    /**
     * Show message notification popup
     *
     * @param thread_id
     * @param message
     * @param name
     * @param avatar
     */
    function showMessage(thread_id, message, name, avatar) {
        if (typeof openThreads[thread_id] !== 'undefined') return;

        var findSrc = avatar.match(/src\="([^\s]*)"\s/);

        if (findSrc != null) {
            avatar = findSrc[1];
        }

        $.amaran({
            'theme': 'user message thread_' + thread_id,
            'content': {
                img: avatar,
                user: name,
                message: message
            },
            'sticky': true,
            'closeOnClick': false,
            'closeButton': true,
            'delay': 10000,
            'thread_id': thread_id,
            'position': 'bottom right',
            onClick: function () {
                location.href = BP_Messages.threadUrl + this.thread_id;
            }
        });

        playSound();
    }

    function updateWritingPosition() {
        var writingSpan = $('span.writing');
        var height = $('.bp-messages-wrap .reply').outerHeight();

        writingSpan.css('bottom', height);
    }
    /**
     * Playing notification sound!
     */
    function playSound() {
        $('#bp-messages-notification')[0].play();
    }

    function createCookie(name, value, days) {
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            var expires = "; expires=" + date.toUTCString();
        }
        else var expires = "";
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }


    function getAutocompleteCookies() {
        var allCookies = document.cookie.split(';'),  // get all cookies and split into an array
            bpCookies = {},
            cookiePrefix = 'bp-',
            i, cookie, delimiter, name, value;

        // loop through cookies
        for (i = 0; i < allCookies.length; i++) {
            cookie = allCookies[i];
            delimiter = cookie.indexOf('=');
            name = jq.trim(unescape(cookie.slice(0, delimiter)));
            value = unescape(cookie.slice(delimiter + 1));

            // if BP cookie, store it
            if (name.indexOf(cookiePrefix) === 0) {
                bpCookies[name] = value;
            }
        }

        // returns BP cookies as querystring
        return encodeURIComponent(jq.param(bpCookies));
    }

    function getParameterByName(name) {
        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
        var regexS = "[\\?&]" + name + "=([^&#]*)";
        var regex = new RegExp(regexS);
        var results = regex.exec(window.location.search);
        if (results == null)
            return "";
        else
            return decodeURIComponent(results[1].replace(/\+/g, " "));
    }
})(jQuery);

/**
 * Show error popup
 */
function BBPMShowError(error) {
    jQuery.amaran({
        'theme': 'colorful',
        'content': {
            bgcolor: '#c0392b',
            color: '#fff',
            message: error
        },
        'sticky': false,
        'closeOnClick': true,
        'closeButton': true,
        'delay': 10000,
        'position': 'bottom right'
    });
}