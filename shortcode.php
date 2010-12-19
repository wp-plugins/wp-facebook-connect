<?php
//shottcode function
function fb_login($atts){
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
?>