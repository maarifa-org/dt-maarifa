<?php
/**
 * DT_Maarifa_Menu class for the admin page
 *
 * @class       DT_Maarifa_Menu
 * @version     0.1.0
 * @since       0.1.0
 */
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
    public $page_title = 'Maarifa Plugin';
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
        add_submenu_page( 'dt_extensions', $this->page_title, $this->page_title, 'manage_dt', $this->token, [ $this, 'content' ] );
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
        $reporting_plugin_active = is_plugin_active( 'disciple-tools-data-reporting/disciple-tools-data-reporting.php' );
        ?>
        <div class="wrap">
            <h2><?php esc_attr_e( 'Maarifa Plugin', 'dt_maarifa' ) ?></h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'general' ?>" class="nav-tab <?php ( $tab == 'general' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_maarifa' ) : print ''; ?>"><?php esc_attr_e( 'General', 'dt_maarifa' ) ?></a>

                <?php if ( $reporting_plugin_active ): ?>
                <a href="<?php echo esc_attr( $link ) . 'reporting' ?>" class="nav-tab <?php ( $tab == 'reporting' || ! isset( $tab ) ) ? esc_attr_e( 'nav-tab-active', 'dt_maarifa' ) : print ''; ?>"><?php esc_attr_e( 'Reporting', 'dt_maarifa' ) ?></a>
                <?php endif; ?>
            </h2>

            <?php
            switch ($tab) {
                case "general":
                    $object = new DT_Maarifa_Tab_General();
                    $object->content();
                    break;
                case "reporting":
                    $object = new DT_Maarifa_Tab_Reporting();
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
/**
 * Class DT_Maarifa_Tab_Reporting
 */
class DT_Maarifa_Tab_Reporting
{
    public function content() {

        $this->save_settings();

        ?>

        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }
    public function main_column() {
        $reporting_enabled = get_option( "dt_maarifa_reporting_enabled", false );
        $reporting_url = get_option( "dt_maarifa_reporting_url" );
        $reporting_key = get_option( "dt_maarifa_reporting_apikey" );
        ?>

        <form method="POST" action="">
            <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
            <table class="widefat striped">
            <thead>
                <th colspan="2">Data Reporting Settings</th>
            </thead>
            <tbody>
                <tr>
                    <th><label for="reporting_enabled">Opt-In</label></th>
                    <td>
                        <input type="checkbox"
                               name="reporting_enabled"
                               id="reporting_enabled"
                               value="1"
                               <?php echo $reporting_enabled ? 'checked' : '' ?>
                        />
                        <label for="reporting_enabled">Enable automatic exporting of Maarifa contact data to their reporting data store</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="reporting_url">API Endpoint</label></th>
                    <td>
                        <input type="text"
                               name="reporting_url"
                               id="reporting_url"
                               value="<?php echo esc_attr( $reporting_url ) ?>"
                               style="width:100%;"
                               />
                        <div class="muted">This should be set automatically. If it is blank, please get in touch with your Maarifa technical contact.</div>
                    </td>
                </tr>
                <tr>
                    <th><label for="reporting_apikey">API Key</label></th>
                    <td>
                        <input type="text"
                               name="apikey"
                               id="reporting_apikey"
                               value="<?php echo esc_attr( $reporting_key ) ?>"
                               style="width:100%;"
                               />
                        <div class="muted">This should be set automatically. If it is blank, please get in touch with your Maarifa technical contact.</div>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button type="submit" class="button">Update</button>
                    </td>
                </tr>
            </tbody>
            </table>
        </form>

        <?php
    }

    public function save_settings() {
        if ( !empty( $_POST ) ){
            if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
                update_option( "dt_maarifa_reporting_enabled", isset( $_POST['reporting_enabled'] ) && $_POST['reporting_enabled'] == '1' );
                if ( isset( $_POST['reporting_url'] ) ) {
                    update_option( "dt_maarifa_reporting_url", sanitize_text_field( wp_unslash( $_POST['reporting_url'] ) ) );
                }
                if ( isset( $_POST['apikey'] ) ) {
                    update_option( "dt_maarifa_reporting_apikey", sanitize_text_field( wp_unslash( $_POST['apikey'] ) ) );
                }
            }
        }
    }
}
