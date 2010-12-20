<?php
//facebook_header function
//taken from here: http://developers.facebook.com/docs/guides/web
//some JavaScript in header. this is required
function facebook_header(){ 
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

//markup for FB in footer. this is not visible, but required.
function fb_footer(){
?> <div id="fb-root"></div> <?php
}

//get_facebook_cookie function
//taken from here: http://developers.facebook.com/docs/guides/web
//gets facebook cookie (yummy thing that is created when user is authenticated with FB in your website and destroyed when user is logged out of FB)
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

//this is the main function that performs the login or user creation process
function fb_login_user(){
	//only do when user is not logged in
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
	    	//this should never happen, since email address is required to register in FB
	    	//I put it here just in case of API changes or some other disaster, like wrong API key or secret
		    if( !isset($user->email) || empty($user->email) )
		    	do_action('fb_connect_get_email_error');
		    	wp_die("Error: failed to get your email from Facebook!");

	    	//if user is logged in, then we just need to associate FB account with WordPress account
	    	if( is_user_logged_in() ){
    			global $current_user;
				get_currentuserinfo();
				if($user->email == $current_user->user_email) {
					//if FB email is the same as WP email we don't need to do anything.
					do_action('fb_connect_wp_fb_same_email');
					return true;
				} else {
					//else we need to set fb_email in user meta, this will be used to identify this user
					do_action('fb_connect_wp_fb_different_email');
					update_user_meta( $current_user->ID, 'fb_email', $user->email );
					//that's it, we don't need to do anything else, because the user is already logged in.
					return true;
				}
	    	}else{
			    //check if user has account in the website. get id
			    $existing_user = absint($wpdb->get_var( 'SELECT `u`.`ID` FROM `' . $wpdb->users . '` `u` JOIN `' . $wpdb->usermeta . '` `m` ON `u`.`ID` = `m`.`user_id`  WHERE user_email = "' . $user->email . '" OR (`m`.`meta_key` = "fb_email" `m`.`meta_value` = "' . $user->email . '" ) LIMIT 1 ' ));
			    //if the user exists - set cookie, do wp_login, redirect and exit
			    if( $existing_user > 0 ){
			    	do_action('fb_connect_fb_same_email');
			    	wp_set_auth_cookie($existing_user->ID, true, false);
			    	wp_redirect(wp_get_referer());
			    	exit();
			    //if user don't exist - create one and do all the same stuff: cookie, wp_login, redirect, exit
				} else {
					do_action('fb_connect_fb_new_email');
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
					$userdata = apply_filters('fb_connect_new_userdata', $userdata, $user);
					//create new user
					$new_user = absint(wp_insert_user($userdata));
					//if user created succesfully - log in and reload
					if( $new_user > 0 ){
						wp_set_auth_cookie($existing_user, true, false);
				    	wp_redirect(wp_get_referer());
				    	exit();
					} else {
						wp_die('Facebook Connect: Error creating new user!');
					}
				}	    	
	    	}
		}
    }
}
?>