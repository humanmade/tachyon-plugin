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
	 * @synopsis [--network] [--sites-page=<int>]
	 */
	public function migrate_files( $args, $assoc_args ) {
		global $wpdb;

		$assoc_args = wp_parse_args( $assoc_args, [
			'network' => false,
			'sites-page' => 0,
		] );

		$sites = [ get_current_blog_id() ];
		if ( $assoc_args['network'] ) {
			$sites = get_sites( [
				'fields' => 'ids',
				'offset' => $assoc_args['sites-page'],
			] );
		}

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

				WP_CLI::log( sprintf( 'Renamed attachment %d successfully, performing search & replace.', $attachment_id ) );

				// Add the full size to the array.
				$result['old']['sizes']['full'] = [
					'file' => $result['old']['file'],
				];
				$result['new']['sizes']['full'] = [
					'file' => $result['new']['file'],
				];

				// Run search replace against each image size.
				foreach ( $result['old']['sizes'] as $size => $size_data ) {
					if ( ! isset( $result['new']['sizes'][ $size ] ) ) {
						WP_CLI::error( sprintf( '  - Size "%s" does not exist for updated attachment %d', $size, $attachment_id ), false );
						continue;
					}

					$options = [
						'return' => true, // Don't capture STDOUT.
						'launch' => false, // Use another process.
						'exit_error' => true,
					];
					$count = WP_CLI::runcommand(
						sprintf(
							'search-replace %s %s --format=count --url=%s',
							$size_data['file'],
							$result['new']['sizes'][ $size ]['file'],
							home_url()
						),
						$options
					);
					WP_CLI::log( sprintf( '  - Made %d replacements for size "%s" %s -> %s', $count, $size, $size_data['file'], $result['new']['sizes'][ $size ]['file'] ) );
				}
			}

			restore_current_blog();
		}

		WP_CLI::log( 'Flushing cache...' );
		wp_cache_flush();
		WP_CLI::success( 'Done!' );
	}

}
