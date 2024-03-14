<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly
/**
 * Class Disciple_Tools_Maarifa_SiteLink
 */
class Disciple_Tools_Maarifa_SiteLink
{
    /**
     * Disciple_Tools_Maarifa_SiteLink The single instance of Disciple_Tools_Maarifa_SiteLink.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_Maarifa_SiteLink Instance
     * Ensures only one instance of Disciple_Tools_Maarifa_SiteLink is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return Disciple_Tools_Maarifa_SiteLink instance
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
        add_filter( 'site_link_type', array( $this, 'site_link_type' ), 10, 1 );
        add_filter( 'site_link_type_capabilities', array( $this, 'site_link_capabilities' ), 10, 1 );

        add_action( 'after_setup_theme', array( $this, 'register_maarifa_source' ) );
    } // End __construct()


    /**
     * Insert 'Maarifa' into list of contact sources
     *
     * @since 0.2
     */
    public function register_maarifa_source() {

        $lists = dt_get_option( 'dt_site_custom_lists' );
        if ( isset( $lists, $lists['sources'] ) ) {
            if ( !isset( $lists['sources']['maarifa'] ) ) {
                $lists['sources']['maarifa'] = array(
                    'label' => 'Maarifa',
                    'key' => 'maarifa',
                    'description' => 'Maarifa.org Response System',
                    'enabled' => true,
                );
                update_option( 'dt_site_custom_lists', $lists );
            }
        }
    }

    /**
     * Create new Site Link Type for Maarifa
     *
     * @since 0.1
     * @param object $types
     * @return mixed
     */
    public function site_link_type( $types ) {
        if ( !isset( $types['maarifa_link'] ) ) {
            $types['maarifa_link'] = 'Maarifa Response System Link';
        }
        return $types;
    }

    /**
     * Add needed permissions to Maarifa Site Link Type
     *
     * @since 0.1
     * @param object $args
     * @return mixed
     */
    public function site_link_capabilities( $args ) {
        if ( $args['connection_type'] === 'maarifa_link' ) {
            $args['capabilities'][] = 'create_contacts';
            $args['capabilities'][] = 'update_any_contacts';
            $args['capabilities'][] = 'view_any_contacts';

            $args['capabilities'][] = 'create_interactions';
            $args['capabilities'][] = 'update_any_interactions';
            $args['capabilities'][] = 'view_any_interactions';

            $args['capabilities'][] = 'read_location';
        }

        return $args;
    }
}

Disciple_Tools_Maarifa_SiteLink::instance();
