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
        add_filter( "dt_custom_fields_settings", array( $this, "dt_contact_fields" ), 10, 2 );

        add_filter( "dt_details_additional_section_ids", array( $this, "dt_maarifa_declare_section_id" ), 999, 2 );
        add_action( "dt_details_additional_section", array( $this, "dt_maarifa_add_section" ) );
        add_filter( "dt_comments_additional_sections", array( $this, "add_comment_section" ), 10, 2 );
    } // End __construct()

    public static function dt_contact_fields( array $fields, string $post_type = ""){
        //check if we are dealing with a contact
        if ($post_type === "contacts"){
            // Define a field to store misc Maarifa data for the tile
            if ( !isset( $fields["maarifa_data"] )){
                $fields["maarifa_data"] = array(
                    "name" => __( "Maarifa Data", "dt_maarifa" ),
                    "type" => "array",
                    "default" => array()
                );
            }
            // Define a field to detect when post updates come from Maarifa so we can avoid sending it back again
            if ( !isset( $fields["maarifa_sync"] )){
                $fields["maarifa_sync"] = array(
                    "name" => __( "Maarifa Sync", "dt_maarifa" ),
                    "type" => "bool",
                    "default" => false
                );
            }
        }
        //don't forget to return the update fields array
        return $fields;
    }

    public static function dt_maarifa_declare_section_id( $sections, $post_type = "" ) {
        //check if we are on a contact
        if ( $post_type === "contacts" ) {
            $contact_fields = Disciple_Tools_Contact_Post_Type::instance()->get_custom_fields_settings();
            //check if the language field is set
            //check if content is there before adding empty tile
            $contact_id    = get_the_ID();
            if ( $contact_id ){
                $contact       = Disciple_Tools_Contacts::get_contact( $contact_id, true, true );
                if ( isset( $contact["maarifa_data"] ) ) {
                    if ( isset( $contact_fields["maarifa_data"] ) ) {
                        $sections[] = "contact_maarifa_data";
                    }
                }
            }
        }
        return $sections;
    }

    public static function dt_maarifa_add_section( $section ) {
        if ( $section == "contact_maarifa_data" ) {
            $contact_id    = get_the_ID();
            $contact       = Disciple_Tools_Contacts::get_contact( $contact_id, true, true );
            $maarifa_data = array();
            if ( isset( $contact["maarifa_data"] ) ) {
                $maarifa_data = maybe_unserialize( $contact["maarifa_data"] );
            }
            ?>


            <?php
            if ( isset( $maarifa_data["id"] ) ) {
                ?>
                <label class="section-header">
                    <?php esc_html_e( "Maarifa", "dt_maarifa" ) ?>
                </label>

                <div class="section-subheader">
                    <?php esc_html_e( "ID", "dt_maarifa" ) ?>
                </div>
                <p><?php echo esc_html( $maarifa_data["id"] ) ?></p>
                <?php
            }
            if ( isset( $maarifa_data["first_contact_details"] ) ) {
                ?>
                <div class="section-subheader">
                    <?php esc_html_e( "First Contact from Campaign", "dt_maarifa" ) ?>
                </div>
                <p>
                    <?php echo esc_html( $maarifa_data["first_contact_details"]["title"] ) ?>

                    <?php if ( isset( $maarifa_data["first_contact_details"]["description"] ) ): ?>
                        <br>
                        <span class="campaign-description" style="font-style:italic">
                            <?php echo esc_html( $maarifa_data["first_contact_details"]["description"] ) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( isset( $maarifa_data["first_contact_details"]["dates"] ) ): ?>
                        <br>
                        <span class="campaign-dates-label" style="font-weight: 500">
                            <?php echo esc_html( "Dates", "dt_maarifa" ) . ": " ?>
                        </span>
                        <span class="campaign-dates">
                            <?php echo esc_html( $maarifa_data["first_contact_details"]["dates"] ) ?>
                        </span>
                    <?php endif; ?>
                </p>
                <?php
            }
        }
    }

    public function add_comment_section( $sections, $post_type ) {
        if ( $post_type === "contacts" ) {
            $sections[] = array(
                "key" => "maarifa",
                "label" => __( "Maarifa", "dt_maarifa" )
            );
        }
        return $sections;
    }

    public function add_duplicate_check_field( $fields ) {
        $fields[] = "maarifa_data";
        return $fields;
    }
}

Disciple_Tools_Maarifa_Tile::instance();
