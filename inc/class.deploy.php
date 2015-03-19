<?php
// Causes the script to die if we are not using an actual endpoint to access it.
if( ! defined( 'ACTIVE_DEPLOY_ENDPOINT' ) || true !== ACTIVE_DEPLOY_ENDPOINT )
	die( '<h1>No Access</h1><p>An endpoint needs to be defined to use this file.</p>' );
/**
 * The main Deploy class. This is set up for GIT repos.
 *
 * To create an end point, extend this abstract class and in the new class' constructor
 * parse whatever post payload and pass it to parent::construct(). The data passed should
 * be an array that is in the following order (note this is right in line with how the
 * config arrays are put together).
 * 'repo name' => array(
 * 		'name'   => 'repo name', // Required
 * 		'path' 	 => '/path/to/local/repo/' // Required
 * 		'branch' => 'the_desired_deploy_branch', // Required
 * 		'commit' => 'the SHA of the commit', // Optional. The SHA is only used in logging.
 *		'remote' => 'git_remote_repo', // Optional. Defaults to 'origin'
 * 		'post_deploy' => 'callback' // Optional callback function for whatever.
 * )
 *
 * The parent constructor will take care of the rest of the setup and deploy.
 *
 * @todo move the logging functions to a separate class to separate the functionality.
 */

abstract class Deploy {
	/**
	 * Registered deploy repos
	 */
	protected static $repos = array();

	/**
	 * The name of the file that will be used for logging deployments. Set 
	 * to false to disable logging.
	 */
	private static $_log_name = 'deployments.log';

	/**
	 * The path to where we wish to store our log file.
	 */
	private static $_log_path = DEPLOY_LOG_DIR;

	/**
	 * The timestamp format used for logging.
	 * 
	 * @link    http://www.php.net/manual/en/function.date.php
	 */
	private static $_date_format = 'Y-m-d H:i:sP';
	
	/**
	 * Registers available repos for deployment
	 *
	 * @param array $repo The repo information and the path information for deployment
	 * @return bool True on success, false on failure.
	 */
	public static function register_repo( $name, $repo ) {
		if ( ! is_string( $name ) )
			return false;

		if ( ! is_array( $repo ) )
			return false;
		
		$required_keys = array( 'path', 'branch' );
		foreach ( $required_keys as $key ) {
			if ( ! array_key_exists( $key, $repo ) )
				return false;
		}

		$defaults = array(
			'remote'      => 'origin',
			'post_deploy' => '',
			'commit'      => '',
		);
		$repo = array_merge( $defaults, $repo );

		self::$repos[ $name ] = $repo;
	}

	/**
	 * Allows alternate log locations and date formats
	 *
	 * @return void.
	 */
	public static function set( $var, $value ) {
		if ( ( 'log_name' === $var || 'date_format' === $var ) && is_string( $value ) )
			self::${'_'.$var} = $value;
	}

	/**
	 * Validates the payload data
	 *
	 * @param 	string 	$payload 	The JSON encoded payload data.
	 * @param 	array 	$headers 	Array with all the HTTP headers from the current request.
	 */
	protected function validate_payload( $payload, $headers, $secret ) {
		if ( empty( $secret ) )
			return true;

		$signature = $headers['X-Hub-Signature'];

		if ( ! isset( $signature ) )
			return false;

		list( $algo, $hash ) = explode( '=', $signature, 2 );
		$payload_hash = hash_hmac( $algo, $payload, $secret );

		if ( $hash !== $payload_hash )
			return false;

		return true;
	}

	/**
	 * Whether or not we are ready to deploy
	 */
	private $_deploy_ready;

	/**
	 * The name of the repo we are attempting deployment for.
	 */
	private $_name;

	/**
	 * The name of the branch to pull from.
	 */
	private $_branch;

	/**
	 * The name of the remote to pull from.
	 */
	private $_remote;

	/**
	 * The path to where your website and git repository are located, can be 
	 * a relative or absolute path
	 */
	private $_path;

	/**
	 * A callback function to call after the deploy has finished.
	 */
	private $_post_deploy;

	/**
	 * The commit that we are attempting to deploy
	 */
	private $_commit;

	/**
	 * Sets up the repo information.
	 * 
	 * @param 	array 	$repo 	The repository info. See class block for docs.
	 */
	protected function __construct( $name, $repo, $payload, $headers ) {
		if ( ! $this->validate_payload( $payload, $headers, $repo['secret'] ) ) {
			$this->log( '[SHA: ' . $repo['commit'] . '] Deployment of ' . $name . ' from branch ' . $repo['branch'] . ' failed. Signature mismatch.', 'ERROR' );
			echo( '[SHA: ' . $repo['commit'] . '] Deployment of ' . $name . ' from branch ' . $repo['branch'] . ' failed. Signature mismatch.' );
			return;
		}

		$this->_path = realpath( $repo['path'] ) . DIRECTORY_SEPARATOR;

		$this->_name = $name;

		$available_options = array( 'branch', 'remote', 'commit', 'post_deploy' );

		foreach ( $repo as $option => $value ){
			if ( in_array( $option, $available_options ) ){
				$this->{'_'.$option} = $value;
			}
		}

		$this->execute();
	}

	/**
	 * Writes a message to the log file.
	 * 
	 * @param 	string 	$message 	The message to write
	 * @param 	string 	$type 		The type of log message (e.g. INFO, DEBUG, ERROR, etc.)
	 */
	protected function log( $message, $type = 'INFO' ) {
		if ( self::$_log_name ) {
			// Set the name of the log file
			$filename = self::$_log_path . '/' . rtrim( self::$_log_name, '/' );

			if ( ! file_exists( $filename ) ) {
				// Create the log file
				file_put_contents( $filename, '' );

				// Allow anyone to write to log files
				chmod( $filename, 0666 );
			}

			// Write the message into the log file
			// Format: time --- type: message
			file_put_contents( $filename, date( self::$_date_format ) . ' --- ' . $type . ': ' . $message . PHP_EOL, FILE_APPEND );
		}
	}

	/**
	* Executes the necessary commands to deploy the code.
	*/
	private function execute() {
		try {
			// Make sure we're in the right directory
			chdir( $this->_path );

			// Discard any changes to tracked files since our last deploy
			exec( 'git reset --hard HEAD', $output );

			// Update the local repository
			exec( 'git pull ' . $this->_remote . ' ' . $this->_branch, $output );

			// Secure the .git directory
			echo exec( 'chmod -R og-rx .git' );

			if ( is_callable( $this->_post_deploy ) )
				call_user_func( $this->_post_deploy );

			$this->log( '[SHA: ' . $this->_commit . '] Deployment of ' . $this->_name . ' from branch ' . $this->_branch . ' successful' );
			echo( '[SHA: ' . $this->_commit . '] Deployment of ' . $this->_name . ' from branch ' . $this->_branch . ' successful' );
		} catch ( Exception $e ) {
			$this->log( $e, 'ERROR' );
		}
	}
}

// Registers all of our repos with the Deploy class
foreach ( $repos as $name => $repo )
	Deploy::register_repo( $name, $repo );
