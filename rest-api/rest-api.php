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


/* Verify if I need that code
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
*/                     


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
                                'args' => [
                                   /* 'notes' => [
                                        'description' => 'The comment text',
                                        'type' => 'string',
                                        'required' => true,
                                        'validate_callback' => [ $this, 'prefix_validate_args' ]
                                    ],*/
                                    'post_type' => $arg_schemas['post_type'],
                                    'id' => $arg_schemas['id'],
                                    'date' => $arg_schemas['date'],
                                    'comment_type' => $arg_schemas['comment_type'],
                                    //'notes' => $arg_schemas['comment']
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

        public function mapFieldsToContact ( $contact_map )
        {

            $fields_map = null;

            $fields_map = array(
                'title' => $contact_map['name'],
                'type' => 'access',
                'milestones' => [ ],
                'maarifa_data' => $contact_map['id'],
            );

 
            if (!empty($contact_map['email'])) { //VER DETALHES
          

                $emails = $contact_map['email'];

                $fields_map['contact_email'] = [ [ 'value' => $contact_map['email'] ] ];

            }
            if (!empty($contact_map['phone'])) { 

                $fields_map['contact_phone'] = [ [ 'value' => $contact_map['phone'] ] ];
            }
            if (!empty($contact_map['facebook'])) {
                
                $fields_map['contact_facebook'] = [ [ 'value' => $contact_map['facebook'] ] ];                
            }            
            if (!empty($contact_map['gender'])) {
                $fields_map['gender'] = $contact_map['gender'];
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
                    case '<45':
                        $age = '35-44';
                        break;
                    case '>45':
                        $age = '45+';
                        break;
                }

                if (!empty($age)) {
                    $fields_map['age'] = $age;
                }
            }


            if (!empty($contact_map['notes'])) {
                $fields_map['notes'] = $contact_map['notes'];
                
            }

            if (!empty($contact_map['tags'])) 
            { //VER DETALHES

                $fields_map['tags'] = $contact_map['tags'];

            }

            // Spiritual
            if ($contact_map['spiritual'] === 'believer') {
                $fields_map['milestones'][] = 'milestone_belief';
            }

            if (!empty($contact_map['milestones'])) { 
                $milestone = '';           

                $milestones = array();


                foreach ($contact_map['milestones'] as $key => $value) {            

                    //$milestone = $value;
                
                    switch ($key) {

                        case 'has bible':
                            //$fields_map['milestones']['milestone_has_bible'] = $milestone;
                            //$fields_map['milestones']['milestone_has_bible'] = $milestone;
                            //$fields_map['milestones']['milestone_has_bible'] =  $milestone ;
                            //$milestones['milestone_has_bible'] =  $milestone ;
                            $milestones["value"][] = "milestone_has_bible"; 
                            
                            break;    
                        case 'studying':
                            //$fields_map['milestones']['milestone_reading_bible'] = $milestone;
                            
                            //$milestones['milestone_reading_bible'] =  $milestone ;
                            $milestones["value"][] = "milestone_reading_bible";
                            break;      
          /*
                        case 'profession':
                            $fields_map['milestones']['milestone_belief'] =  $milestone;
                            break;                                                   
                        case 'can share':
                            $fields_map['milestones']['milestone_can_share'] =  $milestone;
                            break;   
                        case 'sharing':
                            $fields_map['milestones']['milestone_sharing'] =  $milestone;
                            break;                                                                                                           
                        case 'baptizing':
                            $fields_map['milestones']['milestone_baptizing'] =  $milestone;
                            break;                                                           
                        case 'in group':
                            $fields_map['milestones']['milestone_in_group'] = $milestone;
                            break; 
                        case 'starting groups':
                            $fields_map['milestones']['milestone_planting'] = $milestone;
                            break;                                            
                        case 'baptized':
                            $fields_map['milestones']['milestone_baptized'] =  $milestone;
                           // $fields_map['baptism_date'] = date("Y-m-d", $interaction->when_made);
                            break;    
                            */
                    }
                    
                }

                dt_write_log('$milestones');
                dt_write_log($milestones);


                $fields_map['milestones'] = [
                  //"milestones" => [
                    "values" => [
                      [ "value" => "milestone_has_bible" ],  
                      [ "value" => "milestone_planting"] 
                    ]                    
                  ];
               // ];

            }            
    

            dt_write_log('Fields_map VER MILESTONES');
            dt_write_log($fields_map);

            return $fields_map;
        }


        public static function prefix_validate_args_static( $value, $request, $param ) {
            return self::instance()->prefix_validate_args( $value, $request, $param );
        }

        public function contacts( WP_REST_Request $request ) {
                    
            dt_write_log("Contacts");
                                 
            $fields     = $request->get_json_params() ?? $request->get_body_params();

            dt_write_log("Fields before");

            dt_write_log($fields);

            //Converts Maarifa field names to DT field names            
            $fields2 = $this->mapFieldsToContact($fields);

            //dt_write_log("Fields2 after");
            //dt_write_log( $fields2);


            $url_params = $request->get_url_params();
            $get_params = $request->get_query_params();
            $silent     = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';

//                    $check_dups = ! empty( $get_params['check_for_duplicates'] ) ? explode( ',', $get_params['check_for_duplicates'] ) : 
            $check_dups = true;


            $maarifa_contact_id = null;

            //dt_write_log("fields2[maarifa_data]");
            //dt_write_log($fields2['maarifa_data']);

            //if ( $fields2['maarifa_data']  && $fields2['maarifa_data'] != null)

            if( isset( $fields2['maarifa_data'] ))
            {

                dt_write_log("ENTROU NO IF");

                $maarifa_contact_id = $fields2['maarifa_data'];

                dt_write_log("maarifa_contact_id");
                dt_write_log($maarifa_contact_id);

                if ( $maarifa_contact_id ) {
                    //Verify if maarifa_data already exists
                    $return_maarifa = $this->check_field_value_exists( $request , $maarifa_contact_id );

                    dt_write_log("return_maarifa");
                    dt_write_log($return_maarifa);
                }


                    
                //$obj_return = $return_maarifa().get_class(object:object);


                if ( isset( $return_maarifa ) && ! $return_maarifa ==  0 )
                {//Contact already exits in Maarifa


                    //Update the contact
                    $post_id_return = $return_maarifa[0]->post_id;

                    $this->update_maarifa_contact( $request , $post_id_return, $fields2);

                }
                else //Contact is new
                {
                    dt_write_log("Contact is new");
                    //dt_write_log($fields2);
                        
                    $post       = DT_Posts::create_post( 'contacts', $fields2, $silent, true, [
                        'check_for_duplicates' => $check_dups,
                      
                    ] );                             
                
                    return $post;
                }                         
            }

            return NULL;
        }


        public function update_post( WP_REST_Request $request ){

            $url_params = $request->get_url_params();
            $id_upd = $url_params['id'];

            $fields_orig     = $request->get_json_params() ?? $request->get_body_params();
            dt_write_log("Fields before mapping");
            dt_write_log($fields_orig);

            //Converts Maarifa field names to DT field names            
            $fields3 = $this->mapFieldsToContact($fields_orig);
            

            $this->update_maarifa_contact( $request , $id_upd, $fields3);
        }


        public function update_maarifa_contact( WP_REST_Request $request, int $p_post_id, $fields2 ){
            

                dt_write_log("Update_maarifa_contact");

                //$fields = $request->get_json_params() ?? $request->get_body_params();

                //dt_write_log("fields");
                //dt_write_log($fields);

                //dt_write_log("fields2");
                //dt_write_log($fields2);

                dt_write_log("p_post_id");
                dt_write_log("$p_post_id");

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
                dt_write_log("Before update_post");
                
                return DT_Posts::update_post( $url_params['post_type'], $post_id, $fields2, $silent );
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
               // if ( isset( $params['maarifa_data'] ) ) {
                if ( $maarifa_contact_id != null ) {
                    
                    global $wpdb;
                    $result = $wpdb->get_results( $wpdb->prepare(
                        "SELECT `post_id`
                            FROM $wpdb->postmeta
                            WHERE meta_key LIKE 'maarifa_data'
                            AND meta_value = %s;", $maarifa_contact_id ) );

                    //AND meta_value = %s;", $params['maarifa_data'] ) );

                    
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

                $type = 'maarifa';
                
                if (!empty($body)) { 
                    //$milestone = '';           

                    //$milestones = array();


                    dt_write_log("body[]");
                    dt_write_log($body);
                    $value = null;
                    $result = null;
                    $ret = null;

                    foreach ($body as $key => $value) {            

                        dt_write_log("value['notes']");
                        dt_write_log($value);

                        if ( isset( $value['when_made'] ) ){
                            $args['comment_date'] = $value['when_made'];
                        }
                        
                        if ( isset( $value['responder_name'] ) ){
                            $args['user_id'] = $value['responder_name'];
                        }                

                        if ( isset( $value['notes'] ) ){

                            $comment = "Type: ". $type ."\n". "Responder name: ". $value['responder_name']. "\n" .$value['notes'];

                            dt_write_log("value[notes]");
                            dt_write_log($value['notes']);
                        }               

                        if ( isset( $value['meta'] ) ) {
                            $args['comment_meta'] = $value['meta'];
                        }

                        if (isset($value['id']))
                        {//If comment_id exists, update the comment

                            $id = $value['id'];

                            dt_write_log("Add_interactions UPDATE");
                            dt_write_log("type");
                            dt_write_log($type);

                            $result = DT_Posts::update_post_comment( $id, $comment, true, $type, $args );
                        } 
                        else
                        {//If doesn't, create a new comment
                            dt_write_log("Add_interactions CREATE");

                            $result = DT_Posts::add_post_comment( $url_params['post_type'], $url_params['id'], $comment, $type, $args, true, $silent );
                        }                       
                                            
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

        }

        Dt_Maarifa_Endpoints::instance();
        
        

