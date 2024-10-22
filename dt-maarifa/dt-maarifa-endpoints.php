<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly

    /**
     * Class DT_Maarifa_Endpoints
     */
class DT_Maarifa_Endpoints {
    /**
     * @var object Public_Hooks instance variable
     */
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

    /**
     * DT_Maarifa_Endpoints constructor.
     */
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
            ]
        ];


        register_rest_route(
            $this->namespace, '/contacts', [
            'methods'  => 'POST',
            'callback' => [ $this, 'contacts' ],
            'args' => [
               'post_type' => $arg_schemas['post_type'],
            ],
            'permission_callback' => function( WP_REST_Request $request ) {
                return $this->has_permission();
            },

            ]
        );

        register_rest_route(
            $namespace, '/contacts/(?P<id>\d+)', [
            'methods'  => 'POST',
            'callback' => [ $this, 'contact_by_id' ],
            //                'args' => [
            //                    'post_type' => $arg_schemas['post_type'],
            //                    'id' => $arg_schemas['id'],
            //                ],
                'args' => [
                    'comment' => [
                        'description' => 'The comment text',
                        'type' => 'string',
                        'required' => true,
                        'validate_callback' => [ $this, 'prefix_validate_args' ]
                    ],
                    'post_type' => $arg_schemas['post_type'],
                    'id' => $arg_schemas['id'],
                    'comment_id' => $arg_schemas['comment_id'],
                    'comment_type' => $arg_schemas['comment_type']
                ],
            'permission_callback' => function( WP_REST_Request $request ) {
                return $this->has_permission();
            },
            ]
        );

        register_rest_route(
            $namespace, '/contacts/(?P<id>\d+)/interactions', [
            'methods'  => 'POST',
            'callback' => [ $this, 'contact_interaction' ],
            'args' => [
                'post_type' => $arg_schemas['post_type'],
                'id' => $arg_schemas['id'],
            ],
            'permission_callback' => function( WP_REST_Request $request ) {
                return $this->has_permission();
            },
            ]
        );

    }


    public function contacts( WP_REST_Request $request ) {

        // @todo run your function here
        dt_write_log( 'teste 1' );

        return true;
    }

    public function contact_by_id( WP_REST_Request $request ) {

        // @todo run your function here
        dt_write_log( 'teste 2' );

        return true;
    }

    public function contact_interaction( WP_REST_Request $request ) {

        // @todo run your function here
        dt_write_log( 'teste 3' );

        return true;
    }
}
