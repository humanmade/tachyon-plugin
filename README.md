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

## Usage

1. Upload and enable this plugin.
2. Add `define( 'TACHYON_URL', 'https://your.tachyon.url/path/to/uploads' )` to your `wp-config.php` file.

## Credits
Created by Human Made for high volume and large-scale sites, such as [Happytables](http://happytables.com/). We run Tachyon on sites with millions of monthly page views, and thousands of sites.

Written and maintained by [Joe Hoyle](https://github.com/joehoyle).

Tachyon is forked from Photon by Automattic Inc. As Tachyon is not an all-purpose image resizer, rather it uses a media library in Amazon S3, it has a different use case to Photon.

Interested in joining in on the fun? [Join us, and become human!](https://hmn.md/is/hiring/)
