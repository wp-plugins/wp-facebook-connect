<?php 
/*
Plugin name: facebook connect
*/

//hooks - some javascript in header, login process (fb_login_user), shortcode, widget and meniu
add_action('wp_head', 'facebook_header');
add_action('plugins_loaded', 'fb_login_user');
add_action('wp_footer', 'fb_footer');
add_shortcode('fb_login', 'fb_login');
add_action('widgets_init', create_function('', 'return register_widget("FB_Connect_Widget");'));
add_action('admin_menu', 'fb_connect_menu');

//constants - Application API ID and Application Secret
define('FACEBOOK_APP_ID', get_option('fbconnect_api_id'));
define('FACEBOOK_SECRET', get_option('fbconnect_secret'));


//taken from here: http://developers.facebook.com/docs/guides/web
function get_facebook_cookie($app_id, $application_secret) {
  if( FACEBOOK_APP_ID == '' || FACEBOOK_SECRET == '' )
  	return false;
  $args = array();
  parse_str(trim($_COOKIE['fbs_' . $app_id], '\\"'), $args);
  ksort($args);
  $payload = '';
  foreach ($args as $key => $value) {
    if ($key != 'sig') {
      $payload .= $key . '=' . $value;
    }
  }
  if (md5($payload . $application_secret) != $args['sig']) {
    return null;
  }
  return $args;
}

//some JavaScript in header. I should investigate PHP api, because this is a bit funky way to do it.
function facebook_header(){ 
  if( FACEBOOK_APP_ID == '' || FACEBOOK_SECRET == '' )
  	return false;
?>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
jQuery(document).ready(function(){
	  FB.init({appId: '<?php echo FACEBOOK_APP_ID; ?>', status: true,
	           cookie: true, xfbml: true});
	  FB.Event.subscribe('auth.sessionChange', function(response) {
	    if (response.session) {
	    jQuery('body').html('');
	      window.location.href=window.location.href;
	    } else {
	    jQuery('body').html('');
	      window.location.href=window.location.href;
	    }
	  });
});
 </script>
<?php
}

//login button shortcode function
function fb_login($atts){
	if( FACEBOOK_APP_ID == '' || FACEBOOK_SECRET == '' )
  		return false;
	global $wpdb;
	extract(shortcode_atts(array(
		'size' => 'medium',
		'login_text' => 'Login',
		'logout_text' => 'Logout'
	), $atts));
	$cookie = get_facebook_cookie(FACEBOOK_APP_ID, FACEBOOK_SECRET);
	//only show facebook connect when user is not logged in
	if ( !is_user_logged_in() && !$cookie) { ?>
      <fb:login-button perms="email" size="<?php echo $size; ?>" ><?php echo $login_text; ?></fb:login-button>
    <?php } elseif( is_user_logged_in() && $cookie ){  ?>
      <a class="fb_button fb_button_<?php echo $size; ?>" href="javascript:FB.logout(function(){location.href='<?php echo wp_logout_url( get_bloginfo('url') ) ?>'})">
      	<span class="fb_button_text">
      		<?php echo $logout_text; ?>
      	</span>
      </a>
    <?php } ?>
<?php
}

function fb_footer(){
	if( FACEBOOK_APP_ID == '' || FACEBOOK_SECRET == '' )
	  	return false;
?> <div id="fb-root"></div> <?php
}

//login function
function fb_login_user(){
	//only do when user is not logged in
	if(!is_user_logged_in()){
		global $wpdb;
		//@todo: investigate: does this gets included doing regular request?
		require_once( ABSPATH . 'wp-includes/registration.php' );
		//mmmm, cookie
		$cookie = get_facebook_cookie(FACEBOOK_APP_ID, FACEBOOK_SECRET);
		//if we have cookie, then try to get user data
		if ($cookie) {
			//get user data
		    $user = json_decode(file_get_contents('https://graph.facebook.com/me?access_token=' . $cookie['access_token']));
		    //if user data is empty, then nothing will happen
		    if( !empty($user) ){
		    	//this should never happen, since email address is required to register in FB, but i put it here just in case of API changes or some other disaster
			    if( !isset($user->email) || empty($user->email) )
			    	wp_die("Error: failed to get your email from Facebook!");
			    //check if user has account in the website. get id and login
			    //@todo: change these to use only one sql query
			    $existing_user = absint($wpdb->get_var( 'SELECT ID FROM ' . $wpdb->users . ' WHERE user_email = "' . $user->email . '"' ));
			    $existing_user_login = $wpdb->get_var( 'SELECT user_login FROM ' . $wpdb->users . ' WHERE user_email = "' . $user->email . '"' );
			    //if the user egists - set cookie, do wp_login, redirect and exit
			    if( $existing_user > 0 ){
			    	wp_set_auth_cookie($existing_user, true, false);
			    	do_action('wp_login', $existing_user_login);
			    	wp_redirect(wp_get_referer());
			    	exit();
			    //if user don't exist - create one and do all the same stuff: cookie, wp_login, redirect, exit
				} else {
					//sanitize username
					$username = sanitize_user($user->first_name, true);
	
					//check if username is taken
					//if so - add something in the end and check again
					$i='';
					while(username_exists($username . $i)){
						$i=absint($i);
						$i++;
					}
					
					//this will be new user login name
					$username = $username . $i;
					
					//put everything in nice array
					$userdata = array(
						'user_pass'		=>	wp_generate_password(),
						'user_login'	=>	$username,
						'user_nicename'	=>	$user->name,
						'user_email'	=>	$user->email,
						'display_name'	=>	$user->name,
						'nickname'		=>	$username,
						'first_name'	=>	$user->first_name,
						'last_name'		=>	$user->last_name,
						'role'			=>	'subscriber'
					);
					//create new user
					$new_user = absint(wp_insert_user($userdata));
					//if user created succesfully - log in and reload
					if( $new_user > 0 ){
						wp_set_auth_cookie($existing_user, true, false);
				    	do_action('wp_login', $existing_user_login);
				    	wp_redirect(wp_get_referer());
				    	exit();
					}
				}
			}
	    }
    }
}

//widget
class FB_Connect_Widget extends WP_Widget {
    /** constructor */
    function FB_Connect_Widget() {
        parent::WP_Widget(false, $name = 'Facebook Connect');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {	
    	if( FACEBOOK_APP_ID == '' || FACEBOOK_SECRET == '' )
	  		return false;	
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $size = $instance['size'];
        $login_text = $instance['login_text'];
        $logout_text = $instance['logout_text'];
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  <div style="text-align:center; margin-bottom:10px;"><?php do_shortcode("[fb_login size='$size' login_text='$login_text' logout_text='$logout_text']"); ?></div>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['size'] = strip_tags($new_instance['size']);
	$instance['login_text'] = strip_tags($new_instance['login_text']);
	$instance['logout_text'] = strip_tags($new_instance['logout_text']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
    	if( FACEBOOK_APP_ID == '' || FACEBOOK_SECRET == '' ){
	  		_e("Plese go to Settings->FB connect and set your facebook application API key and facebook secret");
	  		return false;
	  	}
				
        $title = esc_attr($instance['title']);
        $size = esc_attr($instance['size']);
        $login_text = esc_attr($instance['login_text']);
        $logout_text = esc_attr($instance['logout_text']);
        ?>
            <p>
            	<label for="<?php echo $this->get_field_id('title'); ?>">
            		<?php _e('Widget title:'); ?>
            		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            	</label>
            </p>
            <p>
            	<label for="<?php echo $this->get_field_id('login_text'); ?>">
            		<?php _e('Login button text:'); ?>
            		<input class="widefat" id="<?php echo $this->get_field_id('login_text'); ?>" name="<?php echo $this->get_field_name('login_text'); ?>" type="text" value="<?php echo $login_text; ?>" />
            	</label>
            </p>
            <p>
            	<label for="<?php echo $this->get_field_id('logout_text'); ?>">
            		<?php _e('Logout button text:'); ?>
            		<input class="widefat" id="<?php echo $this->get_field_id('logout_text'); ?>" name="<?php echo $this->get_field_name('logout_text'); ?>" type="text" value="<?php echo $logout_text; ?>" />
            	</label>
            </p>
            <p>
            	<label for="<?php echo $this->get_field_id('size'); ?>">
            		<?php _e('Size:'); ?>
            		<select id="<?php echo $this->get_field_id('size'); ?>" name="<?php echo $this->get_field_name('size'); ?>" >
         				<?php $options = array(__('small'), __('medium'), __('large'), __('xlarge')); ?>
         				<?php foreach( (array)$options as $option ){ ?>
         				<?php
         				if( $option != $size )
         					$selected='';
         				else
         					$selected = 'selected="selected"';
         				  ?>
         				<option value="<?php echo $option; ?>" <?php echo $selected; ?> ><?php echo $option; ?></option>
         				<?php } ?>
         		   	</select>
         		</label>
            </p>
        <?php 
    }

}

function fb_connect_menu() {
	add_options_page('FB Connect options', 'FB Connect', 'manage_options', 'fb_connect_options', 'fb_connect_options');
	add_action( 'admin_init', 'register_fb_connect_mysettings' );
}

function register_fb_connect_mysettings(){
	register_setting( 'fb_connect_settings', 'fbconnect_api_id' );
	register_setting( 'fb_connect_settings', 'fbconnect_secret' );
}

function fb_connect_options() {
  ?>
	<div class="wrap">
		<h2><?php _e('Facebook Connect'); ?></h2>
	
		<form method="post" action="options.php">
			<?php settings_fields( 'fb_connect_settings' ); ?>
			
			<table class="form-table">
			
				<tr valign="top">
					<th scope="row"><?php _e('Facebook Application API ID:'); ?></th>
					<td><input type="text" name="fbconnect_api_id" value="<?php echo get_option('fbconnect_api_id'); ?>" /></td>
				</tr>
				 
				<tr valign="top">
					<th scope="row"><?php _e('Faceook Application secret:'); ?></th>
					<td><input type="text" name="fbconnect_secret" value="<?php echo get_option('fbconnect_secret'); ?>" /></td>
				</tr>
			</table>
			
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		
		</form>
	</div>
  <?php

}
?>