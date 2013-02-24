<?php
require_once 'deploy-config.php'
/**
 * Deploys BitBucket git repos
 */
class BitBucket_Deploy extends Deploy {
	/**
	 * Decodes and validates the data from bitbucket and calls the 
	 * doploy contructor to deoploy the new code.
	 *
	 * @param 	string 	$payload 	The JSON encoded payload data.
	 */
	function __construct( $payload ){
		$payload = json_decode( stripslashes( $_POST['payload'] ), true );
		$name = $payload['repository']['name'];
		$this->log( $payload['commits'][0]['branch'] );
		if ( isset( parent::$repos[ $name ] ) && parent::$repos[ $name ]['branch'] === $payload['commits'][0]['branch'] ){
			$data = parent::$repos[ $name ];
			$data['commit'] = $payload['commits'][0]['node'];
			parent::__construct( $data );
		}
	}
}

// Checks for payload data, and if present, starts the deploy attempt.
if( isset( $_POST['payload'] ) ) {
	new BitBucket_Deploy( $_POST['payload'] );
}