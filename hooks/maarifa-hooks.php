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
     * @since 0.5.0
     */
    private static $_instance = null;

    private $version = null;
    private $api_host = 'api.maarifa.org';

    /**
     * Main Disciple_Tools_Maarifa_Hooks Instance
     * Ensures only one instance of Disciple_Tools_Maarifa_Hooks is loaded or can be loaded.
     *
     * @since 0.5.0
     * @static
     * @return Disciple_Tools_Maarifa_Hooks instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
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
        // Get the plugin version to use in API calls
        if ( !function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_data = get_plugin_data( dirname( __FILE__, 2 ) . '/disciple-tools-maarifa.php' );
        $this->version = $plugin_data['Version'];

        add_action( 'dt_post_created', array( $this, 'post_created' ), 10, 3 );
        add_action( 'dt_post_updated', array( $this, 'post_updated' ), 10, 4 );
        add_action( 'dt_comment_created', array( $this, 'comment_created' ), 10, 4 );

        add_filter( 'dt_filter_post_comments', [ $this, 'parse_comment_audio_url' ], 10, 3 );

        add_filter( 'dt_assignable_users_compact', array( $this, 'assignable_users_compact' ), 10, 3 );

        add_filter( 'dt_data_reporting_configurations', array( $this, 'data_reporting_configurations' ), 10, 1 );

        add_filter( 'dt_filter_comment_types_receiving_comment_notification', array( $this, 'comment_type_notifications' ) );
    } // End __construct()

    /**
     * Hook for when a new DT post is created.
     * If it is a new contact with the maarifa source, share it with the user
     * configured in the plugin settings.
     *
     * @param string $post_type
     * @param string $post_id
     * @param object $post
     *
     * @since 0.3.0
     */
    public function post_created( $post_type, $post_id, $post ) {
        // If this is a new contact...
        if ( $post_type === 'contacts' ) {

            if ( isset( $post['sources'] ) && isset( $post['sources']['values'] ) ) {
                $maarifa_source = array_search( 'maarifa', array_column( $post['sources']['values'], 'value' ) );

                // ...and if this user has the maarifa source...
                if ( $maarifa_source !== false ) {
                    // Get auto-share user from plugin options
                    $share_user = get_option( 'dt_maarifa_share_user_id' );

                    if ( !empty( $share_user ) ) {
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
     *   updates from Maarifa, and reminders (requires_update)
     *
     * @param string $post_type
     * @param string $post_id
     * @param object $initial_fields
     * @param object $existing_post
     *
     * @since 0.5.0
     */
    public function post_updated( $post_type, $post_id, $initial_fields, $existing_post ) {

        // dt_write_log( 'hook:post_updated' );

        // Only send back contacts post types
        if ( $post_type !== 'contacts' ) {
            return;
        }

        // If this is just a notification about requiring an update, no need to sync
        if ( isset( $initial_fields['requires_update'] ) && $initial_fields['requires_update'] ) {
            return;
        }

        // Check for automated updates from RS
        if ( isset( $initial_fields['maarifa_sync'] ) && $initial_fields['maarifa_sync'] == true ) {
            return;
        }

        // Check if this is a Maarifa-sourced contact
        $maarifa_contact_id = null;
        if ( isset( $existing_post['maarifa_data'] ) ) {
            $maarifa_data = maybe_unserialize( $existing_post['maarifa_data'] );
            if ( isset( $maarifa_data['id'] ) ) {
                $maarifa_contact_id = $maarifa_data['id'];
                dt_write_log( 'maarifa id:' . $maarifa_contact_id );
            }
        }
        // If not Maarifa-sourced, don't proceed
        if ( empty( $maarifa_contact_id ) ) {
            return;
        }
        // dt_write_log( serialize( $existing_post ) );
        // dt_write_log( serialize( $initial_fields ) );

        // Get Maarifa site links
        $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
        if ( empty( $site_links ) ) {
            return;
        }

        $data = array(
            'type' => 'update',
            'values' => $initial_fields,
            'existing' => $existing_post,
            'plugin_version' => $this->version
        );

        // Send the data to each Maarifa site link (there should only be one, but just in case...)
        foreach ( $site_links as $site_link ) {
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
     * @param string $post_type
     * @param string $post_id
     * @param int $created_comment_id
     * @param string $type - Comment type (e.g. "comment")
     * @since 0.5.0
     */
    public function comment_created( $post_type, $post_id, $created_comment_id, $type ) {

        // Only send back contacts post types and comments
        if ( $post_type !== 'contacts' || $type !== 'comment' ) {
            return;
        }

        // Get the post
        $post = DT_Posts::get_post( $post_type, $post_id );
        // If an error occurs (including missing permissions), don't proceed
        if ( is_wp_error( $post ) ) {
            return;
        }

        // Check if this is a Maarifa-sourced contact
        $maarifa_contact_id = null;
        if ( isset( $post['maarifa_data'] ) ) {
            $maarifa_data = maybe_unserialize( $post['maarifa_data'] );
            if ( isset( $maarifa_data['id'] ) ) {
                $maarifa_contact_id = $maarifa_data['id'];
            }
        }
        // If not Maarifa-sourced, don't proceed
        if ( empty( $maarifa_contact_id ) ) {
            return;
        }

        // Get the comment itself
        // Since we have to get a list of comments instead of getting it by id,
        // we need to search the resulting array for the comment id.
        // Luckily, that method returns them in reverse chronological order,
        // so this new comment should be the first in the list (or one of the first).
        $comments = DT_Posts::get_post_comments( $post_type, $post_id );
        $comment = null;
        if ( !empty( $comments ) && !empty( $comments['comments'] ) ) {
            $comment_idx = array_search( $created_comment_id, array_column( $comments['comments'], 'comment_ID' ) );
            if ( $comment_idx !== false ) {
                $comment = $comments['comments'][$comment_idx];
            }
        }

        // If we couldn't find the comment, don't proceed
        if ( empty( $comment ) ) {
            return;
        }

        // If the comment author is "Updated Needed", ignore it because this is an automated comment
        if ( isset( $comment['comment_author'] ) && $comment['comment_author'] == 'Updated Needed' ) {
            return;
        }

        // Get Maarifa site links
        $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
        if ( empty( $site_links ) ) {
            return;
        }

        // Send the data to each Maarifa site link (there should only be one, but just in case...)
        $data = array(
            'type' => 'comment',
            'values' => $comment,
            'existing' => $post,
            'plugin_version' => $this->version
        );
        foreach ( $site_links as $site_link ) {
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
     * @since 0.5.0
     */
    private function post_to_maarifa( $site_url, $transfer_token, $contact_id, $data ) {

        $url = $this->get_api_host( $site_url );
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

    /**
     * @param object $configurations
     * @return mixed
     */
    public function data_reporting_configurations( $configurations ) {
        $reporting_enabled = get_option( 'dt_maarifa_reporting_enabled', false );
        $reporting_url = get_option( 'dt_maarifa_reporting_url' );
        $reporting_key = get_option( 'dt_maarifa_reporting_apikey' );

        // If the url/key is not configured, try to fetch it from the API
        if ( empty( $reporting_url ) || empty( $reporting_key ) ) {
            // Get Maarifa site links
            $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
            if ( !empty( $site_links ) ) {

                // Get the config from each Maarifa site link (there should only be one, but just in case...)
                foreach ( $site_links as $site_link ) {
                    $site = Site_Link_System::get_site_connection_vars( $site_link['id'] );

                    [ $reporting_url, $reporting_key ] = $this->get_reporting_configuration( $site['url'], $site['transfer_token'] );

                    // If we got values back, save them to wp_options for use next time
                    if ( !empty( $reporting_url ) ) {
                        update_option( 'dt_maarifa_reporting_url', $reporting_url );
                    }
                    if ( !empty( $reporting_key ) ) {
                        update_option( 'dt_maarifa_reporting_apikey', $reporting_key );
                    }
                }
            }
        }

        if ( !empty( $reporting_url ) && !empty( $reporting_key ) ) {
            $configurations['maarifa'] = array(
                'name' => 'Maarifa',
                'url' => $reporting_url,
                'token' => $reporting_key,
                'active' => $reporting_enabled,
                'contacts_filter' => array(
                    'sources' => array( 'maarifa' ),
                ),
                'data_types' => array(
                    'contacts' => array(
                        'all_data' => false,
                        'limit' => 100,
                        'schedule' => 'daily',
                    ),
                    'contact_activity' => array(
                        'all_data' => false,
                        'limit' => 1000,
                        'schedule' => 'daily',
                    ),
                )
            );
        }
        return $configurations;
    }

    private function get_reporting_configuration( $site_url, $transfer_token ) {
        $url = $this->get_api_host( $site_url );
        $url .= '/response/api/reporting-config';
        $args = array(
            'timeout' => 30, // 30s timeout
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . $transfer_token,
                'Content-Type' => 'application/json'
            )
        );
        dt_write_log( $url );
        $result = wp_remote_post( $url, $args );
        // If there is an error, we'll capture it and log it,
        // but then move on and not throw it to the user
        if ( is_wp_error( $result ) ){
            dt_write_log( 'Error sending to Maarifa: ' . serialize( $result ) );
        } else {
            $result_body = json_decode( $result['body'], true );
            if ( $result_body['success'] && isset( $result_body['data'] ) ) {
                return [ $result_body['data']['url'], $result_body['data']['key'] ];
            }
        }
        return;
    }

    public function parse_comment_audio_url( $comments, $post_type, $post_id ) {
        $media_host = get_option( 'dt_maarifa_media_host' );
        if ( empty( $media_host ) ) {
            return $comments;
        }

        foreach ( $comments as $id => $comment ) {
            if ( $comment['comment_type'] === 'maarifa' && strpos( $comment['comment_content'], 'Type: Voicemail' ) > -1 ) {
                if ( !key_exists( 'audio_url', $comment['comment_meta'] ) ) {
                    $audio_url = str_replace( 'Type: Voicemail', '', $comment['comment_content'] );
                    $audio_url = trim( preg_replace( '/Responder: \S+/m', '', $audio_url ) );

                    $comments[$id]['comment_meta']['audio_url'][] = [
                        'value' => "$media_host$audio_url",
                    ];
                }
            }
        }
        return $comments;
    }

    public function assignable_users_compact( $list, $search_string, $get_all ) {
        if ( empty( $search_string ) || strpos( 'maarifa', strtolower( $search_string ) ) > -1 ) {
            array_push($list, array(
                'name' => 'Maarifa',
                'ID' => 'maarifa',
            ));
        }
        return $list;
    }

    public function comment_type_notifications( $comment_types ) {
        $comment_types[] = 'maarifa';
        return $comment_types;
    }
    private function get_api_host( $site_url ) {

        $api_host = get_option( 'dt_maarifa_api_host' );
        if ( !empty( $api_host ) ) {
            return $api_host;
        }

        $is_local = strrpos( $site_url, 'local' ) > -1;
        $host = $is_local ? 'http://' : 'https://';
//        $host .= $is_local ? 'localhost:5000' : $this->api_host;
        $host .= $this->api_host;
        return $host;
    }
}

Disciple_Tools_Maarifa_Hooks::instance();
