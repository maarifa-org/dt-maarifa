<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Maarifa_Endpoints
{
    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

        private $version = 1;
        private $context = 'dt-maarifa';
        private $namespace;
        private $geocoding_enabled = false;


    public function __construct()
    {
        $this->namespace = $this->context . '/v' . intval( $this->version );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        if ( ( class_exists( 'DT_Mapbox_API' ) && DT_Mapbox_API::get_key() ) || ( class_exists( 'Disciple_Tools_Google_Geocode_API' ) && Disciple_Tools_Google_Geocode_API::get_key() ) ) {
            $this->geocoding_enabled = true;
        }
    }

    public function add_api_routes() {

        $arg_schemas = [
            'post_type' => [
                'description' => 'The post type',
                'type' => 'string',
                'required' => true,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],
            'id' => [
                'description' => 'The id of the post',
                'type' => 'integer',
                'required' => true,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],
            'comment_id' => [
                'description' => 'The id of the comment',
                'type' => 'integer',
                'required' => true,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],
            'date' => [
                'description' => 'The date the comment was made',
                'type' => 'string',
                'required' => false,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],
            'comment_type' => [
                'description' => 'The type of the comment',
                'type' => 'string',
                'required' => false,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],
            'maarifa_data' => [
                'description' => 'The Maarifa id of the comment',
                'type' => 'string',
                'required' => false,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],

        ];

        //version - Get plugin version
        register_rest_route(
            $this->namespace, '/version', [
                [
                'methods'  => 'GET',
                'callback' => [ $this, 'get_version' ],
                'permission_callback' => '__return_true',
                ]
            ]
        );

        //create_post - Create or update contact
        register_rest_route(
            $this->namespace, '/(?P<post_type>\w+)/', [

                [
                'methods'  => 'POST',
                'callback' => [ $this, 'create_post' ],
                'args' => [
                    'post_type' => $arg_schemas['post_type'],
                    'maarifa_data' => $arg_schemas['maarifa_data'],
                ],
                'permission_callback' => '__return_true',
                ]
            ]
        );

        //update_post - Update contact by given DT contact ID
        register_rest_route(
            $this->namespace, '/(?P<post_type>\w+)/(?P<id>\d+)', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'update_post' ],
                    'args' => [
                        'post_type' => $arg_schemas['post_type'],
                        'id' => $arg_schemas['id']
                    ],
                    'permission_callback' => '__return_true',
                ]
            ]
        );

        //add_interactions - Create new interactions/comments on an existing contact
        register_rest_route(
            $this->namespace, '/(?P<post_type>\w+)/(?P<id>\d+)/interactions', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'add_interactions' ],
                    'permission_callback' => '__return_true',
                ]
            ]
        );

        // cleanup_duplicates - Remove duplicate maarifa interactions/comments
        register_rest_route(
            $this->namespace, '/cleanup-duplicates', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'cleanup_duplicates' ],
                    'permission_callback' => [ $this, 'check_cleanup_permission' ],
                ]
            ]
        );
    }

    public function prefix_validate_args( $value, $request, $param ){

        dt_write_log( 'Prefix_validate_args' );

        $attributes = $request->get_attributes();

        if ( isset( $attributes['args'][ $param ] ) ) {
            $argument = $attributes['args'][ $param ];
            // Check to make sure our argument is a string.
            if ( 'string' === $argument['type'] && ! is_string( $value ) ) {
                return new WP_Error( 'rest_invalid_param', sprintf( '%1$s is not of type %2$s', $param, 'string' ), array( 'status' => 400 ) );
            }
            if ( 'integer' === $argument['type'] && ! is_numeric( $value ) ) {
                return new WP_Error( 'rest_invalid_param', sprintf( '%1$s is not of type %2$s', $param, 'integer' ), array( 'status' => 400 ) );
            }
            if ( $param === 'post_type' ){

                $post_types = DT_Posts::get_post_types();


                // Support advanced search all post type option
                if ( ( $value !== 'all' ) && ! in_array( $value, $post_types ) ) {
                    return new WP_Error( 'rest_invalid_param', sprintf( '%1$s is not a valid post type', $value ), array( 'status' => 400 ) );
                }
            }
        } else {
                // This code won't execute because we have specified this argument as required.
                // If we reused this validation callback and did not have required args then this would fire.
                return new WP_Error( 'rest_invalid_param', sprintf( '%s was not registered as a request argument.', $param ), array( 'status' => 400 ) );
        }

        // If we got this far then the data is valid.
        return true;
    }

    public function map_fields_to_contact( $contact_map ) {

        $fields_map = array(
            'title' => $contact_map['name'],
            'type' => 'access',
            'milestones' => [],
            'maarifa_data' => $contact_map['id'],
        );

        $fields_map['sources']['values'][0]['value'] = 'maarifa';
        $fields_map['maarifa_data'] = $contact_map;

        if ( !empty( $contact_map['tags'] ) ) {
            foreach ( $contact_map['tags'] as $key => $value ) {
                $fields_map['tags']['values'][] = [ 'value' => $value['alias'] ];
            }
        }

        if ( !empty( $contact_map['responders_name'] ) ) {
            // 1. Assemble the simple list of objects
            foreach ( $contact_map['responders_name'] as $name ) {
                $new_responder = $name;
                $responders_name[] = [ 'value' => $new_responder ];
            }

            $fields_map['responders_name'] = [
                'values'       => $responders_name,
                'force_values' => true
            ];
        }

        if ( !empty( $contact_map['email'] ) ) {
            $str_arr = preg_split( '/\,/', $contact_map['email'] );

            $count = count( $str_arr );
            for ( $i = 0; $i < $count; $i++ ) {
                $fields_map['contact_email']['values'][$i]['value'] = trim( trim( trim( $str_arr[$i], '[' ), ']' ), '"' );
            }
        }

        if ( !empty( $contact_map['phone'] ) ) {
            //$fields_map['contact_phone'] = [ [ 'value' => $contact_map['phone'] ] ];
            $str_arr = preg_split( '/\,/', $contact_map['phone'] );
            $count = count( $str_arr );

            for ( $i = 0; $i < $count; $i++ ) {
                $fields_map['contact_phone']['values'][$i]['value'] = trim( trim( trim( $str_arr[$i], '[' ), ']' ), '"' );
            }
        }

        if ( !empty( $contact_map['facebook'] ) ) {
            $fields_map['contact_facebook'] = [ [ 'value' => $contact_map['facebook'] ] ];
        }

        if ( !empty( $contact_map['gender'] ) ) {
            $fields_map['gender'] = $contact_map['gender'];
        }

        // Age
        if ( !empty( $contact_map['age'] ) ) {
            $age = '';
            switch ( $contact_map['age'] ) {
                case '0-17':
                    $age = '<19';
                    $fields_map['age'] = [ '<19' => 'active' ];
                    break;
                case '18-24':
                    $age = '<26';
                    $fields_map['age'] = [ '<26' => 'active' ];
                    break;
                case '25-34':
                    $age = '<41';
                    $fields_map['age'] = [ '<41' => 'active' ];
                    break;
                case '35-44':
                    $age = '<41';
                    $fields_map['age'] = [ '<41' => 'active' ];
                    break;
                case '45+':
                    $age = '>41';
                    $fields_map['age'] = [ '>41' => 'active' ];
                    break;
            }

            if ( !empty( $age ) ) {
                $fields_map['age'] = $age;
            }
        }

        if ( !empty( $contact_map['notes'] ) ) {
            $fields_map['notes'][] = $contact_map['notes'];
        }

        if ( !empty( $contact_map['tags'] ) ) {
            foreach ( $contact_map['tags'] as $key => $value ) {
                $fields_map['tags']['values'][] = [ 'value' => $value['alias'] ];
            }
        }

        // Spiritual
        if ( $contact_map['spiritual'] === 'believer' ) {
            $fields_map['milestones'][] = 'milestone_belief';
        }

        if ( !empty( $contact_map['milestones'] ) ) {
            foreach ( $contact_map['milestones'] as $key => $value ) {
                if ( $value == 1 ) {
                    switch ( $key ) {
                        case 'has bible':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_has_bible' ];
                            break;
                        case 'studying':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_reading_bible' ];
                            break;
                        case 'profession':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_belief' ];
                            break;
                        case 'can share':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_can_share' ];
                            break;
                        case 'baptized':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_baptized' ];
                            break;
                        case 'has bible':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_has_bible' ];
                            break;
                        case 'studying':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_reading_bible' ];
                            break;
                        case 'profession':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_belief' ];
                            break;
                        case 'can share':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_can_share' ];
                            break;
                        case 'sharing':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_sharing' ];
                            break;
                        case 'baptizing':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_baptizing' ];
                            break;
                        case 'in group':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_in_group' ];
                            break;
                        case 'starting groups':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_planting' ];
                            break;
                        case 'baptized':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_baptized' ];
                            break;
                    }
                } else {
                    //Canceling milestones that are = false
                    switch ( $key ) {
                        case 'has bible':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_has_bible', 'delete' => true ];
                            break;
                        case 'studying':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_reading_bible', 'delete' => true ];
                            break;
                        case 'profession':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_belief', 'delete' => true ];
                            break;
                        case 'can share':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_can_share', 'delete' => true ];
                            break;
                        case 'baptized':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_baptized', 'delete' => true ];
                            break;
                        case 'has bible':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_has_bible', 'delete' => true ];
                            break;
                        case 'studying':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_reading_bible', 'delete' => true ];
                            break;
                        case 'profession':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_belief', 'delete' => true ];
                            break;
                        case 'can share':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_can_share', 'delete' => true ];
                            break;
                        case 'sharing':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_sharing', 'delete' => true ];
                            break;
                        case 'baptizing':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_baptizing', 'delete' => true ];
                            break;
                        case 'in group':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_in_group', 'delete' => true ];
                            break;
                        case 'starting groups':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_planting', 'delete' => true ];
                            break;
                        case 'baptized':
                            $fields_map['milestones']['values'][] = [ 'value' => 'milestone_baptized', 'delete' => true ];
                            break;
                    }
                }
            }
        } else {
            //Adding an empty milestone's structure to not get error msg
            $fields_map['milestones']['values'] = [];
        }

        // Location
        if ( !empty( $contact_map['street'] ) ) {
            if ( $this->geocoding_enabled ) {
            // if mapbox/google enabled, just let address geocoding happen automatically
                $fields_map['contact_address'] = [
                    [ 'value' => $contact_map['street'], 'geolocate' => true ],
                ];
            } else {
                $street_address = $contact_map['street'];
                $fields_map['contact_address'] = [ [ 'value' => $street_address ] ];

                // No geocoded location, so let's search for the location in the grid
                $locations = Disciple_Tools_Mapping_Queries::search_location_grid_by_name( [
                    'search_query' => $street_address,
                    'filter' => 'all'
                ] );

                if ( !empty( $locations ) && !empty( $locations['location_grid'] ) ) {
                    $location_match = $locations['location_grid'][0];
                    dt_write_log( 'Found location grid: ' . json_encode( $location_match ) );

                    // If we found a matching location grid, use the first match
                    $fields_map['location_grid'] = [
                        'values' => [
                            [ 'value' => $location_match['grid_id'] ]
                        ]
                    ];

                    if ( $location_match['alt_name'] === $street_address || $location_match['name'] === $street_address ) {
                        // If we found an exact match, don't add the plain text contact_address
                        unset( $fields_map['contact_address'] );
                    }
                } else {
                    // If there is no geolocation or grid, we need to set the contact address to the plain text address
                    $fields_map['contact_address'] = [ [ 'value' => $street_address ] ];
                }
            }
        }

        return $fields_map;
    }

    public static function prefix_validate_args_static( $value, $request, $param ) {
        return self::instance()->prefix_validate_args( $value, $request, $param );
    }

    public function get_version( WP_REST_Request $request )
    {
        // Get the plugin file path (parent directory of current file)
        $plugin_file = dirname( __DIR__ ) . '/disciple-tools-maarifa.php';

        // Extract version from plugin header
        $plugin_data = get_file_data( $plugin_file, [
            'Version' => 'Version',
        ], false );

        return [
            'version' => $plugin_data['Version'] ?? '1.0.0',
        ];
    }

    /**
     * API endpoint: /{post_type}
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function create_post( WP_REST_Request $request ) {

        $request_fields = $request->get_json_params() ?? $request->get_body_params();

        //Converts Maarifa field names to DT field names
        $post_fields = $this->map_fields_to_contact( $request_fields );

        $url_params = $request->get_url_params();
        $post_type = $url_params['post_type'];
        $get_params = $request->get_query_params();
        $silent     = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';
        $check_dups = true;

        if ( isset( $post_fields['maarifa_data'] ) )
        {
            $maarifa_contact_id = $post_fields['maarifa_data']['default']['id'];
            $post_id = null;

            //Verify if maarifa contact already exists in system
            if ( $maarifa_contact_id ) {
                $post_id = $this->get_post_id_by_maarifa_id( $maarifa_contact_id );
            }

            if ( isset( $post_id ) ) {
                // Contact already exists
                // Update the contact
                $post = $this->update_maarifa_contact( $post_type, $post_id, $post_fields, $request_fields );

                return $post;

            } else {
                // Contact is new
                $post = DT_Posts::create_post( $post_type, $post_fields, $silent, true, [
                    'check_for_duplicates' => $check_dups,
                ] );

                return $post;
            }
        }

        return null;
    }

    /**
     * API endpoint: /{post_type}/{post_id}
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function update_post( WP_REST_Request $request ){

        $url_params = $request->get_url_params();
        $post_id = $url_params['id'];
        $post_type = $url_params['post_type'];

        $request_fields = $request->get_json_params() ?? $request->get_body_params();

        //Converts Maarifa field names to DT field names
        $update_fields = $this->map_fields_to_contact( $request_fields );

        $post = $this->update_maarifa_contact( $post_type, $post_id, $update_fields, $request_fields );

        return $post;
    }

    /**
     * Update a post
     * @param string $post_type
     * @param int $post_id
     * @param array $update_fields
     * @param array $request_fields
     * @return array|WP_Error
     */
    public function update_maarifa_contact( string $post_type, int $post_id, $update_fields, $request_fields ){
        dt_write_log( 'Update_maarifa_contact' );

        // Remove notes from update_fields so it doesn't create a new comment everytime it's synced
        unset( $update_fields['notes'] );

        $post = DT_Posts::update_post( $post_type, $post_id, $update_fields, true );

        return $post;
    }

    /**
     * Get the post id by maarifa id
     * @param string $maarifa_id
     * @return int|null
     */
    public function get_post_id_by_maarifa_id( $maarifa_id ) {
        if ( isset( $maarifa_id ) ) {
            global $wpdb;
            // get_var will return the first column of first row
            $result = $wpdb->get_var( $wpdb->prepare(
                "SELECT `post_id`
                            FROM $wpdb->postmeta
                            WHERE meta_key LIKE `maarifa_data`
                            AND meta_value = %s;", $maarifa_id ) );
            return $result;
        }
        return null;
    }

    public function add_interactions( WP_REST_Request $request ) {

        dt_write_log( 'Add_interactions' );

        $url_params = $request->get_url_params();
        $get_params = $request->get_query_params();
        $body = $request->get_json_params() ?? $request->get_body_params();
        $silent = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';

        $type = 'maarifa';
        $post_id = (int) $url_params['id'];

        if ( empty( $body ) ) {
            return null;
        }

        $last_result = null;
        $errors = [];

        foreach ( $body as $value ) {
            $args = [];
            $comment = '';

            if ( isset( $value['date'] ) ) {
                $args['comment_date'] = dt_format_date( $value['date'], 'Y-m-d H:i:s' );
            }

            if ( isset( $value['responder_name'] ) ) {
                $args['user_id'] = $value['responder_name'];
            }

            if ( !isset( $value['comment'] ) || empty( $value['comment'] ) ) {
                continue;
            }
            $comment = (string) $value['comment'];
            $args['comment'] = $comment;

            if ( isset( $value['meta'] ) ) {
                $args['comment_meta'] = $value['meta'];
            }

            // Deduplication check
            $comment_date = $args['comment_date'] ?? '';

            global $wpdb;
            $query = "SELECT comment_ID FROM $wpdb->comments
                    WHERE comment_post_ID = %d
                    AND comment_content = %s
                    AND comment_type = %s";
            $query_params = [ $post_id, $comment, $type ];

            if ( !empty( $comment_date ) ) {
                $query .= ' AND ( comment_date_gmt = %s OR comment_date = %s )';
                $query_params[] = $comment_date;
                $query_params[] = $comment_date;
            }
            $query .= ' LIMIT 1';

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared 
            $existing_id = $wpdb->get_var( $wpdb->prepare( $query, $query_params ) );

            if ( $existing_id ) {
                dt_write_log( 'Add_interactions: duplicate SKIPPED. Post: ' . $post_id . ' CommentID: ' . $existing_id );
                $last_result = $existing_id; // Track as processed
                continue;
            }

            // Create comment
            $result = DT_Posts::add_post_comment( $url_params['post_type'], $post_id, $comment, $type, $args, true, $silent );

            // Record errors instead of stopping
            if ( is_wp_error( $result ) ) {
                dt_write_log( 'Add_interactions: error encountered for comment: '. $comment );
                $errors[] = $result;
                continue; // Move on to next interaction
            }

            $last_result = $result;
        }

        // Return error if any occurred, otherwise return last success
        if ( !empty( $errors ) ) {
            // Returns the first error encountered, but logs all of them
            dt_write_log( 'Add_interactions: finished with errors: ' . count( $errors ) );
            return $errors[0];
        }

        if ( $last_result ) {
            $ret = get_comment( $last_result )->to_array();
            unset( $ret['children'] );
            unset( $ret['populated_children'] );
            unset( $ret['post_fields'] );
            $ret['comment_meta'] = get_comment_meta( $ret['comment_ID'] );
            return $ret;
        }

        return null;
    }

    /**
     * Permission callback for the cleanup_duplicates endpoint.
     * Restricts access to users with 'manage_dt' capability.
     *
     * @return boolean|WP_Error
     */
    public function check_cleanup_permission() {
        // Check if the current user has the 'manage_dt' capability
        if ( current_user_can( 'manage_dt' ) ) {
            return true;
        }

        // Return a WP_Error for non-authorized users, which provides a 403 response
        return new \WP_Error(
            'rest_forbidden',
            __( 'You do not have permission to run this maintenance task.' ),
            [ 'status' => 403 ]
        );
    }

    public function cleanup_duplicates( WP_REST_Request $request ) {

        $params = $request->get_json_params() ?? [];
        $post_id    = isset( $params['post_id'] ) ? (int) $params['post_id'] : null;
        $start_date = isset( $params['start_date'] ) ? sanitize_text_field( $params['start_date'] ) : null;
        $end_date   = isset( $params['end_date'] ) ? sanitize_text_field( $params['end_date'] ) : null;

        //Ensures that the end period includes the entire day.
        if ( $end_date && strpos( $end_date, ':' ) === false ) {
            $end_date .= ' 23:59:59';
        }
        $all        = isset( $params['all'] ) ? filter_var( $params['all'], FILTER_VALIDATE_BOOLEAN ) : false;
        $dry_run    = isset( $params['dry_run'] ) ? filter_var( $params['dry_run'], FILTER_VALIDATE_BOOLEAN ) : true;

        $has_id    = !empty( $post_id );
        $has_dates = !empty( $start_date ) && !empty( $end_date );

        // 1. Validation: Only one mode allowed
        $active_modes = 0;
        if ( $has_id ) { $active_modes++;
        }
        if ( $has_dates ) { $active_modes++;
        }
        if ( $all ) { $active_modes++;
        }

        if ( $active_modes > 1 ) {
            return [
                'status'  => 'Error',
                'message' => 'Use post_id OR start_date/end_date OR all, not multiple options simultaneously.',
            ];
        }

        // 2. Validation: Date range completeness
        if ( ( !empty( $start_date ) && empty( $end_date ) ) || ( empty( $start_date ) && !empty( $end_date ) ) ) {
            return [
                'status'  => 'Error',
                'message' => 'start_date and end_date must be provided together.',
            ];
        }

        // 3. Validation: Mandatory filter or explicit 'all'
        if ( !$has_id && !$has_dates && !$all ) {
            return [
                'status'  => 'Error',
                'message' => 'You must provide a post_id, a date range, or set "all" to true to run on the entire site.',
            ];
        }

        global $wpdb;

        $inner_conditions = [ "comment_type = 'maarifa'" ];
        $outer_conditions = [ "c.comment_type = 'maarifa'" ];
        $query_params = [];

        if ( $post_id ) {
            $inner_conditions[] = 'comment_post_ID = %d';
            $outer_conditions[] = 'c.comment_post_ID = %d';
            $query_params[] = $post_id;
        }
        if ( $start_date ) {
            $inner_conditions[] = 'comment_date >= %s';
            $outer_conditions[] = 'c.comment_date >= %s';
            $query_params[] = $start_date;
        }
        if ( $end_date ) {
            $inner_conditions[] = 'comment_date <= %s';
            $outer_conditions[] = 'c.comment_date <= %s';
            $query_params[] = $end_date;
        }

        $where_inner = ' WHERE ' . implode( ' AND ', $inner_conditions );
        $where_outer = ' WHERE ' . implode( ' AND ', $outer_conditions );

        $query = "SELECT c.comment_ID, c.comment_post_ID, c.comment_content, c.comment_date
                FROM $wpdb->comments c
                INNER JOIN (
                    SELECT comment_post_ID, comment_content, comment_date
                    FROM $wpdb->comments
                    $where_inner
                    GROUP BY comment_post_ID, comment_content, comment_date
                    HAVING COUNT(*) > 1
                ) dupes ON (
                    c.comment_post_ID = dupes.comment_post_ID
                    AND c.comment_content = dupes.comment_content
                    AND c.comment_date = dupes.comment_date
                )
                $where_outer
                ORDER BY c.comment_post_ID, c.comment_date, c.comment_ID ASC";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared 
        $all_params = array_merge( $query_params, $query_params );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared 
        $query = !empty( $all_params ) ? $wpdb->prepare( $query, $all_params ) : $query;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared 
        $duplicates = $wpdb->get_results( $query );

        if ( empty( $duplicates ) ) {
            dt_write_log( 'Cleanup_duplicates: ' . json_encode( 'No duplicates found.' ) );

            return [
                'status'  => 'Ok',
                'message' => 'No duplicates found.',
                'dry_run' => $dry_run,
            ];
        }

        $groups = [];
        foreach ( $duplicates as $row ) {
            $key = $row->comment_post_ID . '|||' . $row->comment_content . '|||' . $row->comment_date;
            $groups[ $key ][] = $row;
        }

        $total_kept     = 0;
        $total_deleted  = 0;
        $deleted_ids    = [];
        $errors         = [];
        $affected_posts = [];

        foreach ( $groups as $items ) {
            if ( count( $items ) <= 1 ) {
                continue;
            }

            usort( $items, fn( $a, $b ) => (int) $a->comment_ID - (int) $b->comment_ID );

            $keep      = $items[0];
            $to_delete = array_slice( $items, 1 );

            $pid = (int) $keep->comment_post_ID;
            $affected_posts[ $pid ] = ( $affected_posts[ $pid ] ?? 0 ) + 1;

            if ( !$dry_run ) {
                foreach ( $to_delete as $del ) {
                    $del_id = (int) $del->comment_ID;

                    // Use wp_delete_comment which handles commentmeta, caches, and counts automatically
                    // The second parameter 'true' forces permanent deletion (skipping trash)
                    $result = wp_delete_comment( $del_id, true );

                    if ( !$result ) {
                        $errors[] = $del_id;
                        dt_write_log( 'Cleanup_duplicates: failed to delete comment ' . $del_id );
                    } else {
                        $deleted_ids[] = $del_id;
                        $total_deleted++;
                        dt_write_log( sprintf(
                            'Cleanup_duplicates: Post ID %d (Content snippet: %s) - DELETED comment ID %d ',
                            (int) $del->comment_post_ID,
                            $del_id,
                        substr( $del->comment_content, 0, 30 )));
                    }
                }
            } else {
                foreach ( $to_delete as $del ) {
                    $deleted_ids[] = (int) $del->comment_ID;
                    $total_deleted++;
                }
            }

            $total_kept++;
        }

        $summary = [
            'status'         => 'ok',
            'dry_run'        => $dry_run,
            'comment_groups_with_duplicates' => $total_kept,
            'total_comments_to_delete'  => $total_deleted,
            'total_contacts_affected'     => count( $affected_posts ),
            'posts_affected'           => $affected_posts,
            'comment_ids_to_delete'     => $deleted_ids,
            'errors'                       => $errors,
            'filters_applied'           => [
                'post_id'    => $post_id,
                'start_date' => $start_date,
                'end_date'   => $end_date,
            ],
        ];

        if ( $dry_run ) {
            $summary['message'] = 'MODE DRY-RUN — Nothing was deleted.';
        } else {
            $summary['message'] = "Total of {$total_deleted} removed. Total number of comment groups with duplicates: {$total_kept} in " . count( $affected_posts ) . ' contacts.';
            dt_write_log( 'Cleanup_duplicates: ' . json_encode( $summary ) );
        }

        return $summary;
    }
}

Dt_Maarifa_Endpoints::instance();
