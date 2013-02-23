<?php

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
	private static $_log_path = __DIR__;

	/**
	 * The timestamp format used for logging.
	 * 
	 * @link    http://www.php.net/manual/en/function.date.php
	 */
	private static $_date_format = 'Y-m-d H:i:sP';
	
	/**
	 * Registers available repos for deployement
	 *
	 * @param array $repo The repo information and the path information for deployment
	 * @return bool True on success, false on failure.
	 */
	public static function register_repo( $repo ) {
		if ( ! is_array( $repo ) )
			return false;
		
		$required_keys = array( 'name', 'path' );
		foreach ( $required_keys as $key ) {
			if ( ! array_key_exists( $key, $repo ) )
				return false;
		}

		$defaults = array(
			'branch' => 'origin',
			'remote' => 'master',
		);
		$repo = array_merge( $defaults, $repo );

		self::$repos[ $repo['name'] ] = $repo;
	}

	/**
	 * Allows alternate log locations and date formats
	 *
	 * @return void.
	 */
	public static function set( $var, $value ) {
		if ( ( 'log_path' === $var || 'log_name' === $var || 'date_format' === $var ) && is_string( $value ) )
			self::${'_'.$var} = $value;
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
	 * @param 	array 	$repo 	Directory where your website is located
	 */
	protected function __construct( $repo ) {
		$this->_path = realpath( $repo['path'] ) . DIRECTORY_SEPARATOR;

		$available_options = array( 'name', 'branch', 'remote', 'commit', 'post_deploy' );

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
	protected function log( $message, $type = 'INFO' ){
		if ( self::$_log_name ){
			// Set the name of the log file
			$filename = self::$_log_path . '/' . rtrim( self::$_log_name, '/' );

			if ( ! file_exists( $filename ) ){
				// Create the log file
				file_put_contents( $filename, '' );

				// Allow anyone to write to log files
				chmod( $filename, 0666 );
			}

			// Write the message into the log file
			// Format: time --- type: message
			file_put_contents( $filename, date( $this->_date_format ) . ' --- ' . $type . ': ' . $message . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	* Executes the necessary commands to deploy the code.
	*/
	private function execute(){
		try{
			$this->log( 'here' );
			// Make sure we're in the right directory
			chdir( $this->_path);

			// Discard any changes to tracked files since our last deploy
			exec( 'git reset --hard HEAD', $output );

			// Update the local repository
			exec( 'git pull ' . $this->_remote . ' ' . $this->_branch, $output );

			// Secure the .git directory
			echo exec( 'chmod -R og-rx .git' );

			if ( is_callable( $this->_post_deploy ) )
				call_user_func( $this->_post_deploy );

			$this->log( '[SHA: ' . $this->_commit . '] Deployment of ' . $this->_name . ' from branch ' . $this->_branch . ' successful' );
		} catch ( Exception $e ){
			$this->log( $e, 'ERROR' );
		}
	}
}
