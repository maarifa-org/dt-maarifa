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
            $this->namespace, '/(?P<post_type>\w+)/', [

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
                        'post_type' => $arg_schemas['post_type'],
                        'id' => $arg_schemas['id'],
                        'date' => $arg_schemas['date'],
                        'comment_type' => $arg_schemas['comment_type'],
                    ],
                    'permission_callback' => '__return_true',
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

    public function add_location( $request ) {

        $url_params = $request->get_url_params();
        $body = $request->get_json_params() ?? $request->get_body_params();

        dt_write_log( ' request ' );
        dt_write_log( $request );
       // $result = DT_Posts::geolocate_addresses( $body['id'], $body['post_type'], 'country', $body['country'] );

        return $result;
    }

    public function map_fields_to_contact( $contact_map ) {

        $fields_map = null;

        $fields_map = array(
        'title' => $contact_map['name'],
        'type' => 'access',
        'milestones' => [],
        'maarifa_data' => $contact_map['id'],

        );

        $fields_map['sources']['values'][0]['value'] = 'Maarifa';

        $fields_map['maarifa_data'] = $contact_map;


        if ( !empty( $contact_map['email'] ) ) {

            $str_arr = preg_split( '/\,/', $contact_map['email'] );

            $count = count( $str_arr );
            for ( $i = 0; $i < $count; $i++ )
            {
                        $fields_map['contact_email']['values'][$i]['value'] = trim( trim( trim( $str_arr[$i], '[' ), ']' ), '"' );

            }

        }

        if ( !empty( $contact_map['phone'] ) ) {

            //$fields_map['contact_phone'] = [ [ 'value' => $contact_map['phone'] ] ];

            $str_arr = preg_split( '/\,/', $contact_map['phone'] );

            $count = count( $str_arr );

            for ( $i = 0; $i < $count; $i++ )
            {
                $fields_map['contact_phone']['values'][$i]['value'] = trim( trim( trim( $str_arr[$i], '[' ), ']' ), '"' );
            }
        }

        if ( !empty( $contact_map['facebook'] ) ) {

            $fields_map['contact_facebook'] = [ [ 'value' => $contact_map['facebook'] ] ];
        }

        if ( !empty( $contact_map['gender'] ) ) {

            $fields_map['gender'] = $contact_map['gender'];
        }

        if ( !empty( $contact_map['street'] ) ) {

            dt_write_log( 'Valor street' );
            dt_write_log( $contact_map['street'] );

            $fields_map['contact_address'] = [ [ 'value' => $contact_map['country'] ] ];
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

        if ( !empty( $contact_map['tags'] ) )
            {

            $int_count0 = 0;

            foreach ( $contact_map['tags'] as $key => $value ) {

                $fields_map['tags']['values'][$int_count0]['value'] = $contact_map['tags'][$key]['alias'];

                $int_count0++;

            }
        }

            // Spiritual
        if ( $contact_map['spiritual'] === 'believer' ) {

            $fields_map['milestones'][] = 'milestone_belief';
        }

        if ( !empty( $contact_map['milestones'] ) ) {

            $int_count = 0;

            foreach ( $contact_map['milestones'] as $key => $value ) {

                if ( $value == 1 )
                {
                    switch ( $key ) {

                        case 'has bible':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_has_bible';
                            $int_count++;

                            break;

                        case 'studying':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_reading_bible';
                            $int_count++;

                            break;

                        case 'profession':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_belief';
                            $int_count++;

                            break;

                        case 'can share':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_can_share';
                            $int_count++;

                            break;

                        case 'baptized':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_baptized';
                            $int_count++;

                            break;

                        case 'has bible':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_has_bible';
                            $int_count++;

                            break;

                        case 'studying':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_reading_bible';
                            $int_count++;

                            break;

                        case 'profession':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_belief';
                            $int_count++;

                            break;

                        case 'can share':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_can_share';
                            $int_count++;

                            break;

                        case 'sharing':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_sharing';
                            $int_count++;

                            break;

                        case 'baptizing':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_baptizing';
                            $int_count++;

                            break;

                        case 'in group':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_in_group';
                            $int_count++;

                            break;

                        case 'starting groups':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_planting';
                            $int_count++;

                            break;

                        case 'baptized':

                            $fields_map['milestones']['values'][$int_count]['value'] = 'milestone_baptized';
                            $int_count++;

                            break;


                    }
                }

            }
        }
        else //No milestones
            {
            $fields_map['milestones']['values'][1]['value'] = 0;
        }


            dt_write_log( 'Fields_map MILESTONES' );
            dt_write_log( $fields_map );

            return $fields_map;
    }


    public static function prefix_validate_args_static( $value, $request, $param ) {
        return self::instance()->prefix_validate_args( $value, $request, $param );
    }

    public function contacts( WP_REST_Request $request ) {

        dt_write_log( 'Contacts' );

        $fields     = $request->get_json_params() ?? $request->get_body_params();

        //Converts Maarifa field names to DT field names
        $fields2 = $this->map_fields_to_contact( $fields );

        dt_write_log( $fields2 );

        $url_params = $request->get_url_params();
        $get_params = $request->get_query_params();
        $silent     = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';

    //                    $check_dups = ! empty( $get_params['check_for_duplicates'] ) ? explode( ',', $get_params['check_for_duplicates'] ) :
        $check_dups = true; //TO DO


        $maarifa_contact_id = null;

        if ( isset( $fields2['maarifa_data'] ) )
        {

            $maarifa_contact_id = $fields2['maarifa_data']['default']['id'];


            if ( $maarifa_contact_id ) {

                //Verify if maarifa_data already exists
                $return_maarifa = $this->check_field_value_exists( $request, $maarifa_contact_id );

            }


            if ( isset( $return_maarifa ) && ! $return_maarifa == 0 )
            {//Contact already exits in Maarifa


                //Update the contact
                $post_id_return = $return_maarifa[0]->post_id;

                $this->update_maarifa_contact( $request, $post_id_return, $fields2 );

            }
            else //Contact is new
            {

                $post       = DT_Posts::create_post( 'contacts', $fields2, $silent, true, [
                    'check_for_duplicates' => $check_dups,

                ] );

                dt_write_log( '1 post' );
                dt_write_log( $fields );

                // Country --> Locations
                if ( !empty( $fields['street'] ) && !empty( $post['ID'] ) ) {

                    dt_write_log( '$request' );
                    dt_write_log( $request );

                    $geoloc = $this->add_user_location( $request, $post['ID'] );
                    $post['maarifa_data']['location_details'] = $geoloc;


                    dt_write_log( 'geoloc' );
                    dt_write_log( $geoloc );

                    dt_write_log( 'post' );
                    dt_write_log( $post );

                }

                return $post;
            }
        }

        return null;
    }


    public function update_post( WP_REST_Request $request ){

        $url_params = $request->get_url_params();
        $id_upd = $url_params['id'];

        $fields_orig     = $request->get_json_params() ?? $request->get_body_params();

        //Converts Maarifa field names to DT field names
        $fields3 = $this->map_fields_to_contact( $fields_orig );


        $this->update_maarifa_contact( $request, $id_upd, $fields3 );
    }


    public function update_maarifa_contact( WP_REST_Request $request, int $p_post_id, $fields2 ){


        dt_write_log( 'Update_maarifa_contact' );

        if ( isset( $p_post_id ) )
        {

            $post_id = $p_post_id;
        }
        else {
            $post_id = $url_params['id'];
        }

        $url_params = $request->get_url_params();
        $get_params = $request->get_query_params();
        $silent = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';

        return DT_Posts::update_post( $url_params['post_type'], $post_id, $fields2, $silent );
    }


    public function check_field_value_exists( WP_REST_Request $request, string $maarifa_contact_id ) {
        //Verify if maarifa_data already exists

        dt_write_log( 'Check_field_value_exists' );

        $params = $request->get_params();

        if ( $maarifa_contact_id != null ) {

            global $wpdb;
            $result = $wpdb->get_results( $wpdb->prepare(
                "SELECT `post_id`
                            FROM $wpdb->postmeta
                            WHERE meta_key LIKE `maarifa_data`
                            AND meta_value = %s;", $maarifa_contact_id ) );


            return $result;

        }
        return [];
    }


    public function add_interactions( WP_REST_Request $request ){

        dt_write_log( 'Add_interactions' );

        $url_params = $request->get_url_params();
        $get_params = $request->get_query_params();
        $body = $request->get_json_params() ?? $request->get_body_params();
        $silent = isset( $get_params['silent'] ) && $get_params['silent'] === 'true';
        $args = [];

        $type = 'maarifa';

        if ( !empty( $body ) ) {

            $value = null;
            $result = null;
            $ret = null;

            foreach ( $body as $key => $value ) {


                if ( isset( $value['when_made'] ) ){
                    $args['comment_date'] = $value['when_made'];
                }

                if ( isset( $value['responder_name'] ) ){
                    $args['user_id'] = $value['responder_name'];
                }

                if ( isset( $value['notes'] ) ){

                    $comment = 'Type: '. $type ."\n". 'Responder name: '. $value['responder_name']. "\n" .$value['notes'];

                }

                if ( isset( $value['meta'] ) ) {
                    $args['comment_meta'] = $value['meta'];
                }

                if ( isset( $value['id'] ) )
                {//If comment_id exists, update the comment

                    $id = $value['id'];

                    dt_write_log( 'Add_interactions UPDATE' );
                    dt_write_log( $id );

                    $result = DT_Posts::update_post_comment( $id, $comment, true, $type, $args );
                }
                else { //If doesn't, create a new comment
                    dt_write_log( 'Add_interactions CREATE' );

                    $result = DT_Posts::add_post_comment( $url_params['post_type'], $url_params['id'], $comment, $type, $args, true, $silent );
                }
            }

            if ( is_wp_error( $result ) ) {

                return $result;
            }
            else {

                $ret = get_comment( $result )->to_array();
                unset( $ret['children'] );
                unset( $ret['populated_children'] );
                unset( $ret['post_fields'] );
                $ret['comment_meta'] = get_comment_meta( $ret['comment_ID'] );
                return $ret;
            }
        }

    }

    public function add_user_location( WP_REST_Request $request, $post_id ) {

        $url_params = $request->get_url_params();
        $body = $request->get_json_params() ?? $request->get_body_params();

/*        dt_write_log( 'Add_user_location' );
        dt_write_log( $body['id'] );
        dt_write_log( $url_params['post_type'] );
        dt_write_log( $post_id );  */

        $result = DT_Posts::geolocate_addresses( $post_id, $url_params['post_type'], 'contact_address', $body['country'] );
/*
        dt_write_log( 'result' );
        dt_write_log( $result );  */

        return $result;
    }

}

    Dt_Maarifa_Endpoints::instance();



