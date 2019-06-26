<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly
/**
 * Class Disciple_Tools_Maarifa_Endpoints
 */
class Disciple_Tools_Maarifa_Endpoints
{
    /**
     * Disciple_Tools_Maarifa_Endpoints The single instance of Disciple_Tools_Maarifa_Endpoints.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_Maarifa_Endpoints Instance
     * Ensures only one instance of Disciple_Tools_Maarifa_Endpoints is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return Disciple_Tools_Maarifa_Endpoints instance
     */
    public static function instance()
    {
        if (is_null( self::$_instance )) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    private $version = 1.0;
    private $context = "dt-maarifa";
    private $namespace;

    /**
     * Constructor function.
     *
     * @access public
     * @since  0.1.0
     */
    public function __construct()
    {
        $this->namespace = $this->context . "/v" . intval( $this->version );

        add_filter( 'site_link_type', [ $this, 'site_link_type' ], 10, 1 );
        add_filter( 'site_link_type_capabilities', [ $this, 'site_link_capabilities' ], 10, 1 );
        add_action( 'dt_post_created', [ $this, 'hook_post_created' ], 10, 3 );

        add_action( 'init', [ $this, 'register_maarifa_source' ] );
    } // End __construct()


    /**
     * Insert 'Maarifa' into list of contact sources
     *
     * @since 0.2
     */
    public function register_maarifa_source() {

        $lists = dt_get_option( "dt_site_custom_lists" );
        if ( isset( $lists, $lists["sources"] ) ) {
            if ( !isset( $lists["sources"]["maarifa"] )) {
                $lists["sources"]["maarifa"] = [
                    'label' => "Maarifa",
                    'key' => "maarifa",
                    'description' => 'Maarifa.org Response System',
                    'enabled' => true,
                ];
                update_option( "dt_site_custom_lists", $lists );
            }
        }
    }

    /**
     * Create new Site Link Type for Maarifa
     *
     * @since 0.1
     * @param $types
     * @return mixed
     */
    public function site_link_type( $types ) {
        if ( !isset( $types["maarifa_link"] ) ) {
            $types["maarifa_link"] = "Maarifa Response System Link";
        }
        return $types;
    }

    /**
     * Add needed permissions to Maarifa Site Link Type
     *
     * @since 0.1
     * @param $args
     * @return mixed
     */
    public function site_link_capabilities( $args ) {
        if ($args['connection_type'] === 'maarifa_link') {
            $args['capabilities'][] = 'create_contacts';
            $args['capabilities'][] = 'update_any_contacts';
            $args['capabilities'][] = 'view_any_contacts';

            $args['capabilities'][] = 'read_location';
        }

        return $args;
    }

    /**
     * Hook for when a new DT post is created.
     * If it is a new contact with the maarifa source, share it with the user
     * configured in the plugin settings.
     *
     * @param $post_type
     * @param $post_id
     * @param $post
     */
    public function hook_post_created( $post_type, $post_id, $post ) {
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
}

Disciple_Tools_Maarifa_Endpoints::instance();
