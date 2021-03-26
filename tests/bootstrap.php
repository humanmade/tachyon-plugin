<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package WordPress
 * @subpackage JSON API
 *
 * phpcs:disable PSR1.Files.SideEffects
 */

require '/wp-phpunit/includes/functions.php';

/**
 * Load Tachyon.
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../tachyon.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Define a UR for Tachyon to use.
define( 'TACHYON_URL', 'http://tachy.on/u' );

// Prevent upload URLs being affected by the date on which tests run.
tests_add_filter( 'pre_option_uploads_use_yearmonth_folders', '__return_zero' );

/**
 * Filter the uploads directory to avoid file name clashes in
 * subsequent tests.
 */
tests_add_filter( 'upload_dir', function( $upload_dir ) {
	$dir = 'tachyon-test-suite';
	$upload_dir['basedir'] = preg_replace( '#/uploads(/|$)#', "/uploads/{$dir}\$1", $upload_dir['basedir'] );
	$upload_dir['path'] = preg_replace( '#/uploads(/|$)#', "/uploads/{$dir}\$1", $upload_dir['path'] );
	return $upload_dir;
} );

// Always run Tachyon filters.
tests_add_filter( 'tachyon_disable_in_admin', '__return_false' );
tests_add_filter( 'tachyon_override_image_downsize', '__return_false', 999999 + 1 );

require '/wp-phpunit/includes/bootstrap.php';
