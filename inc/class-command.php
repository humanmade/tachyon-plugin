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
	 * @synopsis [--network] [--remove-old-files] [--sites-page=<int>]
	 */
	public function migrate_files( $args, $assoc_args ) {
		global $wpdb;

		$assoc_args = wp_parse_args( $assoc_args, [
			'network' => false,
			'remove-old-files' => false,
			'sites-page' => 0
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
				$result = Tachyon::_rename_file( $attachment_id, $assoc_args['remove-old-files'] );
				if ( $result ) {
					WP_CLI::log( sprintf( 'Renamed attachment %d successfully', $attachment_id ) );
				} else {
					WP_CLI::error( $result );
				}
			}

			restore_current_blog();
		}

		WP_CLI::success( 'Done!' );
	}

}
