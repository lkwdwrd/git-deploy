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

		if (!isset(parent::$repos[$name])) {
		    $this->log(sprintf('Unknown repository: \'%s\'', $name));
		    return;
		}

		$multipleBranches = false;
		$branch = basename( $payload->ref );

		$this->log(sprintf('[REPOSITORY]: %s. [BRANCH]: %s.', $name, $branch));

		if (isset(parent::$repos[$name][$branch])) {
		    $multipleBranches = is_array(parent::$repos[$name][$branch]);
		}

		if ((!$multipleBranches && parent::$repos[$name]['branch'] !== $branch) || ($multipleBranches && !isset(parent::$repos[$name][$branch]))) {
		    $this->log(sprintf('Unknown branch \'%s\' of repository \'%s\'', $branch, $name));
		    return;
		}

		$data = parent::$repos[$name];

		if ($multipleBranches) {
		    $data = parent::$repos[$name][$branch];
		}

		$data['commit'] = substr( $payload->commits[0]->id, 0, 12 );
		$data['branch'] = $branch;

		parent::__construct( $name, $data, $_payload, $headers );
	}
}

// Starts the deploy attempt.
new GitHub_Deploy( $payload, getallheaders() );
