<?php
// Make sure we have a payload, stop if we do not.
$payload = file_get_contents( 'php://input' );
if ( empty( $payload ) )
	die ( '<h1>No payload present</h1><p>A GitHub POST payload is required to deploy from this script.</p>' );

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
	 * deploy constructor to deploy the new code.
	 *
	 * @param   string  $_payload   The JSON encoded payload data.
	 * @param   array   $headers    Array with all the HTTP headers from the current request.
	 */
	function __construct( $_payload, $headers ) {
		$payload = json_decode( $_payload );
		$name = $payload->repository->name;
		$branch = basename( $payload->ref );
		$commit = substr( $payload->commits[0]->id, 0, 12 );
		if ( isset( parent::$repos[ $name ] ) && parent::$repos[ $name ]['branch'] === $branch ) {
			$data = parent::$repos[ $name ];
			$data['commit'] = $commit;
			parent::__construct( $name, $data, $_payload, $headers );
		}
	}
}

// Starts the deploy attempt.
new GitHub_Deploy( $payload, getallheaders() );
