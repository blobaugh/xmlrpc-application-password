<?php

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class XAP_Profile_List_Table extends WP_List_Table {


	/**
	 * Sets up the list of columns that will be displayed.
	 * Includes the programmatic and visual labels.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        	=> '<input type="checkbox" />',
			'application'	=> __( 'Application' ),
			'created'		=> __( 'Created' ),
			'last_used'		=> __( 'Last Used' )
		);

		return $columns;
	}

	/**
	 * Fetches any and all data needed from the databass and performs 
	 * additional processing such as sorting, bulk actions, etc
	 */
	public function prepare_items() {
		// Get the id of the current user
		$user_id = get_current_user_id();

		// Deal with bulk actions if any were requested by the user
		$this->process_bulk_action();

		// Get the list of application passwords for this user
		$appass = get_user_meta( $user_id, XAP_USER_META_KEY );

		// Setup pagination
		$per_page = 40;
		$current_page = $this->get_pagenum();
		$total_items = count( $appass );
		$appass = array_slice( $appass, ( ( $current_page-1 ) * $per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items'	=> $total_items,
			'per_page'		=> $per_page
		) );

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $appass;
	}

	/**
	 * Sets up the dropdown containing the bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete' )
		);
		return $actions;
	}

	public function process_bulk_action() {
		if( !isset( $_POST['bulk'] ) || empty ( $_POST['bulk'] ) ) {
			return; // Thou shall not pass! There is nothing to do
		}

		$action = $this->current_action();
		switch( $action ) {
			case 'delete':
				foreach( $_POST['bulk'] AS $k => $pass ) {
					Xap::get_instance()->remove_application( $pass );
				}
		}
	}


	/**
	 * Bulk action column
	 */
	public function column_cb($item) {
        	return sprintf(
            		'<input type="checkbox" name="bulk[]" value="%s" />', md5( $item['password'] ) 
        	);    
    }

    public function column_application( $item ) {
    	return $item['application'];
    }

    public function column_created( $item ) {
		return date_i18n( get_option( 'date_format' ), $item['created'] );
    }

    public function column_last_used( $item ) {
		if( '-1' == $item['last_used'] ) {
			return __( 'Never' );
		}

		return date_i18n( get_option( 'date_format' ), $item['last_used'] );
    }
} // end class xap_profile_list_table
