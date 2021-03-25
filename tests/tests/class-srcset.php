<?php
namespace HM\Tachyon\Tests;

use ReflectionClass;
use Tachyon;
use WP_UnitTestCase;

/**
 * Ensure the tachyon plugin updates gallery and image links.
 *
 * @ticket 48
 */
class Tests_Srcset extends WP_UnitTestCase {

	/**
	 * @var int[] Attachment IDs
	 */
	static $attachment_ids;

	/**
	 * @var array[] Original array of image sizes.
	 */
	static $wp_additional_image_sizes;

	/**
	 * Set up attachments and posts require for testing.
	 *
	 * tachyon.jpg: 1280x719
	 * tachyon-large.jpg: 5312x2988
	 * Photo by Digital Buggu from Pexels
	 * @link https://www.pexels.com/photo/0-7-rpm-171195/
	 */
	static public function wpSetUpBeforeClass( $factory ) {
		global $_wp_additional_image_sizes;
		self::$wp_additional_image_sizes = $_wp_additional_image_sizes;

		self::$attachment_ids['tachyon'] = $factory->attachment->create_upload_object(
			realpath( __DIR__ . '/../data/tachyon.jpg')
		);
	}

	/**
	 * Runs the routine after all tests have been run.
	 *
	 * This deletes the files from the uploads directory
	 * to account for the test suite returning the posts
	 * table to the original state.
	 */
	public static function wpTearDownAfterClass() {
		global $_wp_additional_image_sizes;
		$_wp_additional_image_sizes = self::$wp_additional_image_sizes;

		$singleton = Tachyon::instance(); // Get Tachyon instance.
		$reflection = new ReflectionClass( $singleton );
		$instance = $reflection->getProperty( 'image_sizes' );
		$instance->setAccessible( true ); // Allow modification of image sizes.
		$instance->setValue( null, null ); // Reset image sizes for next tests.
		$instance->setAccessible( false ); // clean up.

		$uploads_dir = wp_upload_dir()['basedir'];

		$files = glob( $uploads_dir . '/*' );
		array_walk( $files, function ( $file ) {
			if ( is_file( $file ) ) {
				unlink($file);
			}
		} );
		rmdir( $uploads_dir );
	}

    function test_image_srcset_encoding() {
        $srcset = wp_get_attachment_image_srcset( self::$attachment_ids['tachyon'] );
        $expected = 'http://tachy.on/u/tachyon.jpg?w=1280 1280w, http://tachy.on/u/tachyon.jpg?resize=300%2C169 300w, http://tachy.on/u/tachyon.jpg?resize=1024%2C575 1024w, http://tachy.on/u/tachyon.jpg?resize=768%2C431 768w';
        $this->assertEquals( $expected, $srcset );
    }
}
