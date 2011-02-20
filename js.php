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