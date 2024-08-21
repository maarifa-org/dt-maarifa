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
        

                public function __construct() {


                    $this->namespace = $this->context . '/v' . intval( $this->version );
                    add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

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
                        ]
                        
                    ];
        

/*                    //create_post - Create or update contact
                    register_rest_route(

                        $this->namespace, '/(?P<post_type>\w+)/',  [
                            
                            [
                            'methods'  => 'POST',
                            'callback' => [ $this, 'contacts' ],
                            'args' => [
                                'post_type' => $arg_schemas['post_type'],
                                ],
                            'permission_callback' => '__return_true',
                            ]
                        ]
                     );*/

                    //create_post - Create or update contact
                    register_rest_route(

                        $this->namespace, '/(?P<post_type>\w+)/',  [
                            
                            [
                            'methods'  => 'POST',
                            'callback' => [ $this, 'contacts' ],
                            'args' => [
                                'post_type' => $arg_schemas['post_type'],
                                'maarifa_data' => $arg_schemas['maarifa_data'],
                                ],
                            'permission_callback' => '__return_true',
                            ]
                        ]
                     );                     


                    //create_post - Update contact with Maarifa id
                    register_rest_route(

                        $this->namespace, '/(?P<post_type>\w+)/(?P<maarifa_data>\d+/maarifa)',  [
                            
                            [
                            'methods'  => 'POST',
                            'callback' => [ $this, 'contacts' ],
                            'args' => [
                                'post_type' => $arg_schemas['post_type'],
                                'maarifa_data' => $arg_schemas['maarifa_data'],
                                'check_for_duplicates' => true
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
                                    'id' => $arg_schemas['id'],
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
                                'args' => [
                                    'comment' => [
                                        'description' => 'The comment text',
                                        'type' => 'string',
                                        'required' => true,
                                        'validate_callback' => [ $this, 'prefix_validate_args' ]
                                    ],
                                    'post_type' => $arg_schemas['post_type'],
                                    'id' => $arg_schemas['id'],
                                    'date' => $arg_schemas['date'],
                                    'comment_type' => $arg_schemas['comment_type']
                                ],
                                'permission_callback' => '__return_true',
                            ]
                        ]
                    );                    
                }

        public function prefix_validate_args( $value, $request, $param ){

            dt_write_log("ENTROU NO prefix_validate_args");

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


        public static function prefix_validate_args_static( $value, $request, $param ) {
            return self::instance()->prefix_validate_args( $value, $request, $param );
        }

        public function contacts( WP_REST_Request $request ) {
                    
                    dt_write_log("ENTROU NO contacts");
                 
                    $fields     = $request->get_json_params() ?? $request->get_body_params();
                    $url_params = $request->get_url_params();
                    $get_params = $request->get_query_params();
                    $silent     = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';

//                    $check_dups = ! empty( $get_params['check_for_duplicates'] ) ? explode( ',', $get_params['check_for_duplicates'] ) : [];
                    $check_dups = true;


                $maarifa_contact_id = null;

                if ( isset( $fields['maarifa_data'] ) ){


                    $maarifa_contact_id = $fields['maarifa_data'];

                    //Verify if maarifa_data already exists
                    $return_maarifa = $this->check_field_value_exists( $request , $maarifa_contact_id );

                    
                    //$obj_return = $return_maarifa().get_class(object:object);


                    if ( isset( $return_maarifa ) && ! $return_maarifa ==  0 )
                    {//Contact already exits in Maarifa


                        //Update the contact
                        $post_id_return = $return_maarifa[0]->post_id;

                        $this->update_post( $request , $post_id_return);

                    }
                    else //Contact is new
                    {
                        dt_write_log("Contact is new");
                        
                        $post       = DT_Posts::create_post( 'contacts', $fields, $silent, true, [
                            'check_for_duplicates' => $check_dups,
                      
                    ] );                             
                
                    return $post;
                    };                         

                }

                    //return null;
            }    

            public function update_post( WP_REST_Request $request, int $p_post_id ){

                dt_write_log("Update_post");

                $fields = $request->get_json_params() ?? $request->get_body_params();

                if( isset( $p_post_id))
                {
                    $post_id = $p_post_id;
                }
                else
                {
                    $post_id = $url_params['id'];
                }
                $url_params = $request->get_url_params();
                $get_params = $request->get_query_params();
                $silent = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';
                
                return DT_Posts::update_post( $url_params['post_type'], $post_id, $fields, $silent );
            }            


            public function check_field_value_exists( WP_REST_Request $request, string $maarifa_contact_id) {
            //Verify if maarifa_data already exists
                

                $params = $request->get_params();

                dt_write_log("Check_field_value_exists");

                /*
                $communication_channels = DT_Posts::get_field_settings_by_type( $params['post_type'], 'communication_channel' );
                if ( in_array( $params['post_type'], $communication_channels ) ) {
                    return new WP_Error( __METHOD__, 'Invalid communication_channel' );
                }
                */
//                if ( isset( $params['post_type'] ) && isset( $params['communication_channel'] ) && isset( $params['maarifa_data'] ) ) {
                if ( isset( $params['maarifa_data'] ) ) {

                    global $wpdb;
                    $result = $wpdb->get_results( $wpdb->prepare(
                        "SELECT `post_id`
                            FROM $wpdb->postmeta
                            WHERE meta_key LIKE 'maarifa_data'
                            AND meta_value = %s;", $params['maarifa_data'] ) );

                    
                    return $result;

                }
                return [];
            }      

            
            public function add_interactions( WP_REST_Request $request ){

                dt_write_log("Add_interactions");

                $url_params = $request->get_url_params();
                $get_params = $request->get_query_params();
                $body = $request->get_json_params() ?? $request->get_body_params();
                $silent = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';
                $args = [];
                if ( isset( $body['date'] ) ){
                    $args['comment_date'] = $body['date'];
                }
                if ( isset( $body['meta'] ) ) {
                    $args['comment_meta'] = $body['meta'];
                }
                $type = 'comment';
                if ( isset( $body['comment_type'] ) ){
                    $type = $body['comment_type'];
                }

                if (isset($body['comment_ID']))
                {//If comment_id exists, update the comment
                    dt_write_log("Add_interactions UPDATE");

                    $result = DT_Posts::update_post_comment( $body['comment_ID'], $body['comment'], true, $type, $args );
                } 
                else
                {//If doesn't, create a new comment
                    dt_write_log("Add_interactions CREATE");

                    $result = DT_Posts::add_post_comment( $url_params['post_type'], $url_params['id'], $body['comment'], $type, $args, true, $silent );
                }

                
                if ( is_wp_error( $result ) ) {
                    return $result;
                } else {
                    $ret = get_comment( $result )->to_array();
                    unset( $ret['children'] );
                    unset( $ret['populated_children'] );
                    unset( $ret['post_fields'] );
                    $ret['comment_meta'] = get_comment_meta( $ret['comment_ID'] );
                    return $ret;
                }
            }

        }

        Dt_Maarifa_Endpoints::instance();
        
        

