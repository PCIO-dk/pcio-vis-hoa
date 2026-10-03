<?php
/**
 * Uninstall handler for PCIO VIS HOA.
 *
 * Runs only when the plugin is deleted from the WordPress admin. Drops the
 * custom table and removes the schema-version option so no orphaned data is
 * left behind.
 *
 * @package PCIO_VIS_HOA
 */

// Bail if WordPress did not invoke this file via the uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

require_once __DIR__ . '/includes/class-pcio-vis-hoa-db.php';

PCIO_VIS_HOA_DB::drop_table();
delete_option( 'pcio_me_hoa_db_version' );
