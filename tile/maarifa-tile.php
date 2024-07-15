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
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_maarifa_add_section' ], 30, 2 );
    } // End __construct()

    /**
     * This function registers a new tile to a specific post type
     *
     * @param array $tiles
     * @param string $post_type
     * @return mixed
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ) {
        if ( $post_type === 'contacts' ){
            $tiles['dt_maarifa'] = [ 'label' => __( 'Maarifa', 'dt_maarifa' ) ];
        }
        return $tiles;
    }

    public static function dt_maarifa_add_section( $section, $post_type ) {
        if ( $post_type === 'contacts' && $section === 'dt_maarifa' ){

            $contact_id = get_the_ID();
            $contact = DT_Posts::get_post( 'contacts', $contact_id, true, true );
            $maarifa_data = array();
            if ( isset( $contact['maarifa_data'] ) ) {
                $maarifa_data = maybe_unserialize( $contact['maarifa_data'] );
            }
            ?>
            <style type="text/css">
                #contact_maarifa_data-tile { font-size: 14px; }
                #contact_maarifa_data-tile .section-subheader {
                    border-bottom: solid 1px #d3d3d3;
                }
                .ip-location-details dl {
                    display: flex;
                    flex-direction: row;
                    flex-wrap: wrap;
                }
                .ip-location-details dt:nth-of-type(even),
                .ip-location-details dd:nth-of-type(even) {
                    background-color: #f5f5f5;
                }
                .ip-location-details dt, .ip-location-details dd { margin-bottom: 0.3rem; }
                .ip-location-details dt { width: 30%; }
                .ip-location-details dd { width: 70%; }
            </style>

            <?php if ( isset( $maarifa_data['id'] ) ): ?>

                <div class="section-subheader section-subheader-maarifa">
                    <?php esc_html_e( 'ID', 'dt_maarifa' ) ?>
                </div>
                <p><?php echo esc_html( $maarifa_data['id'] ) ?></p>
            <?php endif; ?>
            <?php if ( isset( $maarifa_data['first_contact'] ) ): ?>
                <div class="section-subheader">
                    <?php esc_html_e( 'First Contact', 'dt_maarifa' ) ?>
                </div>
                <p>
                    <?php echo esc_html( $maarifa_data['first_contact'] ) ?>
                </p>
            <?php endif; ?>

            <?php if ( isset( $maarifa_data['first_contact_details'] ) && isset( $maarifa_data['first_contact_details']['title'] ) ): ?>
                <div class="section-subheader">
                    <?php esc_html_e( 'Campaign', 'dt_maarifa' ) ?>
                </div>
                <div class="campaign-details" style="margin-bottom: 1rem;">
                    <div class="campaign-title" style="font-weight: 500"><?php echo esc_html( $maarifa_data['first_contact_details']['title'] ) ?></div>

                    <?php if ( isset( $maarifa_data['first_contact_details']['subtitle'] ) && $maarifa_data['first_contact_details']['title'] !== $maarifa_data['first_contact_details']['subtitle'] ): ?>
                        <div class="campaign-subtitle"><?php echo esc_html( $maarifa_data['first_contact_details']['subtitle'] ) ?></div>
                    <?php endif; ?>

                    <?php if ( isset( $maarifa_data['first_contact_details']['dates'] ) ): ?>
                        <div>
                            <span class="campaign-dates-label" style="font-weight: 500">
                                <?php echo esc_html( 'Dates', 'dt_maarifa' ) . ': ' ?>
                            </span>
                            <span class="campaign-dates">
                                <?php echo esc_html( $maarifa_data['first_contact_details']['dates'] ) ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $maarifa_data['first_contact_details']['description'] ) ): ?>
                        <div class="campaign-description" style="font-style:italic">
                            <?php echo esc_html( $maarifa_data['first_contact_details']['description'] ) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $maarifa_data['first_contact_details']['url'] ) ): ?>
                        <a href="<?php echo esc_attr( $maarifa_data['first_contact_details']['url'] ) ?>" target="_blank">
                            <?php echo esc_html( 'View Campaign', 'dt_maarifa' ) ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ( isset( $maarifa_data['location_details'] ) && !empty( $maarifa_data['location_details'] ) ): ?>
                <div class="section-subheader">
                    <?php esc_html_e( 'IP Location Info', 'dt_maarifa' ) ?>
                </div>
                <div class="ip-location-details" style="margin-bottom: 1rem;">
                    <dl>
                        <?php if ( isset( $maarifa_data['location_details']['country'] ) ): ?>
                        <dt>Country</dt>
                        <dd><?php echo esc_html( $maarifa_data['location_details']['country'] ) ?></dd>
                        <?php endif; ?>

                        <?php if ( isset( $maarifa_data['location_details']['city'] ) ): ?>
                        <dt>City</dt>
                        <dd><?php echo esc_html( $maarifa_data['location_details']['city'] ) ?></dd>
                        <?php endif; ?>

                        <?php if ( isset( $maarifa_data['location_details']['query'] ) ): ?>
                        <dt>IP</dt>
                        <dd><a href="https://ip-api.com/<?php echo esc_attr( $maarifa_data['location_details']['query'] ) ?>" target="_blank"><?php echo esc_html( $maarifa_data['location_details']['query'] ) ?></a></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>

            <div class="maarifa-request-info-container">
                <button type="button" id="maarifa-request-info" class="button" data-open="maarifa-request-modal">
                    <?php esc_html_e( 'Request Info', 'dt_maarifa' ) ?>
                </button>
            </div>

            <pre style="display: none;"><code style="display: block;"><?php echo json_encode( $maarifa_data, JSON_PRETTY_PRINT ) ?></code></pre>

            <div class="reveal" id="maarifa-request-modal" data-reveal data-reset-on-close>
                <h3><?php esc_html_e( 'Request Info', 'dt_maarifa' )?></h3>
                <p><?php esc_html_e( 'To contact the Maarifa team for any reason, enter your message below and the relevant team members will be notified.', 'dt_maarifa' ) ?></p>

                <form class="js-maarifa-send-message">
                    <label for="maarifa-message">
                        <?php esc_html_e( 'Message', 'dt_maarifa' ); ?>
                    </label>
                    <textarea dir="auto" id="maarifa-message" name="message"
                              placeholder="<?php echo esc_html_x( 'Write your comment or note here', 'input field placeholder', 'disciple_tools' ) ?>"
                    ></textarea>

                    <div>
                        <button class="button loader js-send-message-button" id="maarifa-send-message-button" type="submit"><?php echo esc_html__( 'Send Message', 'dt_maarifa' ); ?></button>
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
}

Disciple_Tools_Maarifa_Tile::instance();
