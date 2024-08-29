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

            dt_write_log("Prefix_validate_args");

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

        public function mapFieldsToContact ( $fields )
        {
            dt_write_log("MapFieldsToContact");

            dt_write_log($fields);

            $contact_map = $fields;
            $fields_map = null;
            
            dt_write_log($contact_map);
            //$fields_map;


            if (!empty($contact_map['id'])) { 
                $fields_map['maarifa_data'] = $contact_map['id'];
            }
            if (!empty($contact_map['name'])) {
                $fields_map['name'] = $contact_map['name'];
            }
            if (!empty($contact_map['email'])) { //VER DETALHES
                $fields_map['contact_email'] = $contact_map['email'] ;
            }
            if (!empty($contact_map['phone'])) { 
//                $fields_map['contact_phone'] = $contact_map['phonecode'] . $contact_map['phone'];
                $fields_map['contact_phone'] = $contact_map['phone'];
            }
            if (!empty($contact_map['facebook'])) {
                $fields_map['contact_facebook'] = $contact_map['facebook'];
            }            
        


            //Location
            if (!empty($contact_map['street'])) { //VER DETALHES
                $fields_map['contact_address'] = $contact_map['street'];
            }                        
            if (!empty($contact_map['country'])) { //VER DETALHES
                //$fields_map['country'] = $this->getLocations();
                //$fields_map['location_grid_meta'] = $contact_map['country'];
            }            
            

            // Age
            if (!empty($contact_map['age'])) { 
                $age = '';
                switch ($contact_map['age']) {
                    case '<19':
                        $age = '0-17';
                        break;
                    case '<26':
                        $age = '18-24';
                        break;
                    case '<41':
                        $age = '25-34';
                        break;
                    case '<41':
                        $age = '35-44';
                        break;
                    case '>41':
                        $age = '45+';
                        break;
                }

                if (!empty($age)) {
                    $fields_map['age'] = $age;
                }
            }


            if (!empty($contact_map['notes'])) {
                //$fields_map['notes'] = $contact_map['notes'];

                $note = null;

                foreach ($contact_map['notes'] as $note) {     
                    $contact_map['notes'][] = $note;
                }
            }

            //if (!empty($contact_map['tags'])) { //VER DETALHES
            //    $fields_map['tags'] = $contact_map['tags'];
            //}
            //if (!empty($contact_map['notes'])) { //VER DETALHES
            //    $fields_map['notes'] = $contact_map['notes'];
            //}            

            //////////////////////
       
            if (!empty($contact_map['background'])) { 
                $fields_map['background'] = $contact_map['background'];
            }         
            if (!empty($contact_map['spiritual'])) { 
                $fields_map['spiritual'] = $contact_map['spiritual'];
            } 

            //Ignoring those fields
            /*        
            if (!empty($contact_map['created'])) { //VER DETALHES
                $fields_map['created'] = $contact_map['created'];
            }         
            if (!empty($contact_map['last_updated'])) { //VER DETALHES
                $fields_map['last_updated'] = $contact_map['last_updated'];
            }         
            if (!empty($contact_map['last_seen'])) { //VER DETALHES
                $fields_map['last_seen'] = $contact_map['last_seen'];
            }         
            if (!empty($contact_map['first_contact'])) { //VER DETALHES
                $fields_map['first_contact'] = $contact_map['first_contact'];
            }                                                                                 
            if (!empty($contact_map['first_contact_details'])) { //VER DETALHES
                $fields_map['first_contact_details'] = $contact_map['first_contact_details'];
            }                              
            if (!empty($contact_map['first_source'])) { //VER DETALHES
                $fields_map['first_source'] = $contact_map['first_contact'];
            }                              
            if (!empty($contact_map['ds_user_id'])) { //VER DETALHES
                $fields_map['ds_user_id'] = $contact_map['ds_user_id'];
            }                 
            if (!empty($contact_map['external_id'])) { //VER DETALHES
                $fields_map['external_id'] = $contact_map['external_id'];
            }                 
            if (!empty($contact_map['external_url'])) { //VER DETALHES
                $fields_map['external_url'] = $contact_map['external_url'];
            }                            
            if (!empty($contact_map['location_details'])) { //VER DETALHES
                $fields_map['location_details'] = $contact_map['location_details'];
            }              
*/

            if (!empty($contact_map['milestone'])) { 
                $milestone = '';           

                foreach ($contact_map['milestone'] as $milestone) {                          
                
                    switch ($milestone) {

                        case 'believer':
                        case 'profession':
                            $fields_map['milestones'][] = 'milestone_belief';
                            break;
                        case 'began study':
                            $fields_map['milestones'][] = 'milestone_reading_bible';
                            break;
                        case 'joined group':
                            $fields_map['milestones'][] = 'milestone_in_group';
                            break;
                        case 'left group':
                            // if milestone_in_group is already set, that means there was
                            // a more recent interaction for them joining a group, so this
                            // was from leaving a previous group but later joining another (or the same one again)
                            $key = array_search('milestone_in_group', $fields_map['milestones']);
                            if ($key !== false) {
                                array_splice($fields_map['milestones'], $key, 1);
                            }
                            break;
                        case 'started group':
                            $fields_map['milestones'][] = 'milestone_planting';
                            break;
                        case 'baptized':
                            $fields_map['milestones'][] = 'milestone_baptized';
                            $fields_map['baptism_date'] = date("Y-m-d", $interaction->when_made);
                            break;

                        /*
                        case 'milestone_has_bible':
                            // has bible...
                            break;
                        case 'milestone_reading_bible':
                            // studying...
                            break;   
                        case 'milestone_belief':
                            // profession...
                            break;   
                        case 'milestone_can_share':
                            // can share...
                            break;   
                        case 'milestone_sharing':
                            // sharing...
                            break;   
                        case 'milestone_baptized':
                            // baptized...
                            break;   
                        case 'milestone_baptizing ':
                            // baptizing...
                            break;
                        case 'milestone_in_group ':
                            // in group...
                                break;                                                                                                               
                        case 'milestone_planting ':
                            // starting groups...
                            break;     
                        default:
                            // code...
                            break;
                            */
                    }
                }
            }

            dt_write_log('Fields_map');
            dt_write_log($fields_map);

            return $fields_map;
        }


        public static function prefix_validate_args_static( $value, $request, $param ) {
            return self::instance()->prefix_validate_args( $value, $request, $param );
        }

        public function contacts( WP_REST_Request $request ) {
                    
                    dt_write_log("Contacts");
                                     
                    $fields     = $request->get_json_params() ?? $request->get_body_params();

                    dt_write_log("fields antes");

                    dt_write_log($fields);

                    //Converts Maarifa field names to DT field names
                    $fields2 = $this->mapFieldsToContact($fields);

                    $fields = $fields2;

                    dt_write_log("fields depois");
                    dt_write_log($this->mapFieldsToContact($fields));


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
        
        

