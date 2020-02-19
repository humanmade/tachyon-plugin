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
class Tests_Linking extends WP_UnitTestCase {

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

	/**
	 * Extract the first src attribute from the given HTML.
	 *
	 * There should only ever be one image in the content so the regex
	 * can be simplified to search for the source attribute only.
	 *
	 * @param $html string HTML containing an image tag.
	 * @return string The first `src` attribute within the first image tag.
	 */
	function get_src_from_html( $html ) {
		preg_match_all( '/src\s*=\s*[\'"]([^\'"]+)[\'"]/i' , $html, $matches, PREG_SET_ORDER );
		if ( empty( $matches[0][1] ) ) {
			return false;
		}

		return $matches[0][1];
	}

	/**
	 * Extract the first href attribute from the given HTML.
	 *
	 * There should only ever be one link in the content so the regex
	 * can be simplified to search for the href attribute only.
	 *
	 * @param $html string HTML containing an image tag.
	 * @return string The first `src` attribute within the first image tag.
	 */
	function get_href_from_html( $html ) {
		preg_match_all( '/href\s*=\s*[\'"]([^\'"]+)[\'"]/i' , $html, $matches, PREG_SET_ORDER );
		if ( empty( $matches[0][1] ) ) {
			return false;
		}

		return $matches[0][1];
	}

	/**
	 * Test image tags passed as part of the content.
	 *
	 * @dataProvider data_content_filtering
	 */
	function test_content_filtering( $file, $content, $valid_link_urls, $valid_src_urls ) {
		$valid_link_urls = (array) $valid_link_urls;
		$valid_src_urls = (array) $valid_src_urls;
		$attachment_id = self::$attachment_ids[ $file ];
		$content = str_replace( '%%ID%%', $attachment_id, $content );
		$content = str_replace( '%%BASE_URL%%', wp_upload_dir()['baseurl'], $content );

		$the_content = Tachyon::filter_the_content( $content );
		$actual_src = $this->get_src_from_html( $the_content );
		$actual_href = $this->get_href_from_html( $the_content );

		$this->assertContains( $actual_src, $valid_src_urls, 'The resized image is expected to be ' . implode( ' or ', $valid_src_urls ) );
		$this->assertContains( $actual_href, $valid_link_urls, 'The link is expected to be ' . implode( ' or ', $valid_link_urls ) );
	}

	/**
	 * Data provider for test_content_filtering.
	 *
	 * return array[] {
	 *     $file         string The basename of the uploaded file to tests against.
	 *     $content      string The content being filtered.
	 *                          `%%ID%%` is replaced with the attachment ID during the test.
	 *                          `%%BASE_URL%%` is replaced with base uploads directory during the test.
	 *     $valid_urls   array  Valid Tachyon URLs for resizing.
	 * }
	 */
	function data_content_filtering() {
		return [
			// Classic editor linked thumbnail.
			[
				'tachyon',
				'<p><a href="%%BASE_URL%%/tachyon.jpg"><img class="alignnone size-thumbnail wp-image-%%ID%%" src="%%BASE_URL%%/tachyon-150x150.jpg" alt="" width="150" height="150" /></a></p>',
				[
					'http://tachy.on/u/tachyon.jpg',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
				],
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
			],
			// Block editor linked thumbnail.
			[
				'tachyon',
				'<figure class="wp-block-image size-medium"><a href="%%BASE_URL%%/tachyon.jpg"><img src="%%BASE_URL%%/tachyon-150x150.jpg" alt="" class="wp-image-%%ID%%"/></a></figure>',
				[
					'http://tachy.on/u/tachyon.jpg',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
				],
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
			],
			// Block editor gallery.
			[
				'tachyon',
				'<figure class="wp-block-gallery columns-1 is-cropped"><ul class="blocks-gallery-grid"><li class="blocks-gallery-item"><figure><a href="%%BASE_URL%%/tachyon.jpg"><img src="%%BASE_URL%%/tachyon-1024x575.jpg" alt="" data-id="%%ID%%" data-full-url="%%BASE_URL%%/tachyon.jpg" data-link="http://milstead.local/2019/12/26/classic-test/tachyon/" class="wp-image-%%ID%%"/></a></figure></li></ul></figure>',
				[
					'http://tachy.on/u/tachyon.jpg',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
				],
				[
					'http://tachy.on/u/tachyon.jpg?resize=1024,575',
				],
			],
		];
	}
}
