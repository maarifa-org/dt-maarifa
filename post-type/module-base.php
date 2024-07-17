<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class Disciple_Tools_Maarifa_Base
 * Load the core post type hooks into the Disciple.Tools system
 */
class Disciple_Tools_Maarifa_Base extends DT_Module_Base {

    /**
     * Define post type variables
     * @var string
     */
    public $post_type = 'interactions';
    public $module = 'maarifa';
    public $single_name = 'Interaction';
    public $plural_name = 'Interactions';
    public static function post_type(){
        return 'interactions';
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() || true ) { // force disable for now
            return;
        }

        //setup post type
        add_action( 'after_setup_theme', [ $this, 'after_setup_theme' ], 100 );
        add_filter( 'dt_set_roles_and_permissions', [ $this, 'dt_set_roles_and_permissions' ], 20, 1 ); //after contacts

        //setup tiles and fields
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 20, 2 );
        add_filter( 'dt_get_post_type_settings', [ $this, 'dt_get_post_type_settings' ], 20, 2 );
        add_filter( 'dt_comments_additional_sections', array( $this, 'dt_comments_additional_sections' ), 10, 2 );

        //list
        add_filter( 'dt_filter_access_permissions', [ $this, 'dt_filter_access_permissions' ], 20, 2 );
        add_filter( 'dt_search_extra_post_meta_fields', array( $this, 'dt_search_extra_post_meta_fields' ), 10, 1 );

        add_filter( 'dt_data_reporting_field_output', array( $this, 'data_reporting_field_output' ), 10, 4 );

//        add_filter( 'desktop_navbar_menu_options', [ $this, 'desktop_navbar_menu_options' ], 25, 1 );
    }

    public function after_setup_theme(){
        $this->single_name = __( 'Interaction', 'dt_maarifa' );
        $this->plural_name = __( 'Interactions', 'dt_maarifa' );

        if ( class_exists( 'Disciple_Tools_Post_Type_Template' ) ) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

    /**
     * Set the singular and plural translations for this post types settings
     * The add_filter is set onto a higher priority than the one in Disciple_tools_Post_Type_Template
     * so as to enable localisation changes. Otherwise the system translation passed in to the custom post type
     * will prevail.
     */
    public function dt_get_post_type_settings( $settings, $post_type ){
        if ( $post_type === $this->post_type ){
            $settings['label_singular'] = __( 'Interaction', 'dt_maarifa' );
            $settings['label_plural'] = __( 'Interactions', 'dt_maarifa' );
        }
        return $settings;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/roles-permissions.md#rolesd
     */
    public function dt_set_roles_and_permissions( $expected_roles ){

        // if the user can access contact they also can access this post type
        foreach ( $expected_roles as $role => $role_value ){
            if ( isset( $expected_roles[$role]['permissions']['access_contacts'] ) && $expected_roles[$role]['permissions']['access_contacts'] ){
                $expected_roles[$role]['permissions']['access_' . $this->post_type ] = true;
//                $expected_roles[$role]['permissions']['create_' . $this->post_type] = true;
//                $expected_roles[$role]['permissions']['update_' . $this->post_type] = true;
            }
        }

        if ( isset( $expected_roles['dt_admin'] ) ){
            $expected_roles['dt_admin']['permissions']['create_' . $this->post_type] = true;
            $expected_roles['dt_admin']['permissions']['update_' . $this->post_type] = true;
            $expected_roles['dt_admin']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['dt_admin']['permissions']['update_any_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles['administrator'] ) ){
            $expected_roles['administrator']['permissions']['create_' . $this->post_type] = true;
            $expected_roles['administrator']['permissions']['update_' . $this->post_type] = true;
            $expected_roles['administrator']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['update_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['delete_any_'.$this->post_type ] = true;
        }

        return $expected_roles;
    }

    /**
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/fields.md
     */
    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){

            $fields['status'] = [
                'name'        => __( 'Status', 'dt_maarifa' ),
                'description' => __( 'Set the current status.', 'dt_maarifa' ),
                'type'        => 'key_select',
                'default'     => [
                    'waiting' => [
                        'label' => __( 'Waiting', 'dt_maarifa' ),
                        'description' => __( 'Waiting', 'dt_maarifa' ),
                        'color' => '#F43636'
                    ],
                    'answered'   => [
                        'label' => __( 'Answered', 'dt_maarifa' ),
                        'description' => __( 'Answered', 'dt_maarifa' ),
                        'color' => '#4CAF50'
                    ],
                    'outward' => [
                        'label' => __( 'Outward', 'dt_maarifa' ),
                    ],
                    'twoway' => [
                        'label' => __( 'Two-way', 'dt_maarifa' ),
                    ],
                    'ignore' => [
                        'label' => __( 'Ignore', 'dt_maarifa' ),
                    ]
                ],
                'tile'     => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/status.svg',
                'default_color' => '#366184',
                'show_in_table' => 10,
            ];
            $fields['contact'] = [
                'name' => __( 'Contact', 'dt_maarifa' ),
                'description' => '',
                'type' => 'connection',
                'post_type' => 'contacts',
                'p2p_direction' => 'to',
                'p2p_key' => $this->post_type.'_to_contacts',
                'tile' => 'status',
                'icon' => get_template_directory_uri() . '/dt-assets/images/group-type.svg',
                'create-icon' => get_template_directory_uri() . '/dt-assets/images/add-contact.svg',
                'show_in_table' => 1
            ];

            $fields['type'] = [
                'name'        => __( 'Type', 'dt_maarifa' ),
                'description' => __( 'Interaction type.', 'dt_maarifa' ),
                'type'        => 'key_select',
                'default'     => [
                    'form'          => [ 'label' => __( 'Form', 'dt_maarifa' ) ],
                    'app'           => [ 'label' => __( 'App', 'dt_maarifa' ) ],
                    'emailin'       => [ 'label' => __( 'Email In', 'dt_maarifa' ) ],
                    'emailout'      => [ 'label' => __( 'Email Out', 'dt_maarifa' ) ],
                    'emailbad'      => [ 'label' => __( 'Email Bad', 'dt_maarifa' ) ],
                    'comment'       => [ 'label' => __( 'Comment', 'dt_maarifa' ) ],
                    'livechat'      => [ 'label' => __( 'Live Chat', 'dt_maarifa' ) ],
                    'messenger'     => [ 'label' => __( 'Messenger', 'dt_maarifa' ) ],
                    'registration'  => [ 'label' => __( 'Registration', 'dt_maarifa' ) ],
                    'newsletter'    => [ 'label' => __( 'Newsletter', 'dt_maarifa' ) ],
                    'newsletteropen' => [ 'label' => __( 'Newsletter Open', 'dt_maarifa' ) ],
                    'unsubscribe'   => [ 'label' => __( 'Unsubscribe', 'dt_maarifa' ) ],
                    'study'         => [ 'label' => __( 'Study', 'dt_maarifa' ) ],
                    'phone'         => [ 'label' => __( 'Phone', 'dt_maarifa' ) ],
                    'text'          => [ 'label' => __( 'Text', 'dt_maarifa' ) ],
                    'voicemail'     => [ 'label' => __( 'Voicemail', 'dt_maarifa' ) ],
                    'skype'         => [ 'label' => __( 'Skype', 'dt_maarifa' ) ],
                    'whatsapp'      => [ 'label' => __( 'WhatsApp', 'dt_maarifa' ) ],
                    'viber'         => [ 'label' => __( 'Viber', 'dt_maarifa' ) ],
                    'line'          => [ 'label' => __( 'Line', 'dt_maarifa' ) ],
                    'imo'           => [ 'label' => __( 'Imo', 'dt_maarifa' ) ],
                    'telegram'      => [ 'label' => __( 'Telegram', 'dt_maarifa' ) ],
                    'signal'        => [ 'label' => __( 'Signal', 'dt_maarifa' ) ],
                    'facebook'      => [ 'label' => __( 'Facebook', 'dt_maarifa' ) ],
                    'snapchat'      => [ 'label' => __( 'Snapchat', 'dt_maarifa' ) ],
                    'instagram'     => [ 'label' => __( 'Instagram', 'dt_maarifa' ) ],
                    'zoom'          => [ 'label' => __( 'Zoom', 'dt_maarifa' ) ],
                    'paltalk'       => [ 'label' => __( 'PalTalk', 'dt_maarifa' ) ],
                    'reminder'      => [ 'label' => __( 'Reminder', 'dt_maarifa' ) ],
                    'transfer'      => [ 'label' => __( 'Transfer', 'dt_maarifa' ) ],
                    'f2frequest'    => [ 'label' => __( 'F2F Request', 'dt_maarifa' ) ],
                    'f2fupdate'     => [ 'label' => __( 'F2F Update', 'dt_maarifa' ) ],
                    'f2fmeeting'    => [ 'label' => __( 'F2F Meeting', 'dt_maarifa' ) ],
                    'f2fclosed'     => [ 'label' => __( 'F2F Closed', 'dt_maarifa' ) ],
                    'dtupdate'      => [ 'label' => __( 'DT Update', 'dt_maarifa' ) ],
                    'close'         => [ 'label' => __( 'Close', 'dt_maarifa' ) ],
                    'other'         => [ 'label' => __( 'Other', 'dt_maarifa' ) ],
                    'prayerrequest' => [ 'label' => __( 'Prayer Request', 'dt_maarifa' ) ],
                    'private'       => [ 'label' => __( 'Private', 'dt_maarifa' ) ],
                ],
                'tile'     => 'status',
                'show_in_table' => 12,
            ];
            $fields['type_id'] = [
                'name' => __( 'Type ID', 'dt_maarifa' ),
                'type' => 'number',
                'tile' => 'status',
                'show_in_table' => 13,
            ];

//            $fields['responder'] = [
//                'name'        => __( 'Responder', 'dt_maarifa' ),
//                'description' => __( 'Select the main person who is responsible for reporting on this record.', 'dt_maarifa' ),
//                'type'        => 'user_select',
//                'default'     => '',
//                'tile' => 'status',
//                'icon' => get_template_directory_uri() . '/dt-assets/images/assigned-to.svg',
//                'show_in_table' => 16,
//            ];

            $fields['attitude'] = [
                'name'        => __( 'Attitude', 'dt_maarifa' ),
                'description' => __( 'Attitude of contact from this interaction.', 'dt_maarifa' ),
                'type'        => 'key_select',
                'default'     => [
                    'unclear'       => [ 'label' => __( 'Unclear', 'dt_maarifa' ) ],
                    'hostile'       => [ 'label' => __( 'Hostile', 'dt_maarifa' ) ],
                    'uninterested'  => [ 'label' => __( 'Uninterested', 'dt_maarifa' ) ],
                    'open'          => [ 'label' => __( 'Open', 'dt_maarifa' ) ],
                    'very open'     => [ 'label' => __( 'Very Open', 'dt_maarifa' ) ],
                    'believer'      => [ 'label' => __( 'Believer', 'dt_maarifa' ) ],
                    'now seeking'   => [ 'label' => __( 'Now Seeking', 'dt_maarifa' ) ],
                    'lost interest' => [ 'label' => __( 'Lost Interest', 'dt_maarifa' ) ],
                    'began study'   => [ 'label' => __( 'Began Study', 'dt_maarifa' ) ],
                    'profession'    => [ 'label' => __( 'Profession', 'dt_maarifa' ) ],
                    'baptized'      => [ 'label' => __( 'Baptized', 'dt_maarifa' ) ],
                    'joined group'  => [ 'label' => __( 'Joined Group', 'dt_maarifa' ) ],
                    'left group'    => [ 'label' => __( 'Left Group', 'dt_maarifa' ) ],
                    'started group' => [ 'label' => __( 'Started Group', 'dt_maarifa' ) ],
                ],
                'tile'     => 'status',
                'show_in_table' => 15,
            ];

            /**
             * Common and recommended fields
             */
            $fields['date'] = [
                'name'        => __( 'Date', 'dt_maarifa' ),
                'description' => '',
                'type'        => 'date',
                'default'     => time(),
                'tile' => 'details',
            ];
            $fields['note'] = [
                'name'        => __( 'Notes', 'dt_maarifa' ),
                'description' => '',
                'type'        => 'textarea',
                'tile' => 'details',
            ];

            $fields['form_alias'] = [
                'name'        => __( 'Form Alias', 'dt_maarifa' ),
                'description' => '',
                'type'        => 'text',
                'tile' => 'other',
            ];
            $fields['ad_source'] = [
                'name'        => __( 'Ad Source', 'dt_maarifa' ),
                'description' => '',
                'type'        => 'text',
                'tile' => 'other',
            ];
        }

        /**
         * @todo this adds connection to contacts. remove if not needed.
         */
        if ( $post_type === 'contacts' ){
            $fields[$this->post_type] = [
                'name' => $this->plural_name,
                'description' => '',
                'type' => 'connection',
                'post_type' => $this->post_type,
                'p2p_direction' => 'from',
                'p2p_key' => $this->post_type.'_to_contacts',
                'tile' => 'other',
                'show_in_table' => 35
            ];
        }

        return $fields;
    }

    /**
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === $this->post_type ){
            $tiles['connections'] = [ 'label' => __( 'Connections', 'dt_maarifa' ) ];
            $tiles['other'] = [ 'label' => __( 'Other', 'dt_maarifa' ) ];
        }
        return $tiles;
    }

    /**
     * @todo define additional section content
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md#add-custom-content
     */
    public function dt_details_additional_section( $section, $post_type ){

        if ( $post_type === $this->post_type && $section === 'other' ) {
            $fields = DT_Posts::get_post_field_settings( $post_type );
            $post = DT_Posts::get_post( $this->post_type, get_the_ID() );
            ?>
            <div class="section-subheader">
                <?php esc_html_e( 'Custom Section Contact', 'dt_maarifa' ) ?>
            </div>
            <div>
                <p>Add information or custom fields here</p>
            </div>

        <?php }
    }

    public function dt_comments_additional_sections( $sections, $post_type ) {
        if ( $post_type === 'contacts' ) {
            $sections[] = array(
                'key' => 'maarifa',
                'label' => __( 'Maarifa', 'dt_maarifa' )
            );
        }
        return $sections;
    }
    //list page filters function

    /**
     * Add maarifa_data field to contact search
     * @param array $fields
     * @return array
     * @since 0.5.3
     */
    public static function dt_search_extra_post_meta_fields( array $fields ) {
        array_push( $fields, 'maarifa_data' );
        return $fields;
    }

    // access permission
    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === self::post_type() ){
            if ( DT_Posts::can_view_all( $post_type ) ){
                $permissions = [];
            }
        }
        return $permissions;
    }

    public function data_reporting_field_output( $field_value, $type, $field_key, $flatten ) {
        if ( $field_key == 'maarifa_data' ) {
            $data = $field_value;
            if ( is_string( $field_value ) ) {
                $data = json_decode( $field_value, true );
            }
            if ( is_array( $data ) && isset( $data['id'] ) ) {
                return strval( $data['id'] );
            }
            return '';
        }
        return $field_value;
    }

    public function desktop_navbar_menu_options( $tabs ){
        if ( isset( $tabs[$this->post_type] ) ){
            $tabs[$this->post_type]['hidden'] = true;
        }
        return $tabs;
    }
}

