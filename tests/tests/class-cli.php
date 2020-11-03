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
class Tests_CLI extends WP_UnitTestCase {

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
	 * tachyon-1280x719.jpg: 1280x719
	 * Photo by Digital Buggu from Pexels
	 * @link https://www.pexels.com/photo/0-7-rpm-171195/
	 */
	static public function wpSetUpBeforeClass( $factory ) {
		global $_wp_additional_image_sizes;
		self::$wp_additional_image_sizes = $_wp_additional_image_sizes;

		// Ensure pre WP 5.3.1 behaviour with image file having dimensions in file name.
		add_filter( 'wp_unique_filename', __NAMESPACE__ . '\\Tests_CLI::unique_filename_override' );

		self::$attachment_ids['tachyon-1280x719'] = $factory->attachment->create_upload_object(
			realpath( __DIR__ . '/../data/tachyon-1280x719.jpg')
		);

		remove_filter( 'wp_unique_filename', __NAMESPACE__ . '\\Tests_CLI::unique_filename_override' );
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

	/**
	 * Prevents WP from fixing the file name during upload.
	 *
	 * This occurs if the file name contains dimensions as a suffix.
	 * This is to help test for backwards compat with WP 5.3.0 and earlier.
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function unique_filename_override( $filename ) {
		if ( strpos( $filename, 'tachyon-1280x719' ) === false ) {
			return $filename;
		}

		return str_replace( '-1.jpg', '.jpg', $filename );
	}

	public function test_file_renaming() {
		$attachment_id = self::$attachment_ids['tachyon-1280x719'];

		// Get the file path.
		$file = get_attached_file( $attachment_id );
		$thumb_file = dirname( $file ) . '/' . basename( $file, '.jpg' ) . '-150x150.jpg';

		// Confirm original file name.
		$this->assertEquals( 'tachyon-1280x719.jpg', basename( $file ) );
		// Confirm original exists.
		$this->assertTrue( file_exists( $file ), "File $file exists" );
		// Confirm a thumbnail exists.
		$this->assertTrue( file_exists( $thumb_file ), "Thumbnail $thumb_file exists" );

		// Rename the attachment.
		$result = Tachyon::_rename_file( $attachment_id );
		$this->assertTrue( $result, 'Attachment renamed successfully' );

		$new_file = get_attached_file( $attachment_id );

		// Confirm new file name.
		$this->assertEquals( 'tachyon-1.jpg', basename( $new_file ) );
		// Confirm old original has been removed.
		$this->assertFalse( file_exists( $file ), "File $file deleted" );
		// Confirm old thumbnail has been removed.
		$this->assertFalse( file_exists( $thumb_file ), "Thumbnail $file deleted" );
	}
}
