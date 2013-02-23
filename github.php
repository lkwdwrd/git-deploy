<?php
require_once 'class.deploy.php';

class GitHub_Deploy extends Deploy {
	function __construct(){
		
	}
}
new GitHub_Deploy

// This is just an example
	/*$postdata = json_decode( stripslashes( $_POST['payload'] ), true );
	if ( 'master' === $postdata['commits'][0]['branch'] )
		$deploy = new Deploy( '/home2/woodwas4/public_html/ashwoodward/wp-content/' );
		*/