<?php
namespace HM\Tachyon\Tests;

use ReflectionClass;
use Tachyon;
use WP_UnitTestCase;

/**
 * Ensure the tachyon plugin resizes correctly.
 *
 * @ticket 48
 */
class Tests_Resizing extends WP_UnitTestCase {

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

		self::setup_custom_sizes();

		self::$attachment_ids['tachyon'] = $factory->attachment->create_upload_object(
			realpath( __DIR__ . '/../data/tachyon.jpg')
		);

		self::$attachment_ids['tachyon-large'] = $factory->attachment->create_upload_object(
			realpath( __DIR__ . '/../data/tachyon-large.jpg')
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

	function setUp() {
		parent::setUp();
		self::setup_custom_sizes();
	}

	/**
	 * Set up custom image sizes.
	 *
	 * These are done in both the class and per test set up as the sizes are
	 * reset in the WP test suite's tearDown.
	 *
	 * Oversize, too tall/wide refer are references against the smaller image's
	 * size, the larger image is always larger than the dimensions listed.
	 */
	static function setup_custom_sizes() {
		add_image_size(
			'oversize2d-early',
			2500,
			1500
		);

		add_image_size(
			'too-wide-shorter-crop',
			1500,
			500,
			true
		);

		add_image_size(
			'too-tall-narrower-crop',
			1000,
			1000,
			true
		);

		if ( ! empty( self::$attachment_ids ) ) {
			return;
		}

		add_image_size(
			'oversize2d-late',
			2000,
			1000
		);
	}

	/**
	 * Test URLs are parsed correctly.
	 *
	 * @dataProvider data_filtered_url
	 */
	function test_filtered_url( $file, $size, $valid_urls, $expected_size ) {
		$valid_urls = (array) $valid_urls;
		$actual_src = wp_get_attachment_image_src( self::$attachment_ids[ $file ], $size );
		$actual_url = $actual_src[0];

		$this->assertContains( $actual_url, $valid_urls, "The resized image is expected to be {$actual_src[1]}x{$actual_src[2]}" );
		$this->assertSame( $expected_size[0], $actual_src[1] );
		$this->assertSame( $expected_size[1], $actual_src[2] );
	}

	/**
	 * Data provider for `test_filtered_url()`.
	 *
	 * Only the filename and querystring are stored as the
	 *
	 * return array[] {
	 *     $file       string The basename of the uploaded file to tests against.
	 *     $size       string The image size requested.
	 *     $valid_urls array  Valid Tachyon URLs for resizing.
	 * }
	 */
	function data_filtered_url() {
		return [
			[
				'tachyon',
				'thumb',
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
				[ 150, 150 ],
			],
			[
				'tachyon',
				'thumbnail',
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
				[ 150, 150 ],
			],
			[
				'tachyon',
				'medium',
				[
					'http://tachy.on/u/tachyon.jpg?fit=300,169',
					'http://tachy.on/u/tachyon.jpg?resize=300,169',
					'http://tachy.on/u/tachyon.jpg?fit=300,300',
				],
				[ 300, 169 ],
			],
			[
				'tachyon',
				'medium_large',
				[
					'http://tachy.on/u/tachyon.jpg?fit=768,431',
					'http://tachy.on/u/tachyon.jpg?resize=768,431',
					'http://tachy.on/u/tachyon.jpg?w=768',
					'http://tachy.on/u/tachyon.jpg?w=768&h=431',
				],
				[ 768, 431 ],
			],
			[
				'tachyon',
				'large',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1024,575',
					'http://tachy.on/u/tachyon.jpg?fit=1024,719',
					'http://tachy.on/u/tachyon.jpg?resize=1024,575',
					'http://tachy.on/u/tachyon.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon.jpg?w=1024&h=575',
				],
				[ 1024, 575 ],
			],
			[
				'tachyon',
				'full',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
				[ 1280, 719 ],
			],
			[
				'tachyon',
				'oversize2d-early',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
				[ 1280, 719 ],
			],
			[
				'tachyon',
				'oversize2d-late',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
				[ 1280, 719 ],
			],
			[
				'tachyon',
				'too-wide-shorter-crop',
				[
					'http://tachy.on/u/tachyon.jpg?resize=1280,500',
				],
				[ 1280, 500 ],
			],
			[
				'tachyon',
				'too-tall-narrower-crop',
				[
					'http://tachy.on/u/tachyon.jpg?resize=1000,719',
				],
				[ 1000, 719 ],
			],
			[
				'tachyon',
				[ 1024, 1024 ], // Manual size, matches existing crop.
				[
					'http://tachy.on/u/tachyon.jpg?fit=1024,575',
					'http://tachy.on/u/tachyon.jpg?resize=1024,575',
					'http://tachy.on/u/tachyon.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon.jpg?w=1024&h=575',
				],
				[ 1024, 575 ],
			],
			[
				'tachyon',
				[ 500, 300 ], // Manual size, new size, smaller than image, width limited.
				[
					'http://tachy.on/u/tachyon.jpg?fit=500,281',
					'http://tachy.on/u/tachyon.jpg?resize=500,281',
					'http://tachy.on/u/tachyon.jpg?fit=500,300',
					'http://tachy.on/u/tachyon.jpg?w=500&h=281',
					'http://tachy.on/u/tachyon.jpg?w=500&h=300',
				],
				[ 500, 281 ],
			],
			[
				'tachyon',
				[ 500, 30 ], // Manual size, new size, smaller than image, height limited.
				[
					'http://tachy.on/u/tachyon.jpg?fit=53,30',
					'http://tachy.on/u/tachyon.jpg?resize=53,30',
					'http://tachy.on/u/tachyon.jpg?fit=500,30',
					'http://tachy.on/u/tachyon.jpg?w=500&h=30',
					'http://tachy.on/u/tachyon.jpg?w=53&h=30',
				],
				[ 53, 30 ],
			],
			[
				'tachyon',
				[ 5000, 3000 ], // Manual size, new size, large than image, would be width limited.
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
				[ 1280, 719 ],
			],
			[
				'tachyon',
				[ 4000, 2000 ], // Manual size, new size, large than image, would be height limited.
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
				[ 1280, 719 ],
			],
			[
				'tachyon-large',
				'thumb',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=150,150',
				],
				[ 150, 150 ],
			],
			[
				'tachyon-large',
				'thumbnail',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=150,150',
				],
				[ 150, 150 ],
			],
			[
				'tachyon-large',
				'medium',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=300,169',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=300,169',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=300,300',
				],
				[ 300, 169 ],
			],
			[
				'tachyon-large',
				'medium_large',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=768,432',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=768,432',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=768',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=768&h=432',
				],
				[ 768, 432 ],
			],
			[
				'tachyon-large',
				'large',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1024,576',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1024,576',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1024&h=576',
				],
				[ 1024, 576 ],
			],
			[
				'tachyon-large',
				'full',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2560,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=2560,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2560',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2560&h=1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg',
				],
				[ 2560, 1440 ],
			],
			[
				'tachyon-large',
				'oversize2d-early',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2500,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2500,1406',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=2500,1406',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2500,1500',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2500',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2500&h=1500',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2500&h=1406',
				],
				[ 2500, 1406 ],
			],
			[
				'tachyon-large',
				'oversize2d-late',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1778,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1778,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2000,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1778',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1778&h=1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2000&h=1000',
				],
				[ 1778, 1000 ],
			],
			[
				'tachyon-large',
				'too-wide-shorter-crop',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1500,500',
				],
				[ 1500, 500 ],
			],
			[
				'tachyon-large',
				'too-tall-narrower-crop',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1000,1000',
				],
				[ 1000, 1000 ],
			],
		];
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
	 * Test image tags passed as part of the content.
	 *
	 * @dataProvider data_content_filtering
	 */
	function test_content_filtering( $file, $content, $valid_urls ) {
		$valid_urls = (array) $valid_urls;
		$attachment_id = self::$attachment_ids[ $file ];
		$content = str_replace( '%%ID%%', $attachment_id, $content );
		$content = str_replace( '%%BASE_URL%%', wp_upload_dir()['baseurl'], $content );

		$the_content = Tachyon::filter_the_content( $content );
		$actual_src = $this->get_src_from_html( $the_content );

		$this->assertContains( $actual_src, $valid_urls, 'The resized image is expected to be ' . implode( ' or ', $valid_urls ) );
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
			// Classic editor formatted image tags.
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-thumb" src="%%BASE_URL%%/tachyon-150x150.jpg" alt="" width="150" height="150" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
			],
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-medium" src="%%BASE_URL%%/tachyon-300x169.jpg" alt="" width="300" height="169" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=300,169',
					'http://tachy.on/u/tachyon.jpg?resize=300,169',
					'http://tachy.on/u/tachyon.jpg?fit=300,300',
				],
			],
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-large" src="%%BASE_URL%%/tachyon-1024x575.jpg" alt="" width="1024" height="575" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1024,575',
					'http://tachy.on/u/tachyon.jpg?resize=1024,575',
					'http://tachy.on/u/tachyon.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon.jpg?w=1024&h=1024',
				],
			],
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-full" src="%%BASE_URL%%/tachyon.jpg" alt="" width="1280" height="719" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
			],
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-too-wide-shorter-crop" src="%%BASE_URL%%/tachyon-1280x500.jpg" alt="" width="1280" height="500" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=1280,500',
				],
			],
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-too-tall-narrower-crop" src="%%BASE_URL%%/tachyon-1000x719.jpg" alt="" width="1000" height="719" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=1000,719',
				],
			],
			[
				'tachyon',
				'<p><img class="alignnone wp-image-%%ID%% size-oversize2d-late" src="%%BASE_URL%%/tachyon.jpg" alt="" width="1280" height="719" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-thumb" src="%%BASE_URL%%/tachyon-large-scaled-150x150.jpg" alt="" width="150" height="150" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=150,150',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-medium" src="%%BASE_URL%%/tachyon-large-scaled-300x169.jpg" alt="" width="300" height="169" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=300,169',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=300,169',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=300,300',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-large" src="%%BASE_URL%%/tachyon-large-scaled-1024x576.jpg" alt="" width="1024" height="576" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1024,576',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1024,576',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1024&h=576',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-full" src="%%BASE_URL%%/tachyon-large-scaled.jpg" alt="" width="2560" height="1440" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2560,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=2560,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2560',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2560&h=1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-oversize2d-late" src="%%BASE_URL%%/tachyon-large-scaled-1778x1000.jpg" alt="" width="1778" height="1000" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1778,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1778,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2000,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1778',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1778&h=1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2000&h=1000',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-too-wide-shorter-crop" src="%%BASE_URL%%/tachyon-large-scaled-1500x500.jpg" alt="" width="1500" height="500" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1500,500',
				],
			],
			[
				'tachyon-large',
				'<p><img class="alignnone wp-image-%%ID%% size-too-tall-narrower-crop" src="%%BASE_URL%%/tachyon-large-scaled-1000x1000.jpg" alt="" width="1000" height="1000" /></p>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1000,1000',
				],
			],
			// Block editor formatted image tags.
			[
				'tachyon',
				'<figure class="wp-block-image size-thumbnail"><img src="%%BASE_URL%%/tachyon-150x150.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
			],
			[
				'tachyon',
				'<figure class="wp-block-image size-medium"><img src="%%BASE_URL%%/tachyon-300x169.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=300,169',
					'http://tachy.on/u/tachyon.jpg?resize=300,169',
					'http://tachy.on/u/tachyon.jpg?fit=300,300',
				],
			],
			[
				'tachyon',
				'<figure class="wp-block-image size-large"><img src="%%BASE_URL%%/tachyon-1024x575.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1024,575',
					'http://tachy.on/u/tachyon.jpg?resize=1024,575',
					'http://tachy.on/u/tachyon.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon.jpg?w=1024&h=1024',
				],
			],
			[
				'tachyon',
				'<figure class="wp-block-image size-full"><img src="%%BASE_URL%%/tachyon.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
			],
			[
				'tachyon',
				'<figure class="wp-block-image size-too-wide-shorter-crop"><img src="%%BASE_URL%%/tachyon-1280x500.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=1280,500',
				],
			],
			[
				'tachyon',
				'<figure class="wp-block-image size-too-tall-narrower-crop"><img src="%%BASE_URL%%/tachyon-1000x719.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=1000,719',
				],
			],
			[
				'tachyon',
				'<figure class="wp-block-image size-oversize2d-late"><img src="%%BASE_URL%%/tachyon.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon.jpg?fit=1280,719',
					'http://tachy.on/u/tachyon.jpg?resize=1280,719',
					'http://tachy.on/u/tachyon.jpg?w=1280',
					'http://tachy.on/u/tachyon.jpg?w=1280&h=719',
					'http://tachy.on/u/tachyon.jpg',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-thumbnail"><img src="%%BASE_URL%%/tachyon-large-scaled-150x150.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=150,150',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-medium"><img src="%%BASE_URL%%/tachyon-large-scaled-300x169.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=300,169',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=300,169',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=300,300',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-large"><img src="%%BASE_URL%%/tachyon-large-scaled-1024x576.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1024,576',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1024,576',
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1024,1024',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1024&h=1024',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-full"><img src="%%BASE_URL%%/tachyon-large-scaled.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=2560,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=2560,1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2560',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=2560&h=1440',
					'http://tachy.on/u/tachyon-large-scaled.jpg',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-too-wide-shorter-crop"><img src="%%BASE_URL%%/tachyon-large-scaled-1500x500.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1500,500',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-too-tall-narrower-crop"><img src="%%BASE_URL%%/tachyon-large-scaled-1000x1000.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1000,1000',
				],
			],
			[
				'tachyon-large',
				'<figure class="wp-block-image size-oversize2d-late"><img src="%%BASE_URL%%/tachyon-large-scaled-1778x1000.jpg" alt="" class="wp-image-%%ID%%"></figure>',
				[
					'http://tachy.on/u/tachyon-large-scaled.jpg?fit=1778,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?resize=1778,1000',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1778',
					'http://tachy.on/u/tachyon-large-scaled.jpg?w=1778&h=1000',
				],
			],
			// Unknown attachement ID, unknown size, classic editor formatted image tags.
			[
				'tachyon',
				'<p><img class="alignnone" src="%%BASE_URL%%/tachyon-150x150.jpg" alt="" width="150" height="150" /></p>',
				[
					'http://tachy.on/u/tachyon.jpg?resize=150,150',
				],
			],
		];
	}
}
