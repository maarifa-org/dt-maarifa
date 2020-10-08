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
        add_filter( "dt_search_extra_post_meta_fields", array( $this, "dt_search_fields" ), 10, 1 );

        add_filter( "dt_details_additional_section_ids", array( $this, "dt_maarifa_declare_section_id" ), 999, 2 );
        add_action( "dt_details_additional_section", array( $this, "dt_maarifa_add_section" ) );
        add_filter( "dt_comments_additional_sections", array( $this, "add_comment_section" ), 10, 2 );
        add_filter( "dt_data_reporting_field_output", array( $this, "data_reporting_field_output" ), 10, 4 );
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
                    "default" => false,
                    "hidden" => true
                );
            }
        }
        //don't forget to return the update fields array
        return $fields;
    }

  /**
   * Add maarifa_data field to contact search
   * @param array $fields
   * @return array
   * @since 0.5.3
   */
    public static function dt_search_fields( array $fields ) {
        array_push( $fields, "maarifa_data" );
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


            <?php if ( isset( $maarifa_data["id"] ) ): ?>
                <label class="section-header">
                    <?php esc_html_e( "Maarifa", "dt_maarifa" ) ?>
                </label>

                <div class="section-subheader">
                    <?php esc_html_e( "ID", "dt_maarifa" ) ?>
                </div>
                <p><?php echo esc_html( $maarifa_data["id"] ) ?></p>
            <?php endif; ?>
            <?php if ( isset( $maarifa_data["first_contact_details"] ) ): ?>
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
            <?php endif; ?>

            <div class="maarifa-request-info-container">
                <button type="button" id="maarifa-request-info" class="button" data-open="maarifa-request-modal">
                    <?php esc_html_e( "Request Info", "dt_maarifa" ) ?>
                </button>
            </div>

            <div class="reveal" id="maarifa-request-modal" data-reveal data-reset-on-close>
                <h3><?php esc_html_e( 'Request Info', 'dt_maarifa' )?></h3>
                <p><?php esc_html_e( 'To contact the Maarifa team for any reason, enter your message below and the relevant team members will be notified.', 'dt_maarifa' ) ?></p>

                <form class="js-maarifa-send-message">
                    <label for="maarifa-message">
                        <?php esc_html_e( "Message", "dt_maarifa" ); ?>
                    </label>
                    <textarea dir="auto" id="maarifa-message" name="message"
                              placeholder="<?php echo esc_html_x( "Write your comment or note here", 'input field placeholder', 'disciple_tools' ) ?>"
                    ></textarea>

                    <div>
                        <button class="button loader js-send-message-button" id="maarifa-send-message-button" type="submit"><?php echo esc_html__( "Send Message", 'dt_maarifa' ); ?></button>
                        <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                            <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                        </button>
                    </div>
                </form>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    function maarifa_send_message() {
                        let postId = window.detailsSettings.post_id;
                        let postType = window.detailsSettings.post_type;
                        let rest_api = window.API;

                        let commentInput = jQuery("#maarifa-message")
                        let commentButton = jQuery("#maarifa-send-message-button")

                        // Get comment text
                        let message = commentInput.val();

                        if (message) {
                            // Prepend @maarifa tag
                            message = "@[Maarifa](maarifa) " + message;
                            // Loading indicators
                            commentButton.toggleClass('loading');
                            commentInput.attr("disabled", true);
                            commentButton.attr("disabled", true);
                            // Save comment
                            rest_api.post_comment(postType, postId, message, 'comment' ).then(data => {
                                let updated_comment = data.comment || data;
                                // Reset input
                                commentInput.val("").trigger("change");
                                commentButton.toggleClass('loading');
                                updated_comment.date = moment(updated_comment.comment_date_gmt + "Z");

                                commentInput.attr("disabled", false)
                                commentButton.attr("disabled", false)
                                // Close modal
                                $('#maarifa-request-modal').foundation('close');
                            }).catch(err => {
                                console.log("error")
                                console.log(err)
                                jQuery("#errors").append(err.responseText)
                            })
                        }

                    }
                    $('#maarifa-request-info').on('click', function () {
                        masonGrid.masonry('layout');
                        $('#maarifa-request-modal').foundation('open');
                    });
                    $('.js-maarifa-send-message').on('submit', function (evt) {
                        if (evt) {
                            evt.preventDefault();
                        }
                        maarifa_send_message();
                    });
                });
            </script>
            <?php
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

    public function data_reporting_field_output( $field_value, $type, $field_key, $flatten ) {
        if ($field_key == 'maarifa_data' ) {
            $data = $field_value;
            if ( is_string( $field_value ) ) {
                $data = json_decode( $field_value, true );
            }
            if ( is_array( $data ) && isset( $data['id'] ) ) {
                return strval( $data['id'] );
            }
            return "";
        }
        return $field_value;
    }
}

Disciple_Tools_Maarifa_Tile::instance();
