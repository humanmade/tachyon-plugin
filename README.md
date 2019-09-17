<table width="100%">
	<tr>
		<td align="left" width="70">
			<strong>Tachyon</strong><br />
			Faster than light image processing. Inspired / forked from Photon.
		</td>
		<td align="right" width="20%">
			<a href="https://travis-ci.org/humanmade/tachyon-plugin">
				<img src="https://travis-ci.org/humanmade/tachyon.svg?branch=master" alt="Build status">
			</a>
			<a href="http://codecov.io/github/humanmade/tachyon-plugin?branch=master">
				<img src="http://codecov.io/github/humanmade/tachyon-plugin/coverage.svg?branch=master" alt="Coverage via codecov.io" />
			</a>
		</td>
	</tr>
	<tr>
		<td>
			A <strong><a href="https://hmn.md/">Human Made</a></strong> project. Maintained by @joehoyle.
		</td>
		<td align="center">
			<img src="https://hmn.md/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" />
		</td>
	</tr>
</table>

[Tachyon](https://github.com/humanmade/tachyon) is an image resizing service built to be used with Amazon S3 as the image backend, AWS Lambda (or any node.js server) to process images using [sharp](http://sharp.pixelplumbing.com/en/stable/), and sits behind a CDN such as CloudFront or CloudFlare.

This plugin handles modifying WordPress image URLs to use a Tachyon service instance.

## Installation

1. Upload and enable this plugin.
2. Add `define( 'TACHYON_URL', 'https://your.tachyon.url/path/to/uploads' )` to your `wp-config.php` file.

## Usage

Typically the above steps are all you need to do however you can use the following public facing functions and filters.

### Functions

#### `tachyon_url( string $image_url, array $args = [] )`

This function returns the Tachyon URL for a given image hosted on Amazon S3.

```php
$image_url = 'https://my-bucket.s3.us-east-1.amazonaws.com/path/to/image.jpg';
$args      = [
	'resize'  => '300,300',
	'quality' => 90
];

$url = tachyon_url( $image_url, $args );
```

### Filters

The following filters allow you to modify the output and behaviour of the plugin.

#### `tachyon_disable_in_admin`

Defaults to `true`. You can override this by adding the following code to a plugin or your theme's `functions.php`:

```php
add_filter( 'tachyon_disable_in_admin', '__return_false' );
```

#### `tachyon_override_image_downsize`

Defaults to `false`. Provides a way of preventing Tachyon from being applied to images retrieved from WordPress Core at the lowest level, you might use this if you wanted to use `tachyon_url()` manually in specific cases.

```php
add_filter( 'tachyon_override_image_downsize', '__return_true' );
```

#### `tachyon_skip_for_url`

Allows skipping the Tachyon URL for a given image URL. Defaults to `false`.

```php
add_filter( 'tachyon_skip_for_url', function ( $skip, $image_url, $args ) {
	if ( strpos( $image_url, 'original' ) !== false ) {
		return true;
	}
	
	return $skip;
}, 10, 3 );
```

#### `tachyon_pre_image_url`

Filters the Tachyon image URL excluding the query string arguments. You might use this to shard Tachyon requests across multiple instances of the service for example.

```php
add_filter( 'tachyon_pre_image_url', function ( $image_url, $args ) {
	if ( rand( 1, 2 ) === 2 ) {
		$image_url = str_replace( TACHYON_URL, TACHYON_URL_2, $image_url );
	}
	
	return $image_url;
}, 10, 2 );
```

#### `tachyon_pre_args`

Filters the query string parameters appended to the tachyon image URL.

```php
add_filter( 'tachyon_pre_args', function ( $args ) {
	if ( isset( $args['resize'] ) ) {
		$args['crop_strategy'] = 'smart';
	}
	
	return $args;
} );
```

#### `tachyon_remove_size_attributes`

Defaults to `true`. `width` & `height` attributes on image tags are removed by default to prevent aspect ratio distortion that can happen in some unusual cases where `srcset` sizes have different aspect ratios.


```php
add_filter( 'tachyon_remove_size_attributes', '__return_true' );
```

## Credits
Created by Human Made for high volume and large-scale sites, such as [Happytables](http://happytables.com/). We run Tachyon on sites with millions of monthly page views, and thousands of sites.

Written and maintained by [Joe Hoyle](https://github.com/joehoyle).

Tachyon is forked from Photon by Automattic Inc. As Tachyon is not an all-purpose image resizer, rather it uses a media library in Amazon S3, it has a different use case to Photon.

Interested in joining in on the fun? [Join us, and become human!](https://hmn.md/is/hiring/)
