<?php
function uninstall_facebook_connect(){
	delete_option('fbconnect_api_id');
	delete_option('fbconnect_secret');
}
?>