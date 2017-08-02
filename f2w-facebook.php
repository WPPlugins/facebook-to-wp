<?php
/*
	Facebook wrapper class
*/
class f2w_fb {

	public $facebook = null;

	private $app_id = null;
	private $secret = null;

	function __construct($app_id, $secret) {
		if( $app_id )
			$this->app_id = $app_id;
		if( $secret )
			$this->secret = $secret;
		$this->_load_sdk();
		$this->facebook = $this->_init_facebook( $this->app_id, $this->secret );
		if( !$this->facebook )
			return null;
	}

	function _load_sdk() {
		if ( !class_exists('Facebook') )
			require_once 'facebook-sdk/facebook.php';
	}

	function _init_facebook($app_id, $secret) {
		$params = array(
			'appId'  => $app_id,
			'secret' => $secret
		);
		return new Facebook($params);
	}

	function api($name) {
		if( !$this->facebook )
			return null;
		return $this->facebook->api($name);
	}
}

?>