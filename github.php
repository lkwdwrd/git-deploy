<?php
// Make sure we have a payload, stop if we do not.
if( ! isset( $_POST['payload'] ) )
	die( '<h1>No payload present</h1><p>A GitHub POST payload is required to deploy from this script.</p>' );

/**
 * Tell the script this is an active end point.
 */
define( 'ACTIVE_DEPLOY_ENDPOINT', true );

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
	function __construct( $payload ) {
		$payload = json_decode( $_POST['payload'] );
		$name = $payload->repository->name;
		$branch = basename( $payload->ref );
		$commit = substr( $payload->commits[0]->id, 0, 12 );
		if ( isset( parent::$repos[ $name ] ) && parent::$repos[ $name ]['branch'] === $branch ) {
			$data = parent::$repos[ $name ];
			$data['commit'] = $commit;
			parent::__construct( $name, $data );
		}
	}
}
// Starts the deploy attempt.
new GitHub_Deploy( $_POST['payload'] );