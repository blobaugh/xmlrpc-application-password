<?php
/*
 * Inserts test data into the database
 */


class xap_test_data {

	private $data = array(
		array(
        	'password'    => '$P$B1kLw0q9obog6iq.WWFmTMdzoSHJj90',
        	'application' => 'WordPress for Android',
        	'last_used'   => 1398976395,
        	'created'		=> 1398976395 
    	),
    	array(
        	'password'    => '$P$BoJqpfkmOPzPNHD.wBA.hwAmJzqTFS/',
        	'application' => 'WordPress for iOS',
        	'last_used'   => 1396384395,
        	'created'		=> 1396384395
    	),
    	array(
        	'password'    => '$P$BM8NJkvyrYNu21xkCdqK3U2v4tSxY8.',
        	'application' => 'Pressgram',
        	'last_used'   => 1396384395,
        	'created'		=> 1396384395
    	),
	);


	private $meta_key = '_application_passwords';

	public function insert_test_data() {
		$user_id = get_current_user_id();

		foreach( $this->data AS $data ) {
			add_user_meta( $user_id, $this->meta_key, $data );
		}
	}

	public function delete_test_data() {
		$user_id = get_current_user_id();
		delete_user_meta( $user_id, $this->meta_key );
	}

} // end class xap_test_data
