<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Component Class.
 *
 * @since 1.0.0
 */
class BP_Better_Messages_Component extends BP_Component
{

    public static function instance()
    {

        // Store the instance locally to avoid private static replication
        static $instance = null;

        // Only run these methods if they haven't been run previously
        if ( null === $instance ) {
            $instance = new BP_Better_Messages_Component;
        }

        // Always return the instance
        return $instance;

        // The last metroid is in captivity. The galaxy is at peace.
    }

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::start(
            'bp_better_messages_tab',
            __( 'Messages', 'bp-better-messages' ),
            '',
            array(
                'adminbar_myaccount_order' => 50
            )
        );

        $this->setup_hooks();

    }

    /**
     * Set some hooks to maximize BuddyPress integration.
     *
     * @since 1.0.0
     */
    public function setup_hooks()
    {
        add_action( 'init', array( $this, 'remove_standard_tab' ) );
    }


    public function remove_standard_tab()
    {
        global $bp;
        $bp->members->nav->delete_nav( 'messages' );
    }

    /**
     * Include component files.
     *
     * @since 1.0.0
     */
    public function includes( $includes = array() )
    {
    }

    /**
     * Set up component global variables.
     *
     * @since 1.0.0
     */
    public function setup_globals( $args = array() )
    {

        // Define a slug, if necessary
        if ( !defined( 'BP_BETTER_MESSAGES_SLUG' ) ) {
            define( 'BP_BETTER_MESSAGES_SLUG', 'bp-messages' );
        }

        // All globals for component.
        $args = array(
            'slug'          => BP_BETTER_MESSAGES_SLUG,
            'has_directory' => false
        );

        parent::setup_globals( $args );

        $this->tax_network_profile = '';
        $slug = bp_get_profile_slug();
        $this->tax_network_profile = trailingslashit( bp_loggedin_user_domain() . $this->slug );

        // Was the user redirected from WP Admin ?
        $this->was_redirected = false;
    }


    /**
     * Set up the component entries in the WordPress Admin Bar.
     *
     * @since 1.3
     */
    public function setup_admin_bar( $wp_admin_nav = array() )
    {
        // Menus for logged in user
        if ( !is_user_logged_in() ) return;

        $messages_total = messages_get_unread_count();

        $title = sprintf( _x( 'Messages <span class="%s bp-better-messages-unread">%s</span>', 'Messages list sub nav', 'bp-better-messages' ), esc_attr( 'count' ), bp_core_number_format( $messages_total ) );

        $wp_admin_nav[] = array(
            'parent' => buddypress()->my_account_menu_id,
            'id'     => 'bp-messages-' . $this->id,
            'title'  => $title,
            'href'   => BP_Better_Messages()->functions->get_link()
        );

        $wp_admin_nav[] = array(
            'parent' => 'bp-messages-' . $this->id,
            'id'     => 'bp-messages-' . $this->id . '-threads',
            'title'  => __( 'Threads', 'bp-better-messages' ),
            'href'   => BP_Better_Messages()->functions->get_link()
        );

        $wp_admin_nav[] = array(
            'parent' => 'bp-messages-' . $this->id,
            'id'     => 'bp-messages-' . $this->id . '-starred',
            'title'  => __( 'Starred', 'bp-better-messages' ),
            'href'   => BP_Better_Messages()->functions->get_link() . '?starred'
        );

        $wp_admin_nav[] = array(
            'parent' => 'bp-messages-' . $this->id,
            'id'     => 'bp-messages-' . $this->id . '-new-message',
            'title'  => __( 'New Thread', 'bp-better-messages' ),
            'href'   => BP_Better_Messages()->functions->get_link() . '?new-message'
        );

        parent::setup_admin_bar( $wp_admin_nav );
    }

    /**
     * Set up component navigation.
     *
     * @since 1.0.0
     */
    public function setup_nav( $main_nav = array(), $sub_nav = array() )
    {
        if ( !bp_is_active( 'messages' ) ) return false;

        $messages_total = messages_get_unread_count();

        $class = ( 0 === $messages_total ) ? 'no-count' : 'count';
        $nave = sprintf( _x( 'Messages <span class="%s bp-better-messages-unread">%s</span>', 'Messages list sub nav', 'bp-better-messages' ), esc_attr( $class ), bp_core_number_format( $messages_total ) );

        $main_nav = array(
            'name'                    => $nave,
            'slug'                    => $this->slug,
            'position'                => 50,
            'screen_function'         => array( $this, 'set_screen' ),
            'default_subnav_slug'     => BP_BETTER_MESSAGES_SLUG,
            'item_css_id'             => $this->id,
            'show_for_displayed_user' => false
        );

        parent::setup_nav( $main_nav, $sub_nav );
    }

    /**
     * Set the BuddyPress screen for the requested actions
     *
     * @since 1.0.0
     */
    public function set_screen()
    {
        // Allow plugins to do things there..
        do_action( 'bp_better_messages_screen' );

        // Prepare the template part.
        add_action( 'bp_template_content', array( $this, 'content' ) );

        // Load the template
        bp_core_load_template( 'members/single/plugins' );
    }

    /**
     * Output the Comments page content
     *
     * @since 1.0.0
     */
    public function content()
    {
        $path = BP_Better_Messages()->path . '/views/';

        if ( isset( $_GET[ 'thread_id' ] ) ) {
            $thread_id = absint( $_GET[ 'thread_id' ] );

            if ( ! BP_Messages_Thread::check_access( $thread_id ) ) {
                echo '<p>' . __( 'Access restricted', 'bp-better-messages' ) . '</p>';
                include( $path . 'layout-index.php' );
            } else {
                messages_mark_thread_read( $thread_id );
                include( $path . 'layout-thread.php' );
            }
        } else if ( isset( $_GET[ 'new-message' ] ) ) {
            include( $path . 'layout-new.php' );
        } else if ( isset( $_GET[ 'starred' ] ) ) {
            include( $path . 'layout-starred.php' );
        } else {
            include( $path . 'layout-index.php' );
        }
    }

    /**
     * Figure out if the user was redirected from the WP Admin
     *
     * @since 1.0.0
     */
    public function was_redirected( $prevent_access )
    {
        // Catch this, true means the user is about to be redirected
        $this->was_redirected = $prevent_access;

        return $prevent_access;
    }
}

function BP_Better_Messages_Tab()
{
    return BP_Better_Messages_Component::instance();
}
