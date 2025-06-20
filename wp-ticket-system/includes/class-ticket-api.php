<?php
class WPTS_Ticket_API {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wpts/v1', '/tickets', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_tickets' ),
            'permission_callback' => array( $this, 'permissions_check' ),
        ) );
    }

    public function permissions_check( $request ) {
        return current_user_can( 'edit_posts' );
    }

    public function get_tickets( $request ) {
        $tickets = get_posts( array( 'post_type' => 'wpts_ticket' ) );
        return rest_ensure_response( $tickets );
    }
}
