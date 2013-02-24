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
		$branch = basename( $payload->ref );
		$commit = substr( $payload->commits[0]->id, 0, 12 );
		if ( isset( parent::$repos[ $name ] ) && parent::$repos[ $name ]['branch'] === $branch ){
			$data = parent::$repos[ $name ];
			$data['commit'] = $commit;
			parent::__construct( $data );
		}
	}
}
// Checks for payload data, and if present, starts the deploy attempt.
if( isset( $_POST['payload'] ) ) {
	new GitHub_Deploy( $_POST['payload'] );
}