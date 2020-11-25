<?php
/**
 * CLI Commands for Tachyon.
 */

class Tachyon_Command extends WP_CLI_Command {

	/**
	 * Update attachment file names to work with Tachyon.
	 *
	 * Certain file names that end in dimensions such as those produced by
	 * WordPress eg. example-150x150.jpg can cause problems when uploaded
	 * as an original image. This prevents Tachyon from accurately and
	 * performantly rewriting the post content.
	 *
	 * @subcommand migrate-files
	 * @synopsis [--network] [--sites-page=<int>] [--include-columns=<columns>]
	 */
	public function migrate_files( $args, $assoc_args ) {
		global $wpdb;

		$assoc_args = wp_parse_args( $assoc_args, [
			'network' => false,
			'sites-page' => 0,
			'include-columns' => 'post_content,post_excerpt,meta_value'
		] );

		$sites = [ get_current_blog_id() ];
		if ( $assoc_args['network'] ) {
			$sites = get_sites( [
				'fields' => 'ids',
				'offset' => $assoc_args['sites-page'],
			] );
		}

		// Get a reference to the search replace command class.
		// The class uses the `__invoke()` magic method allowing it to be called like a function.
		$search_replace = new Search_Replace_Command;

		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );

			if ( $assoc_args['network'] ) {
				WP_CLI::log( "Processing site {$site_id}" );
			}

			$attachments = $wpdb->get_col( "SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_wp_attached_file'
				AND meta_value REGEXP '-[[:digit:]]+x[[:digit:]]+\.(jpe?g|png|gif)$';" );

			WP_CLI::log( sprintf( 'Renaming %d attachments', count( $attachments ) ) );

			foreach ( $attachments as $attachment_id ) {
				$result = Tachyon::_rename_file( $attachment_id );
				if ( is_wp_error( $result ) ) {
					WP_CLI::error( $result, false );
					continue;
				}

				WP_CLI::success( sprintf( 'Renamed attachment %d successfully, performing search & replace.', $attachment_id ) );

				// Add the full size to the array.
				$result['old']['sizes']['full'] = [
					'file' => $result['old']['file'],
				];
				$result['new']['sizes']['full'] = [
					'file' => $result['new']['file'],
				];

				// Store all update queries into one transaction per image.
				$wpdb->query( 'START TRANSACTION;' );

				// Run search replace against each image size.
				foreach ( $result['old']['sizes'] as $size => $size_data ) {
					if ( ! isset( $result['new']['sizes'][ $size ] ) ) {
						WP_CLI::error( sprintf( 'Size "%s" does not exist for updated attachment %d', $size, $attachment_id ), false );
						continue;
					}

					WP_CLI::log( sprintf( 'Making replacements for size "%s" %s -> %s', $size, $size_data['file'], $result['new']['sizes'][ $size ]['file'] ) );

					// Run search & replace.
					$search_replace(
						[
							// Old.
							$size_data['file'],
							// New.
							$result['new']['sizes'][ $size ]['file'],
						],
						// Associative array args / command flags.
						[
							'include-columns' => $assoc_args['include-columns'],
							'quiet' => true,
						]
					);
				}

				$wpdb->query( 'COMMIT;' );
			}

			restore_current_blog();
		}

		WP_CLI::log( 'Flushing cache...' );
		wp_cache_flush();
		WP_CLI::success( 'Done!' );
	}

}
