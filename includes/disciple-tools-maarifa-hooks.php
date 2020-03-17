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

        // Check for automated updates from RS
        if ( isset( $initial_fields['maarifa_sync'] ) && $initial_fields['maarifa_sync'] == true ) {
            return;
        }

        // Check if this is a Maarifa-sourced contact
        $maarifa_contact_id = null;
        if ( isset( $existing_post["maarifa_data"] ) ) {
            $maarifa_data = maybe_unserialize( $existing_post["maarifa_data"] );
            if ( isset( $maarifa_data["id"] ) ) {
                $maarifa_contact_id = $maarifa_data["id"];
                dt_write_log( 'maarifa id:' . $maarifa_contact_id );
            }
        }
        // If not Maarifa-sourced, don't proceed
        if ( empty( $maarifa_contact_id ) ) {
            return;
        }
        dt_write_log( serialize( $existing_post ) );
        dt_write_log( serialize( $initial_fields ) );

        // Get Maarifa site links
        $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
        if ( empty( $site_links ) ) {
            return;
        }

        $data = array(
            'type' => 'update',
            'values' => $initial_fields,
            'existing' => $existing_post
        );

        // Send the data to each Maarifa site link (there should only be one, but just in case...)
        foreach ($site_links as $site_link ) {
            dt_write_log( serialize( $site_link ) );
             $site = Site_Link_System::get_site_connection_vars( $site_link['id'] );
            dt_write_log( serialize( $site ) );

            $this->post_to_maarifa( $site['url'], $site['transfer_token'], $maarifa_contact_id, $data );
        }
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
        dt_write_log( json_encode( array(
            'post_type' => $post_type,
            'post_id' => $post_id,
            'created_comment_id' => $created_comment_id,
            'type' => $type
        )));

        // Only send back contacts post types and comments
        if ( $post_type !== 'contacts' || $type !== 'comment') {
            return;
        }

        //todo: get comment and post
        // Get the post
        $post = DT_Posts::get_post( $post_type, $post_id );
//        dt_write_log(json_encode($post));

        // Check if this is a Maarifa-sourced contact
        $maarifa_contact_id = null;
        if ( isset( $post["maarifa_data"] ) ) {
            $maarifa_data = maybe_unserialize( $post["maarifa_data"] );
            if ( isset( $maarifa_data["id"] ) ) {
                $maarifa_contact_id = $maarifa_data["id"];
//                dt_write_log( 'maarifa id:' . $maarifa_contact_id );
            }
        }
        // If not Maarifa-sourced, don't proceed
        if ( empty( $maarifa_contact_id ) ) {
            return;
        }

        // Get the comment itself
        $comments = DT_Posts::get_post_comments( $post_type, $post_id );
        $comment = null;
        if ( !empty( $comments ) && !empty( $comments['comments'] )) {
            $comment_idx = array_search( $created_comment_id, array_column( $comments['comments'], 'comment_ID' ) );
            if ($comment_idx !== false) {
                $comment = $comments['comments'][$comment_idx];
            }
        }

        // If we couldn't find the comment, don't proceed
        if ( empty( $comment ) ) {
            return;
        }

        // Get Maarifa site links
        $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
        if ( empty( $site_links ) ) {
            return;
        }

        $data = array(
            'type' => 'comment',
            'values' => $comment,
            'existing' => $post
        );

        // Send the data to each Maarifa site link (there should only be one, but just in case...)
        foreach ($site_links as $site_link ) {
            dt_write_log( json_encode( $site_link ) );
            $site = Site_Link_System::get_site_connection_vars( $site_link['id'] );
            dt_write_log( json_encode( $site ) );

            $this->post_to_maarifa( $site['url'], $site['transfer_token'], $maarifa_contact_id, $data );
        }
    }

    /**
     * Send all data to the Maarifa RS endpoint
     * @param string $site_url
     * @param string $transfer_token
     * @param int $contact_id
     * @param object $data
     * @since 0.5
     */
    private function post_to_maarifa( $site_url, $transfer_token, $contact_id, $data ) {

        $is_local = strrpos( $site_url, 'local' ) > -1;
        $url = $is_local ? 'http://' : 'https://';
        $url .= str_replace( '.lan', '.org', $site_url );
        $url .= "/response/api/contacts/$contact_id/dt-activity";
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $transfer_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode( $data )
        );
        dt_write_log( $url );
        $result = wp_remote_post( $url, $args );
        // If there is an error, we'll capture it and log it,
        // but then move on and not throw it to the user
        if ( is_wp_error( $result ) ){
            dt_write_log( 'Error sending to Maarifa: ' . serialize( $result ) );
        }
    }
}

Disciple_Tools_Maarifa_Hooks::instance();
