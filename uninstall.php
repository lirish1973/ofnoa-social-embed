<?php
/**
 * Uninstall routine.
 *
 * Content is never deleted automatically — only the plugin's own options and
 * caches go. Delete the videos from the library first if you want them gone.
 *
 * @package OfnoaSocialEmbed
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'ose_settings' );
delete_site_option( 'ose_settings' );
delete_site_transient( 'ose_gh_release' );

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_ose_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_ose_' ) . '%'
	)
);

wp_cache_flush();
