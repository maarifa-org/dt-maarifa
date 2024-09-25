<?php
/**
 * Plugin Name: Disciple Tools - Maarifa
 * Plugin URI: https://github.com/maarifa-org/dt-maarifa
 * Description: Disciple Tools - Maarifa integrates the two platforms by providing access for Maarifa to create and read contacts in Disciple Tools.
 * Version:  0.9.8
 * Author URI: https://github.com/maarifa-org
 * GitHub Plugin URI: https://github.com/maarifa-org/dt-maarifa
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools_Maarifa
 * @link    https://github.com/maarifa-org
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gets the instance of the `DT_Maarifa` class.
 *
 * @since  0.1
 * @access public
 * @return object
 */
function dt_maarifa() {
    $dt_maarifa_required_dt_theme_version = '1.19';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( 'Disciple_Tools' );
    if ( $is_theme_dt && version_compare( $version, $dt_maarifa_required_dt_theme_version, '<' ) ) {
        add_action( 'admin_notices', 'dt_maarifa_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ) {
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ) {
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return DT_Maarifa::instance();

}
add_action( 'after_setup_theme', 'dt_maarifa', 20 );

//register the D.T Plugin
add_filter( 'dt_plugins', function ( $plugins ){
    $plugin_data = get_file_data( __FILE__, [
        'Version' => 'Version',
        'Plugin Name' => 'Plugin Name'
    ], false );
    $plugins['dt-maarifa'] = [
        'plugin_url' => trailingslashit( plugin_dir_url( __FILE__ ) ),
        'version' => $plugin_data['Version'] ?? null,
        'name' => $plugin_data['Plugin Name'] ?? null,
    ];
    return $plugins;
});
/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Maarifa {

    public static $token = 'dt_maarifa';


    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor method.
     *
     * @return void
     * @since  0.1
     * @access private
     */
    private function __construct() {
        $is_rest = dt_is_rest();

        if ( $is_rest && strpos( dt_get_url_path(), 'dt-maarifa' ) !== false ) {       

            require_once( 'rest-api/rest-api.php' ); // adds starter rest api class
        }

        // add post type and modifications to contacts
        require_once( 'post-type/loader.php' );

        // add site to site link class and capabilities
        require_once( 'site-link/maarifa-sitelink.php' );

        require_once( 'tile/maarifa-tile.php' );

        // adds starter admin page and section for plugin
        if ( is_admin() ) {
            require_once( 'admin/admin-menu-and-tabs.php' );
        }

        $this->i18n();

        require_once( 'hooks/maarifa-hooks.php' );

        add_filter( 'allowed_wp_v2_paths', function ( $paths ) {
            array_push( $paths, '/wp/v2/plugins' );
            return $paths;
        } );
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public static function activation() {
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-maarifa' );
    }

    /**
     * Loads the translation files.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_maarifa', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'languages' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @return string
     * @since  0.1
     * @access public
     */
    public function __toString() {
        return 'dt_maarifa';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'dt_maarifa' ), '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @return void
     * @since  0.1
     * @access public
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Whoah, partner!', 'dt_maarifa' ), '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        // @codingStandardsIgnoreLine
        _doing_it_wrong("dt_maarifa::{$method}", esc_html__('Method does not exist.', 'dt_maarifa'), '0.1');
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, array( 'DT_Maarifa', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'DT_Maarifa', 'deactivation' ) );

if ( ! function_exists( 'dt_maarifa_hook_admin_notice' ) ) {
    function dt_maarifa_hook_admin_notice() {
        // todo: add suggestion to install data_reporting plugin if it isn't
        global $dt_maarifa_required_dt_theme_version;
        $wp_theme = wp_get_theme();
        $current_version = $wp_theme->version;
        $message = __( "'Disciple Tools - Maarifa' requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.", 'dt_maarifa' );
        if ( $wp_theme->get_template() === 'disciple-tools-theme' ) {
            $message .= sprintf( esc_html__( 'Current Disciple Tools version: %1$s, required version: %2$s', 'dt_maarifa' ), esc_html( $current_version ), esc_html( $dt_maarifa_required_dt_theme_version ) );
        }
        // Check if it's been dismissed...
        if ( !get_option( 'dismissed-dt-maarifa', false ) ) { ?>
      <div class="notice notice-error notice-dt-maarifa is-dismissible" data-notice="dt-maarifa">
        <p><?php echo esc_html( $message ); ?></p>
      </div>
      <script>
        jQuery(function ($) {
          $(document).on('click', '.notice-dt-maarifa .notice-dismiss', function () {
            $.ajax(ajaxurl, {
              type: 'POST',
              data: {
                action: 'dismissed_notice_handler',
                type: 'dt-maarifa',
                security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
              }
            })
          });
        });
      </script>
        <?php }
    }
}

/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( 'dt_hook_ajax_notice_handler' ) ){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST['type'] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}

/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
add_action( 'plugins_loaded', function () {
    if ( is_admin() && !( is_multisite() && class_exists( 'DT_Multisite' ) ) || wp_doing_cron() ) {
        // Check for plugin updates
        if ( !class_exists( 'Puc_v4_Factory' ) ) {
            if ( file_exists( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ) {
            Puc_v4_Factory::buildUpdateChecker(
                'https://raw.githubusercontent.com/maarifa-org/dt-maarifa/master/version-control.json',
                __FILE__,
                'dt-maarifa'
            );

        }
    }

    $plugin_data = get_file_data( __FILE__, [
        'Version' => 'Version',
    ], false );

    $version_db = get_option( DT_Maarifa::$token . '_version', '' );
    error_log( "Checking dt-maarifa version ($version_db vs " . $plugin_data['Version'] . ')' );
    if ( $version_db !== $plugin_data['Version'] ) {

        require_once( get_template_directory() . '/dt-core/admin/site-link-post-type.php' );

    //        dt_write_log( "plugin_updated($version)" );

        $site_links = Site_Link_System::get_list_of_sites_by_type( array( 'maarifa_link' ) );
        if ( empty( $site_links ) ) {
            return;
        }

        // Send the data to each Maarifa site link (there should only be one, but just in case...)
        foreach ( $site_links as $site_link ) {
            $site = Site_Link_System::get_site_connection_vars( $site_link['id'] );

            // copied from protected Site_Link_System::get_current_site_base_url
            $url = str_replace( 'http://', '', home_url() );
            $url = str_replace( 'https://', '', $url );
            $url = trim( $url );

            $data = array(
                'site' => $url,
                'version' => $plugin_data['Version']
            );
            error_log( 'sending updated dt-maarifa version' );
            error_log( json_encode( $data ) );

            $host = get_option( 'dt_maarifa_api_host' );
            if ( empty( $host ) ) {
                $is_local = strrpos( $site['url'], 'local' ) > -1;
                $host = $is_local ? 'http://' : 'https://';
                $host .= 'api.maarifa.org';
            }

            $url = $host . '/response/api/version';
            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode( $data )
            );

            error_log( $url );
            $result = wp_remote_post( $url, $args );
            if ( is_wp_error( $result ) ){
                error_log( 'Error sending version to Maarifa: ' . serialize( $result ) );
            } else {
                update_option( DT_Maarifa::$token . '_version', $plugin_data['Version'] );
            }
        }
    }
} );
