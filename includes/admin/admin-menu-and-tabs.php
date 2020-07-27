<?php
/**
 * DT_Maarifa_Menu class for the admin page
 *
 * @class       DT_Maarifa_Menu
 * @version     0.1.0
 * @since       0.1.0
 */
//@todo Replace all instances if DT_Maarifa
if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}
/**
 * Initialize menu class
 */
DT_Maarifa_Menu::instance();
/**
 * Class DT_Maarifa_Menu
 */
class DT_Maarifa_Menu {
    public $token = 'dt_maarifa';
    private static $_instance = null;
    /**
     * DT_Maarifa_Menu Instance
     *
     * Ensures only one instance of DT_Maarifa_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Maarifa_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        add_action( "admin_menu", array( $this, "register_menu" ) );
    } // End __construct()
    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( __( 'Extensions (DT)', 'disciple_tools' ), __( 'Extensions (DT)', 'disciple_tools' ), 'manage_dt', 'dt_extensions', array( $this, 'extensions_menu' ), 'dashicons-admin-generic', 59 );
        add_submenu_page( 'dt_extensions', __( 'Maarifa Plugin', 'dt_maarifa' ), __( 'Maarifa Plugin', 'dt_maarifa' ), 'manage_dt', $this->token, array( $this, 'content' ) );
    }
    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}
    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {
        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }
        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'general';
        }
        $link = 'admin.php?page='.$this->token.'&tab=';
        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Maarifa Plugin', 'dt_maarifa' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_maarifa' ) : print ''; ?>"><?php esc_attr_e( 'General', 'dt_maarifa' ) ?></a>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Maarifa_Tab_General();
                    $object->content();
                    break;
                default:
                    break;
            }
            ?>

        </div><!-- End wrap -->

        <?php
    }
}
/**
 * Class DT_Maarifa_Tab_General
 */
class DT_Maarifa_Tab_General
{
    public function content() {

        $this->save_settings();

        ?>

        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }
    public function main_column() {
        $share_user_id = get_option( "dt_maarifa_share_user_id" );
        $potential_user_list = get_users(
            array(
                'role__in' => array( 'dispatcher', 'administrator', 'dt_admin', 'multiplier', 'marketer', 'strategist' ),
                'order'    => 'ASC',
                'orderby'  => 'display_name',
            )
        );
        ?>

        <table class="widefat striped">
        <thead>
            <th>Auto-Share User</th>
        </thead>
        <tbody>
            <tr>
                <td>
                    <form method="POST" action="">
                        <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
                        <p>Automatically share all contacts that are created with the "Maarifa" source with the selected user.</p>
                        <hr>
                        User:
                        <select name="share_user_id">
                            <option value="">None</option>
                            <?php foreach ( $potential_user_list as $potential_user ): ?>
                            <option
                                value="<?php echo esc_attr( $potential_user->ID ) ?>"
                                <?php echo $share_user_id == $potential_user->ID ? 'selected' : '' ?>
                            >
                                <?php echo esc_attr( $potential_user->display_name ) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="button right">Update</button>
                    </form>
                </td>
            </tr>
        </tbody>
        </table>

        <?php
    }
    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
          <thead>
          <tr><th>Information</th>
          </tr></thead>
          <tbody>
          <tr>
            <td>
              <form method="POST" action="">
                <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
                <input type="hidden" name="site_link_check" value="1"/>
                <h3>Get Server Info</h3>
                <p>Get server IP and location details that may be required for IP whitelisting.</p>
                <button type="submit" class="button right">Get Server Info</button>
                <div style="clear:both;"></div>
                <?php $this->get_server_details() ?>
                <?php $this->site_link_check() ?>
              </form>
            </td>
          </tr>
          </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function save_settings() {
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
                if ( isset( $_POST['share_user_id'] ) ) {
                    update_option( "dt_maarifa_share_user_id", sanitize_text_field( wp_unslash( $_POST['share_user_id'] ) ) );
                }
            }
        }
    }

    public function get_server_details() {
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
                if ( isset( $_POST['site_link_check'] ) ) {

                    $url = 'https://iraq.journey.tools/wp-json/dt-public/v1/sites/site_link_check';
                    $args = array(
                    'method' => 'POST',
                    'body' => array(
                    //          'transfer_token' => $site['transfer_token'],
                    )
                    );

                    $result = wp_remote_post( $url, $args );
                    if ( is_wp_error( $result ) ){
                        print_r( $result );
        //              echo "<pre>$result</pre>";
        //              return $result;
                    }
                    $result_body = json_decode( $result['body'] );
                    if ($result_body) {
                        echo "<div style='overflow-x:scroll;max-width:258px;'>";
                        echo "<pre><code style='display:block;'>";
                        echo json_encode( $result_body, JSON_PRETTY_PRINT );
                        echo "</code></pre>";
                        echo "</div>";
                    } else {
                        echo "<p style='color:red'>An error occurred and the server info cannot be retrieved right now.</p>";
                        echo "<div style='display:none;'><pre><code>";
                        echo esc_html( $result['body'] );
                        echo "</code></pre></div>";
                    }
                }
            }
        }
    }
    public function site_link_check() {
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
                if ( isset( $_POST['site_link_check'] ) ) {

                    $url = 'http://ifconfig.co/json';
                    $args = array(
                    'method' => 'GET',
                    );

                    $result = wp_remote_post( $url, $args );
                    if ( is_wp_error( $result ) ){
                        print_r( $result );
        //              echo "<pre>$result</pre>";
        //              return $result;
                    }
                    $result_body = json_decode( $result['body'] );
                    if ($result_body) {
                        echo "<div style='overflow-x:scroll;max-width:258px;'>";
                        echo "<pre><code style='display:block;'>";
                        echo json_encode( $result_body, JSON_PRETTY_PRINT );
                        echo "</code></pre>";
                        echo "</div>";
                    } else {
                        echo "<p style='color:red'>An error occurred and the server info cannot be retrieved right now.</p>";
                        echo "<div style='display:none;'><pre><code>";
                        echo esc_html( $result['body'] );
                        echo "</code></pre></div>";
                    }
                }
            }
        }
    }
}
