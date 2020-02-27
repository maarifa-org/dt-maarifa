<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly
/**
 * Class Disciple_Tools_Maarifa_Hooks
 */
class Disciple_Tools_Maarifa_Hooks
{
    /**
     * Disciple_Tools_Maarifa_Hooks The single instance of Disciple_Tools_Maarifa_Hooks.
     *
     * @var    object
     * @access private
     * @since  0.5.0
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_Maarifa_Hooks Instance
     * Ensures only one instance of Disciple_Tools_Maarifa_Hooks is loaded or can be loaded.
     *
     * @since  0.5.0
     * @static
     * @return Disciple_Tools_Maarifa_Hooks instance
     */
    public static function instance() {
        if (is_null( self::$_instance )) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    /**
     * Constructor function.
     *
     * @access public
     * @since  0.1.0
     */
    public function __construct() {
        add_action( 'dt_post_created', array( $this, 'post_created' ), 10, 3 );
        add_action( 'dt_post_updated', array( $this, 'post_updated' ), 10, 4 );
        add_action( 'dt_comment_created', array( $this, 'comment_created' ), 10, 4 );
    } // End __construct()

    /**
     * Hook for when a new DT post is created.
     * If it is a new contact with the maarifa source, share it with the user
     * configured in the plugin settings.
     *
     * @param $post_type
     * @param $post_id
     * @param $post
     *
     * @since 0.3.0
     */
    public function post_created( $post_type, $post_id, $post ) {
        // If this is a new contact...
        if ( $post_type === 'contacts' ) {

            if ( isset( $post['sources'] ) && isset( $post['sources']['values'] ) ) {
                $maarifa_source = array_search( 'maarifa', array_column( $post['sources']['values'], 'value' ) );

                // ...and if this user has the maarifa source...
                if ($maarifa_source !== false) {
                    // Get auto-share user from plugin options
                    $share_user = get_option( 'dt_maarifa_share_user_id' );

                    if ( !empty( $share_user )) {
                        // Assign new contact to given user
                        DT_Posts::add_shared( $post_type, $post_id, $share_user, null, false );
                    }
                }
            }
        }
    }

    /**
     * Hook for when a DT post is updated.
     * Capture dt_post_updated actions to forward back to Maarifa.
     * Ignores: non-contacts, requires_update, contacts without maarifa_data,
     * updates from Maarifa, and reminders (requires_update)
     *
     * @param $post_type
     * @param $post_id
     * @param $initial_fields
     * @param $existing_post
     *
     * @since 0.5.0
     */
    public function post_updated( $post_type, $post_id, $initial_fields, $existing_post ) {

        dt_write_log( 'hook:post_updated' );

        // Only send back contacts post types
        if ( $post_type !== 'contacts' ) {
            return;
        }

        // If this is just a notification about requiring an update, no need to sync
        if ( isset( $initial_fields['requires_update'] ) && $initial_fields['requires_update']) {
            return;
        }

        // todo: check for automated updates from RS

        // Check if this is a Maarifa-sourced contact
        $is_maarifa = false;
        if ( isset( $existing_post["maarifa_data"] ) ) {
            $maarifa_data = maybe_unserialize( $existing_post["maarifa_data"] );
            if ( isset( $maarifa_data["id"] ) ) {
                $is_maarifa = true;
            }
        }
        // If not, don't proceed
        if ( !$is_maarifa ) {
            return;
        }
        dt_write_log( serialize( $existing_post ) );
        dt_write_log( serialize( $initial_fields ) );

        // Get Maarifa site links
        $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
        if ( empty( $site_links ) ) {
            return;
        }

        foreach ($site_links as $site_link ) {
            dt_write_log( serialize( $site_link ) );
             $site = Site_Link_System::get_site_connection_vars( $site_link['id'] );
            dt_write_log( serialize( $site ) );
            // todo: POST to $site['url'] with $site['transfer_token']
        }
        // todo: if Maarifa and has RS ID
        // todo: something
    }

    /**
     * Hook for when a DT comment is updated.
     * Capture dt_comment_created actions to forward back to Maarifa.
     * Ignores: non-contacts, non-comments, contacts without maarifa_data,
     * updates from Maarifa, and reminders (action not actually called for reminders/triggers)
     *
     * @param $post_type
     * @param $post_id
     * @param $created_comment_id
     * @param $type - Comment type (e.g. "comment")
     */
    public function comment_created( $post_type, $post_id, $created_comment_id, $type ) {

        dt_write_log( 'hook:comment_created' );

        // Only send back contacts post types and comments
        if ( $post_type !== 'contacts' || $type !== 'comment') {
            return;
        }

        //todo: get comment and post
        //todo: get site link
        dt_write_log( serialize( array(
            'post_type' => $post_type,
            'post_id' => $post_id,
            'created_comment_id' => $created_comment_id,
            'type' => $type
        )));
    }
}

Disciple_Tools_Maarifa_Hooks::instance();
