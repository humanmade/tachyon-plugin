<?php

/**
 * Plugin Name: Tachyon
 * Version: 0.11.1
 * Description: A standalone tachyon proof of concept
 * Author: Joe Hoyle | Human Made | Automattic Inc
 */

/**
 * Copyright: Automattic Inc
 * Copyright: Human Made Limited
 */

if ( ! defined( 'TACHYON_URL' ) || ! TACHYON_URL ) {
	return;
}

require_once( dirname( __FILE__ ) . '/inc/class-tachyon.php' );

Tachyon::instance();

/**
 * Generates a Tachyon URL.
 *
 * @see http://developer.wordpress.com/docs/tachyon/
 *
 * @param string $image_url URL to the publicly accessible image you want to manipulate
 * @param array|string $args An array of arguments, i.e. array( 'w' => '300', 'resize' => array( 123, 456 ) ), or in string form (w=123&h=456)
 * @return string The raw final URL. You should run this through esc_url() before displaying it.
 */
function tachyon_url( $image_url, $args = array(), $scheme = null ) {

	$upload_dir = wp_upload_dir();
	$upload_baseurl = $upload_dir['baseurl'];

	if ( is_multisite() ) {
		$upload_baseurl = preg_replace( '#/sites/[\d]+#', '', $upload_baseurl );
	}

	$image_url = trim( $image_url );

	$image_file = basename( parse_url( $image_url, PHP_URL_PATH ) );
	$image_url  = str_replace( $image_file, urlencode( $image_file ), $image_url );

	if ( strpos( $image_url, $upload_baseurl ) !== 0 ) {
		return $image_url;
	}

	if ( false !== apply_filters( 'tachyon_skip_for_url', false, $image_url, $args, $scheme ) ) {
		return $image_url;
	}

	$image_url = apply_filters( 'tachyon_pre_image_url', $image_url, $args,      $scheme );
	$args      = apply_filters( 'tachyon_pre_args',      $args,      $image_url, $scheme );

	$tachyon_url = str_replace( $upload_baseurl, TACHYON_URL, $image_url );

	if ( $args ) {
		if ( is_array( $args ) ) {
			$tachyon_url = add_query_arg( $args, $tachyon_url );
		} else {
			// You can pass a query string for complicated requests but where you still want CDN subdomain help, etc.
			$tachyon_url .= '?' . $args;
		}
	}

	/**
	 * Allows a final modification of the generated tachyon URL.
	 *
	 * @param string $tachyon_url The final tachyon image URL including query args.
	 * @param string $image_url   The image URL without query args.
	 * @param array  $args        A key value array of the query args appended to $image_url.
	 */
	return apply_filters( 'tachyon_url', $tachyon_url, $image_url, $args );
}
