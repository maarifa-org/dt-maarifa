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

        add_filter( 'site_link_type_capabilities', [ $this, 'site_link_capabilities' ], 10, 1 );
    } // End __construct()

    public function site_link_capabilities( $args ) {
        if ('contact_sharing' === $args['connection_type']) {
            $args['capabilities'][] = 'access_contacts';
            $args['capabilities'][] = 'create_contacts';
            $args['capabilities'][] = 'delete_any_contacts';
            $args['capabilities'][] = 'update_any_contacts';
            $args['capabilities'][] = 'view_any_contacts';

            $args['capabilities'][] = 'read_location';
            $args['capabilities'][] = 'publish_locations';
        }

        return $args;
    }
}

Disciple_Tools_Maarifa_Endpoints::instance();
