<?php

/* - - - - - -
	
	Class containing everything for our options page.
	
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
class f2w_admin {
	
	protected $options = null;

	protected $posted = false;
	
	function __construct() {
		
		global $f2w_setup_defaults;
		
		if ( is_array($f2w_setup_defaults) )
			$this->options = array_merge($f2w_setup_defaults, get_option('f2w_options'));
		else
		
		$this->options = get_option('f2w_options');
	}
	
	function add_options_menu() {
		$page = add_options_page('Facebook to WP', 'Facebook to WP','manage_options', __file__, array(&$this,'options_page'));
	}
	
	/* - - - - - -
		
		Register our settings.
		
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
	function settings_api_init() {
		
		
		register_setting( 'facebook-to-wordpress', 'f2w_options', array(&$this, 'validate_options') ); 

		// App ID & Secret
		add_settings_section('fb_app_info', __('Facebook App ID & Secret', 'facebook2wp'), array(&$this, 'setting_section_callback_function'), __FILE__);

		add_settings_field('f2w_app_id', __('App ID', 'facebook2wp'), array(&$this, 'app_id_field'), __FILE__, 'fb_app_info');
		add_settings_field('f2w_secret', __('App Secret', 'facebook2wp'), array(&$this, 'secret_field'), __FILE__, 'fb_app_info');

		// Feed Info
		add_settings_section('fb_feed_info', __('Facebook Feed Info', 'facebook2wp'), array(&$this, 'setting_section_callback_function'), __FILE__);

		add_settings_field('f2w_feed_name', __('Feed Name', 'facebook2wp'), array(&$this, 'feed_name_field'), __FILE__, 'fb_feed_info');

		add_settings_field('f2w_feed_reset', __('Reset'), array(&$this, 'feed_reset_field'), __FILE__, 'fb_feed_info');
	}
	
	function setting_section_callback_function( $section ) {
		switch ( $section['id'] ) {
			case 'fb_app_info':
				_e("<p>You will need a facebook App ID and Secret key. To get them you must register as a developer and create an application, which you can do from their <a href='https://developers.facebook.com/setup' target='_blank'>Create an App</a> page.</p>", 'facebook2wp');
				break;
			case 'fb_feed_info':
				break;

		}
	}

	
	
	/* - - - - - -
		
		The options page.
		
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
	function options_page() {
		if (!current_user_can('manage_options'))
			wp_die( __('You do not have sufficient permissions to access this page.', 'facebook2wp') );
		?>
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br /></div>
			<h2><?php _e('Facebook to WordPress', 'facebook2wp') ?></h2>
			<form action="options.php" method="post">
			<?php settings_fields('facebook-to-wordpress'); ?>
			<?php do_settings_sections(__file__); ?>
			<p class="submit">
				<input name="Submit" type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
				<span class="description">&nbsp;&nbsp;<?php _e('It may take some time.', 'facebook2wp') ?></span>
			</p>
			</form>
		</div>
		<?php
	}
	
	function app_id_field() {
		?>
		<input type="text" name="f2w_options[app_id]" value="<?php echo esc_attr($this->options['app_id']); ?>" class="regular-text" id="f2w-app-id" autocomplete="off" />
		<span class="description"><?php _e('Required for the plugin to work.', 'facebook2wp') ?></span>
		<?php
	}
	
	function secret_field() {
		?>
		<input type="text" name="f2w_options[secret]" value="<?php echo esc_attr($this->options['secret']); ?>" class="regular-text" id="f2w-secret" autocomplete="off" />
		<span class="description"><?php _e('Required for the plugin to work.', 'facebook2wp') ?></span>
		<?php
	}
	
	function feed_name_field() {
		?>
		<span>http://www.facebook.com/</span><input type="text" name="f2w_options[feed_name]" value="<?php echo esc_attr($this->options['feed_name']); ?>" class="regular-text" /> 
		<span class="description"><?php _e('The name of the feed.', 'facebook2wp') ?></span>
		<br>
		<span class="description"><?php echo ' cron path: '.dirname(dirname(dirname(__FILE__))).'/wp-cron.php' ?></span>
		<?php
	}

	function feed_reset_field() {
		?>
		<input type="checkbox" name="f2w_options[feed_reset]" value="1" id="_feed_reset"/> 
		<label for="_feed_reset"><span class="description"><?php _e('Reset the posting history.', 'facebook2wp') ?></span></label>
		<?php
	}
	
	/* - - - - - -
		
		Validate our options.
		
	- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
	function validate_options( $input ) {
		//var_dump($input);

		if (!current_user_can('manage_options'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		// Wordpress should be handling the nonce.

		// Validate App Id
		if ( !preg_match('/^[0-9]{10,}$/', trim($input['app_id'])) ) {

			$input['app_id'] = null;

			// Tell wp of the error (wp 3+)
			if ( function_exists('add_settings_error') )
				add_settings_error( 'f2w_app_id', 'app-id', __('You do not appear to have provided a valid App Id for your Facebook Application.', 'facebook2wp') );

		} else
			$input['app_id'] = trim($input['app_id']);

		// Validate Secret
		if ( !preg_match('/^[0-9a-z]{27,37}$/i', trim($input['secret'])) ) {

			$input['secret'] = null;		

			// Tell wp of the error (wp 3+)
			if ( function_exists('add_settings_error') )
				add_settings_error( 'f2w_secret', 'secret', __('You do not appear to have proivided a valid Secret for your Facebook Application.', 'facebook2wp') );

		} else {
			$input['secret'] = trim($input['secret']);
		}

		// Misc Settigns
		$input['feed_name'] = trim($input['feed_name']);

		if ( !$input['feed_name'] ){
			$input['feed_id'] = null;
			$input['feed_name'] = null;
		}else if ( ctype_digit($input['feed_name']) !== false ) {
			$input['feed_id'] = $input['feed_name'];
		} else {
			include_once 'f2w-facebook.php';
			$fb = new f2w_fb( $input['app_id'], $input['secret'] );
			if ( !$fb ) {
				echo 'Invalid';
				die();
			}
			$profile = $fb->api('/'.$input['feed_name']);
			if ( $profile && isset($profile['id']) ) {
				$input['feed_id'] = $profile['id'];
			} else {
				$input['feed_id'] = null;
				$input['feed_name'] = null;
			}
		}

		$reset = $input['feed_reset'];
		if ( $reset ){
			$input['post_ids'] = array();
		}
		if ( $reset || ( $input['feed_id'] && ($input['feed_id'] !== $this->options['feed_id']) ) ){
			add_action('update_option_f2w_options', array(&$this, 'update_options'));
		}

		unset($input['feed_reset']);
		$input = array_merge($this->options, $input);

		return $input;
	}

	function update_options() {
		if ( !$this->posted ) {
			$this->posted = true;
			f2w_post(100);
		}
	}

}

// Hook stuff in.
$f2w_admin = new f2w_admin();

add_action('admin_menu', array(&$f2w_admin, 'add_options_menu'));
add_action('admin_init', array(&$f2w_admin, 'settings_api_init'));

?>