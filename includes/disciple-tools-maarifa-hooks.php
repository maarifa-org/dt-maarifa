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
        add_action( 'dt_post_updated', array( $this, 'post_updated' ), 10, 3 );
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
     *
     * @param $post_type
     * @param $post_id
     * @param $initial_fields
     * @param $existing_post
     *
     * @since 0.5.0
     */
    public function post_updated( $post_type, $post_id, $initial_fields, $existing_post ) {

        // todo: something
    }
}

Disciple_Tools_Maarifa_Hooks::instance();
