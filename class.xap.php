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
		if( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) {
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
		if( !is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = new Xap();

		return self::$instance;
	}

	public function intercept_xmlrpc_login( $user, $username, $password ) {
		// Make sure a username and password are present for us to work with
		if($username == '' || $password == '') return;

		// Lets see if the username matches an existing user
		$user = get_user_by( 'login',  $username );
	
		$appass = get_user_meta( $user->ID, XAP_USER_META_KEY );

		foreach( $appass AS $app ) {
			if( sha1( $password ) == $app['password'] ) {
				return $user;
			}
		}

		// No user found!
		return null;
	}

	public function notice_success() {
		echo "<div class='updated'>";
		echo '<p>' . __( 'Application password successfully created. Please note the password as it will <b>not be shown again</b>.' ) . '</p>';
		echo '<p>' . __( 'New Application Password:' ) . ' ' . $this->temp_password . '</p>';
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
		if ( !isset( $_POST['app_name'] ) || !current_user_can( 'edit_user', $user->ID ) ) {
			return false;
		}

		// Generate a new password
		$password = wp_generate_password( 16, false );
		$password = chunk_split( $password, 4, ' ' );
		$password = preg_replace( '/[^a-z\d]/i', '', $password );
echo "Generated password: $password";
		// Setup data to be saved regarding the password
		$data = array( 
			'password'		=> $password,
			'application'	=> esc_attr( $_POST['app_name'] ),
			'created'		=> time(),
			'last_used'		=> -1
		);

		$added = add_user_meta( $user->ID, XAP_USER_META_KEY, $data );
		if( !is_wp_error( $added ) ) {
			add_action( 'admin_notices', array( $this, 'notice_success' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'notice_fail' ) );
		}
	}


	/**
	 * Handles displaying the Application Password of the
	 * user profile section
	 *
	 * @param WP_User $user
	 */
	public function show_user_profile( $user ) {

		$this->maybe_update_user_profile( $user );

		require_once( 'class.xap-profile-list-table.php' );
		$list_table = new Xap_Profile_List_Table();
		echo '<div class="wrap"><h2>' . __( 'Application Passwords' ) . '</h2>';
		echo '<p>To create a new password type the name of the application in the box below.</p>';
		echo '<form method="post">';
		echo '<input type="text" name="app_name" />';
		submit_button( 'Create Password', 'primary', 'create_password', false);
		echo '</form>';
		echo '<form method="post">';
		$list_table->prepare_items();
		$list_table->display();
		echo '</form></div>';
	}

	/**
	 * Remove an application password for the current user
	 *
	 * @param string $password - md5'd hash
	 */
	public function remove_application( $password ) {
		$user_id = get_current_user_id();

		$appass = get_user_meta( $user_id, XAP_USER_META_KEY );

		foreach( $appass AS $app ) {
			if( $password == md5( $app['password'] ) ) {
				delete_user_meta( $user_id, XAP_USER_META_KEY, $app );
				break;
			}
		}
	}
} // end class Xap
