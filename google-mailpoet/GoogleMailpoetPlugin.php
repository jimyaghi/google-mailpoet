<?php
/**
 * بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيم
 *
 * Created by Jim Yaghi
 * Date: 2021-10-31
 * Time: 11:17
 *
 */


namespace YL {


	class GoogleMailpoetPlugin {

		/**
		 * @var GoogleMailpoetPlugin
		 */
		private static $instance = null;


		/**
		 * Initialises our plugin
		 */
		private function __construct() {
			$this->attach_hooks();
		}

		/**
		 * instantiates an instance of this plugin and ensures only one such instance exists
		 * @return GoogleMailpoetPlugin
		 */
		public static function getInstance() {
			if ( ! static::$instance ) {
				static::$instance = new static();
			}

			return static::$instance;
		}


		/**
		 * Add subscribers to multiple MailPoet lists when receiving data from Zapier with WP Zapier plugin.
		 * i.e. Add users to MailPoet lists via Zapier when added to Google Sheets.
		 * For full blog post visit - https://yoohooplugins.com/add-subscribers-mailpoet-zapier
		 */

		public function add_subscribers( $user ) {

			if ( ! class_exists( \MailPoet\API\API::class ) ) {
				exit;
			}
			if ( ! function_exists( '\\wpzp_get_user' ) ) {
				exit;
			}

			//If lists aren't passed through just bail.
			if ( empty( $_REQUEST['lists'] ) ) {
				exit;
			}

			$lead = [];
			foreach ( ( $_REQUEST['user_column_data'] ?? [] ) as $data ) {
				$lead[ strtolower( $data['column_id'] ) ] = $data['string_value'];
			}

			if ( ! ( $email = sanitize_email( $lead['email'] ?? '' ) ) ) {
				exit;
			}


			$lists_id = explode( ',', $_REQUEST['lists'] );
			$lists    = array_filter( array_map( function ( $list_id ) {
				return intval( $list_id );
			}, $lists_id ) );

			$mailpoet_api = \MailPoet\API\API::MP( 'v1' );

			$first_name = $lead['first_name'] ?? '';
			$last_name  = $lead['last_name'] ?? '';
			$phone      = $lead['phone_number'];

			$password = wp_generate_password( 12, true, true );
			$user     = get_user_by( 'email', $email );

			if ( ! $user ) {
				$user_id = wp_create_user( $email, $password, $email );
				if ( is_wp_error( $user_id ) ) {
					exit;
				}
				$user = \get_user_by( 'email', $email );
				$user->set_role( 'subscriber' );
				if ( ! $first_name && ! $last_name ) {
					$name       = explode( '@', $email );
					$first_name = array_shift( $name );
				}
				// the password is not known to the user
				update_user_meta( $user_id, "_password_shown", 'no' );
			}

			// Let's not allow calls to update administrators.
			if ( in_array( 'administrator', $user->roles ) ) {
				exit;
			}

			$user_id = $user->ID;
			wp_update_user( [ 'ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name ] );
			update_user_meta( $user_id, "phone", $phone );

			$subscriber = null;

			// See if the subscriber exists first.
			try {
				$subscriber = $mailpoet_api->getSubscriber( $email );

				// If the subscriber doesn't exist, add them.
				if ( ! $subscriber ) {
					$subscriber = $mailpoet_api->addSubscriber( [ 'email' => $email ], $lists_id );
				}
			} catch ( \Throwable $th ) {
			}

			// Try add the user to lists.
			if ( $subscriber ) {
				$user_id = $subscriber['id'];

				// add users to the lists.
				try {
					$subscriber = $mailpoet_api->subscribeToLists( $user_id, $lists );
				} catch ( \Throwable $th ) {
				}
			}


			if($subscriber) {
				echo json_encode( [
					'status'   => 'success',
					'response' => 'user updated successfully',
					'user_id'  => $user_id
				] );
			}
			exit();
		}

		public function attach_hooks() {
			add_action( 'wp_zapier_custom_webhook', [ $this, 'add_subscribers' ] );
		}
	}

}
