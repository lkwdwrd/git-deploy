<?php
// Make sure we have a payload, stop if we do not.
if( ! isset( $_POST['payload'] ) )
	die( '<h1>No payload present</h1><p>A BitBucket POST payload is required to deploy from this script.</p>' );

/**
 * Tell the script this is an active end point.
 */
define( 'ACTIVE_DEPLOY_ENDPOINT', true );

require_once 'deploy-config.php';
/**
 * Deploys BitBucket git repos
 */
class BitBucket_Deploy extends Deploy {
	/**
	 * Decodes and validates the data from bitbucket and calls the
	 * deploy constructor to deploy the new code.
	 *
	 * @param 	string 	$payload 	The JSON encoded payload data.
	 */
	function __construct( $payload ) {
		$payload = json_decode( stripslashes( $_POST['payload'] ), true );
		$name = $payload['repository']['name'];

		if (!isset(parent::$repos[$name])) {
		    $this->log(sprintf('Unknown repository: \'%s\'', $name));
		    return;
		}

		$multipleBranches = false;
		$branch = $payload['commits'][0]['branch'];

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

		$data['commit'] = $payload['commits'][0]['node'];
		$data['branch'] = $branch;

		parent::__construct($name, $data);
	}
}
// Start the deploy attempt.
new BitBucket_Deploy( $_POST['payload'] );
