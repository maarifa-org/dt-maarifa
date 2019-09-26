<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly
class Disciple_Tools_Maarifa_Tile
{
    /**
     * Disciple_Tools_Maarifa_Tile The single instance of Disciple_Tools_Maarifa_Tile.
     *
     * @var    object
     * @access private
     * @since  0.4.0
     */
    private static $_instance = null;

    /**
     * Main Disciple_Tools_Maarifa_Tile Instance
     * Ensures only one instance of Disciple_Tools_Maarifa_Tile is loaded or can be loaded.
     *
     * @since  0.4.0
     * @static
     * @return Disciple_Tools_Maarifa_Tile instance
     */
    public static function instance()
    {
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
    public function __construct()
    {
        add_filter( "dt_comments_additional_sections", [ $this, "add_comment_section" ], 10, 2 );
    } // End __construct()

    public function add_comment_section( $sections, $post_type ) {
        if ( $post_type === "contacts" ) {
            $sections[] = [
                "key" => "maarifa",
                "label" => __( "Maarifa", "dt_maarifa" )
            ];
        }
        return $sections;
    }
}

Disciple_Tools_Maarifa_Tile::instance();
