<?php
/*
	Plugin Name: Facebook to WordPress
	Plugin URI: http://miyakeryo.com/plugin
	Description: Auto post to WordPress from Facebook page. <a href='options-general.php?page=facebook-to-wp/f2w-options.php'>Settings - Facebook to WP</a>
	Version: 1.1
	Author: Ryo Miyake
	Author URI: http://miyakeryo.com
	License: GPLv2
	Text Domain: facebook2wp
	Domain Path: /languages
*/

/*
	Uses Facebook PHP SDK (v.3.2.2)
	https://github.com/facebook/facebook-php-sdk
*/

/*
	This program is free software; you can redistribute it and/or 
	modify it under the terms of the GNU General Public License 
	as published by the Free Software Foundation; either version 2 
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/

load_plugin_textdomain( 'facebook2wp', false, basename( dirname( __FILE__ ) ).'/languages' );

class f2w_setup {

	protected $version = '0.1';

	protected $defaults = null;

	function __construct() {
		$this->defaults = array(
			// Facebook App ID & Secret
			'app_id' => null,
			'secret' => null,
		
			// Facebook Name & id
			'feed_name' => null,
			'feed_id' => null,

			// Current Version
			'version' => $this->version,

			// Posted item ids
			'post_ids' => array(),
		);

		add_action('wp_head', array(&$this, 'f2w_head'), 0);
	}

	function defaults(){
		return $this->defaults;
	}

	function activate() {
		$options = get_option('f2w_options');

		if( !is_array($options) ) {
			$options = $this->defaults;
		} else if ( $options['version'] != $this->version) {
			$options = array_merge($this->defaults, $options);
		}

		$options['version'] = $this->version;
	
		update_option('f2w_options', $options);

		// set WordPress cron
		wp_schedule_event(time(), '6timeshourly', 'f2w_cron');
	}

	function deactivate() {
		// unset WordPress cron
		wp_clear_scheduled_hook('f2w_cron');
	}

	public function f2w_head()
	{
		$css = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/css/facebook-to-wp.css';
		echo "<link rel=\"stylesheet\" href=\"$css\" type=\"text/css\" />";
		echo "<!-- Facebook to WordPress Plugin -->\n";
    }

}// end of 'class f2w_setup'

/*
if ( !session_id() )
	session_start();
*/

$f2w_feed_posted = false;
function f2w_post($limit) {
	if ( $f2w_feed_posted ) {
		return false;
	}
	$f2w_feed_posted = true;
	set_time_limit(60);
	include_once 'f2w-feed-post.php';
	$f2w_feed = new f2w_feed_post();
	if ( $f2w_feed )
		return $f2w_feed->post($limit);
	else
		return false;
}
add_action('f2w_cron', 'f2w_post');

// Adds 6times hourly to the existing schedules.
function cron_add_6timeshourly( $schedules ) {
	$schedules['6timeshourly'] = array(
		'interval' => MINUTE_IN_SECONDS*10,
		'display' => __( '6times Hourly' )
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'cron_add_6timeshourly' );

$f2w_setup = new f2w_setup();

register_activation_hook(__FILE__, array(&$f2w_setup, 'activate'));
register_deactivation_hook(__FILE__, array(&$f2w_setup, 'deactivate'));

$f2w_setup_defaults = $f2w_setup->defaults();

if ( is_admin() ){
	include_once 'f2w-options.php';
}