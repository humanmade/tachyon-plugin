<?php

/**
 * Plugin Name: Techyon x1
 * Description: A standalone techyon proof of concept
 * Author: Joe Hoyle | HUman Made
 */

if ( ! defined( 'TECHYON_URL' ) || ! TECHYON_URL ) {
	return;
}

require_once( dirname( __FILE__ ) . '/inc/class-techyon.php' );

Techyon::instance();

/**
 * Generates a Techyon URL.
 *
 * @see http://developer.wordpress.com/docs/techyon/
 *
 * @param string $image_url URL to the publicly accessible image you want to manipulate
 * @param array|string $args An array of arguments, i.e. array( 'w' => '300', 'resize' => array( 123, 456 ) ), or in string form (w=123&h=456)
 * @return string The raw final URL. You should run this through esc_url() before displaying it.
 */
function techyon_url( $image_url, $args = array(), $scheme = null ) {

	$upload_dir = wp_upload_dir();
	$upload_baseurl = $upload_dir['baseurl'];

	if ( is_multisite() ) {
		$upload_baseurl = preg_replace( '#/sites/[\d]+#', '', $upload_baseurl );
	}

	$image_url = trim( $image_url );

	if ( strpos( $image_url, $upload_dir['baseurl'] ) !== 0 ) {
		return $image_url;
	}

	if ( false !== apply_filters( 'jetpack_techyon_skip_for_url', false, $image_url, $args, $scheme ) ) {
		return $image_url;
	}

	$image_url = apply_filters( 'jetpack_techyon_pre_image_url', $image_url, $args,      $scheme );
	$args      = apply_filters( 'jetpack_techyon_pre_args',      $args,      $image_url, $scheme );

	$techyon_url = str_replace( $upload_baseurl, TECHYON_URL, $image_url );

	if ( $args ) {
		if ( is_array( $args ) ) {
			$techyon_url = add_query_arg( $args, $techyon_url );
		} else {
			// You can pass a query string for complicated requests but where you still want CDN subdomain help, etc.
			$techyon_url .= '?' . $args;
		}
	}


	return $techyon_url;
}
add_filter( 'jetpack_techyon_url', 'techyon_url', 10, 3 );
