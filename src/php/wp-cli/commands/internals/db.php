<?php

WP_CLI::add_command( 'db', 'DB_Command' );

/**
 * Implement db command
 *
 * @package wp-cli
 * @subpackage commands/internals
 **/
class DB_Command extends WP_CLI_Command {

	protected $default_subcommand = 'cli';

	protected $aliases = array( 'dump' => 'export' );

	/**
	 * Creates the database specified in the wp-config.php file.
	 */
	function create() {
		WP_CLI::launch( self::create_cmd(
			'mysql --host=%s --user=%s --password=%s --execute=%s',
			DB_HOST, DB_USER, DB_PASSWORD, 'CREATE DATABASE ' . DB_NAME
		) );

		WP_CLI::success( "Database created." );
	}

	/**
	 * Deletes the database specified in the wp-config.php file.
	 */
	function drop( $args, $assoc_args ) {
		if ( !isset( $assoc_args['yes'] ) ) {
			WP_CLI::out( "Are you sure you want to drop the database? [y/n] " );

			$answer = trim( fgets( STDIN ) );

			if ( 'y' != $answer )
				return;
		}

		WP_CLI::launch( self::create_cmd(
			'mysql --host=%s --user=%s --password=%s --execute=%s',
			DB_HOST, DB_USER, DB_PASSWORD, 'DROP DATABASE ' . DB_NAME
		) );

		WP_CLI::success( "Database dropped." );
	}

	/**
	 * Removes all tables from the database.
	 */
	function reset( $args, $assoc_args ) {
		if ( !isset( $assoc_args['yes'] ) ) {
			WP_CLI::out( "Are you sure you want to reset the database? [y/n] " );

			$answer = trim( fgets( STDIN ) );

			if ( 'y' != $answer )
				return;
		}

		WP_CLI::launch( self::create_cmd(
			'mysql --host=%s --user=%s --password=%s --execute=%s',
			DB_HOST, DB_USER, DB_PASSWORD, 'DROP DATABASE IF EXISTS ' . DB_NAME
		) );

		WP_CLI::launch( self::create_cmd(
			'mysql --host=%s --user=%s --password=%s --execute=%s',
			DB_HOST, DB_USER, DB_PASSWORD, 'CREATE DATABASE ' . DB_NAME
		) );

		WP_CLI::success( "Database reset." );
	}

	/**
	 * Optimizes the database specified in the wp-config.php file.
	 */
	function optimize() {
		WP_CLI::launch( self::create_cmd(
			'mysqlcheck --optimize --host=%s --user=%s --password=%s %s',
			DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
		) );

		WP_CLI::success( "Database optimized." );
	}

	/**
	 * Repairs the database specified in the wp-config.php file.
	 */
	function repair() {
		WP_CLI::launch( self::create_cmd(
			'mysqlcheck --repair --host=%s --user=%s --password=%s %s',
			DB_HOST, DB_USER, DB_PASSWORD, DB_NAME
		) );

		WP_CLI::success( "Database repaired." );
	}

	/**
	 * Print a string for connecting to the DB.
	 */
	function connect() {
		WP_CLI::line( $this->connect_string() );
	}

	/**
	 * Open a SQL command-line interface using WordPress's credentials.
	 */
	function cli() {
		WP_CLI::launch( $this->connect_string() );
	}

	/**
	 * Execute a query against the site database.
	 */
	function query( $args, $assoc_args ) {
		if ( empty( $args ) ) {
			WP_CLI::line( "usage: wp db query <SQL>" );
			exit;
		}

		$query = $args[0];

		$command = $this->connect_string() . self::create_cmd( ' --execute=%s', $query );

		WP_CLI::launch( $command );
	}

	/**
	 * Exports the WordPress DB as SQL using mysqldump.
	 */
	function export( $args, $assoc_args ) {
		$result_file = $this->get_file_name( $args );

		$command = self::create_cmd( 'mysqldump %s --user=%s --password=%s --host=%s --result-file %s',
			DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, $result_file );

		WP_CLI::launch( $command );

		WP_CLI::success( sprintf( 'Exported to %s', $result_file ) );
	}

	/**
	 * Imports a database from a file.
	 */
	function import( $args, $assoc_args ) {
		$result_file = $this->get_file_name( $args );

		$command = self::create_cmd(
			'mysql %s --user=%s --password=%s --host=%s < %s',
			DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, $result_file );

		WP_CLI::launch( $command );

		WP_CLI::success( sprintf( 'Imported from %s', $result_file ) );
	}

	/**
	 * Return a string for connecting to the DB.
	 */
	private function connect_string() {
		return self::create_cmd( 'mysql --host=%s --user=%s --password=%s --database=%s',
			DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
	}

	private function get_file_name( $args ) {
		if ( empty( $args ) )
			return sprintf( '%s.sql', DB_NAME );

		return $args[0];
	}

	/**
	 * Given a formatted string and an arbitrary number of arguments,
	 * returns the final command, with the parameters escaped
	 */
	private static function create_cmd( $cmd ) {
		$args = func_get_args();

		$cmd = array_shift( $args );

		return vsprintf( $cmd, array_map( 'escapeshellarg', $args ) );
	}
}
