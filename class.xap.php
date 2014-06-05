<?php

class Xap {

	/**
	 * Holds a static copy of the Xap class for the singeton
	 *
	 * @var Xap
	 */
	private static $instance = null;

	/**
	 * Temporarily holds the new application password
	 *
	 * @var string
	 **/
	private $temp_password = null;

	private function __construct() {

		// If we are working with an xmlrpc client lets take over authentication
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			add_filter( 'authenticate', array( $this, 'intercept_xmlrpc_login' ), 10, 3 );
		}
		
		// Add user profile section
		add_action( 'show_user_profile', array( $this, 'show_user_profile' ) );
		add_action( 'edit_user_profile', array( $this, 'show_user_profile' ) );
	}

	/**
	 * Provides access to an instance of Xap
	 *
	 * @return Xap
	 */
	public static function get_instance() {
		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = new Xap();

		return self::$instance;
	}

	public function intercept_xmlrpc_login( $input_user, $username, $password ) {
		// Make sure a username and password are present for us to work with
		if ( $username == '' || $password == '' ) {
			return;
		}

		// Lets see if the username matches an existing user
		$user     = get_user_by( 'login',  $username );
		if (!$user) return $input_user;

		$appass   = get_user_meta( $user->ID, XAP_USER_META_KEY );
		$password = preg_replace( '/[^a-z\d]/i', '', $password );

		foreach ( $appass AS $app ) {
			if ( wp_check_password( $password, $app['password'], $user->ID ) ) {
				$orig_app = $app;
				$app['last_used'] = time();
				update_user_meta( $user->ID, XAP_USER_META_KEY, $app, $orig_app );
				return $user;
			}
		}

		// No user found!
		return $input_user;
	}

	public function notice_success() {
		echo "<div class='updated'>";
		echo '<p>' . __( 'Application password successfully created. Please note the password as it will <b>not be shown again</b>.' ) . '</p>';
		echo '<p>' . __( 'New Application Password:' ) . ' <tt>' . chunk_split( $this->temp_password, 4, ' ' ) . '</tt></p>';
		echo "</div>";
	}

	public function notice_fail() {
		echo "<div class='error'>";
		echo __( 'Unable to create Application Password. Please try again or contact support for help.' );
		echo '</div>';
	}

	/**
	 * Adds new application passwords to the user's profile
	 *
	 * @param integer $user_id
	 */
	public function maybe_update_user_profile( $user ) {
		if ( empty( $_POST['app_name'] ) || ! current_user_can( 'edit_user', $user->ID ) ) {
			return false;
		}

		// If the 'Create Password' button wasn't pressed, don't do anything.
		if ( ! isset( $_POST['create_password'] ) ) {
			return;
		}

		// Generate a new password
		$password = wp_generate_password( 16, false );
	
		$hashed_password = wp_hash_password( $password );
		// Setup data to be saved regarding the password
		$data = array( 
			'password'		=> $hashed_password,
			'application'	=> $_POST['app_name'],
			'created'		=> time(),
			'last_used'		=> -1
		);

		$added = add_user_meta( $user->ID, XAP_USER_META_KEY, $data );
		if( ! is_wp_error( $added ) ) {
			return $password;
		} else {
			return false;
		}
	}


	/**
	 * Handles displaying the Application Password of the
	 * user profile section
	 *
	 * @param WP_User $user
	 */
	public function show_user_profile( $user ) {

		$updated = $this->maybe_update_user_profile( $user );

		require_once( 'class.xap-profile-list-table.php' );
		$list_table = new Xap_Profile_List_Table();
		echo '<div id="xap-profile" class="wrap"><h2>' . __( 'Application Passwords' ) . '</h2>';

		if ( $updated ) {
			echo '<div class="updated">';
			echo '<p><strong>' . __( 'Please note the password as it will <b>not be shown again</b>.' ) . '</strong></p>';
			echo '<p>' . __( 'New Application Password:' ) . ' <tt>' . chunk_split( $updated, 4, ' ' ) . '</tt></p>';
			echo '</div>';
		}
		echo '<p>' . __ ( 'To create a new password type the name of the application in the box below.' ) . '</p>';
		echo '<form method="post">';
		echo __( 'Application Name' ) . ': <input type="text" name="app_name" />';
		submit_button( 'Create Password', 'primary', 'create_password', false );
		echo '</form>';
		echo '<form method="post">';
		$list_table->prepare_items();
		$list_table->display();
		echo '</form></div>';

		?>
		<script>
		jQuery( document ).ready(function() {
		//	jQuery( '#xap-profile' ).insertAfter( '#profile-page' );
		});
		</script>
		<?php
	}

	/**
	 * Remove an application password for the current user
	 *
	 * @param string $password - md5'd hash
	 */
	public function remove_application( $password ) {
		$user_id = get_current_user_id();

		$appass = get_user_meta( $user_id, XAP_USER_META_KEY );

		foreach ( $appass AS $app ) {
			if ( $password == md5( $app['application'] . '|' . $app['created'] ) ) {
				delete_user_meta( $user_id, XAP_USER_META_KEY, $app );
				break;
			}
		}
	}
} // end class Xap
