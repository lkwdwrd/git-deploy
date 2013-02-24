<?php
require_once 'deploy-config.php';
/**
 * Deploys GitHub git repos
 */
class GitHub_Deploy extends Deploy {
	/**
	 * Decodes and validates the data from github and calls the 
	 * doploy contructor to deoploy the new code.
	 *
	 * @param 	string 	$payload 	The JSON encoded payload data.
	 */
	function __construct( $payload ){
		$payload = json_decode( $_POST['payload'] );
		$name = $payload->repository->name;
		error_log( var_export( $payload->ref , true ) );
		//$this->log( $payload['commits'][0]['branch'] );
		//if ( isset( parent::$repos[ $name ] ) && parent::$repos[ $name ]['branch'] === $payload['commits'][0]['branch'] ){
			//$data = parent::$repos[ $name ];
			//$data['commit'] = $payload['commits'][0]['node'];
			//parent::__construct( $data );
		//}
	}
}
// Checks for payload data, and if present, starts the deploy attempt.
if( isset( $_POST['payload'] ) ) {
	new GitHub_Deploy( $_POST['payload'] );
}