<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package WordPress
*/

// Support for:
// 1. `WP_DEVELOP_DIR` environment variable
// 2. Plugin installed inside of WordPress.org developer checkout
// 3. Tests checked out to /tmp
if ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( file_exists( '../../../../tests/phpunit/includes/bootstrap.php' ) ) {
	$test_root = '../../../../tests/phpunit';
} elseif ( file_exists( '/tmp/wordpress-tests-lib/includes/bootstrap.php' ) ) {
	$test_root = '/tmp/wordpress-tests-lib';
}

require $test_root . '/includes/functions.php';

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

// Load Tachyon.
tests_add_filter( 'muplugins_loaded', function() {
	require_once dirname( __DIR__ ) . '/tachyon.php';
} );

// Always run Tachyon filters.
tests_add_filter( 'tachyon_disable_in_admin', '__return_false' );
tests_add_filter( 'tachyon_override_image_downsize', '__return_false', 999999 + 1 );



require $test_root . '/includes/bootstrap.php';
