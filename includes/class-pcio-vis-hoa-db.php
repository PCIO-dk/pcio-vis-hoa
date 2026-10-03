<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom admin table; caching would hide real-time changes. Schema changes are intentional install/uninstall routines.

/**
 * Database layer for PCIO VIS HOA.
 *
 * Owns the {prefix}me_hoa_members table, which stores the HOA-specific member
 * fields (external address, membership validity window and a free-text note),
 * keyed one-to-one to a core member by member_id.
 */
class PCIO_VIS_HOA_DB {

    const TABLE = 'me_hoa_members';

    const DB_VERSION = '1.0.0';

    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    // ── Schema ────────────────────────────────────────────────────

    /**
     * Create the table on first install / version bump.
     *
     * Uses a prepared CREATE TABLE with the %i identifier placeholder (WP 6.2+)
     * instead of dbDelta(), which cannot take prepared SQL. The charset/collation
     * is hardcoded because it cannot be a placeholder.
     */
    public static function install(): void {
        if ( get_option( 'pcio_me_hoa_db_version' ) === self::DB_VERSION ) {
            return; // schema already current
        }

        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "CREATE TABLE IF NOT EXISTS %i (
                    member_id    INT          NOT NULL,
                    ext_address  VARCHAR(200) NOT NULL DEFAULT '',
                    valid_after  DATE                  DEFAULT NULL,
                    valid_before DATE                  DEFAULT NULL,
                    note         TEXT,
                    PRIMARY KEY  (member_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                self::table()
            )
        );

        update_option( 'pcio_me_hoa_db_version', self::DB_VERSION );
    }

    /**
     * Drop the table. Used by uninstall.php.
     */
    public static function drop_table(): void {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', self::table() ) );
    }

    // ── Read ──────────────────────────────────────────────────────

    /**
     * Fetch a member's HOA row, or null when none exists.
     *
     * @return array<string,mixed>|null
     */
    public static function get( int $member_id ): ?array {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT `ext_address`, `valid_after`, `valid_before`, `note`
                 FROM %i
                 WHERE `member_id` = %d',
                self::table(),
                $member_id
            ),
            ARRAY_A
        );
    }

    // ── Write ─────────────────────────────────────────────────────

    /**
     * Insert or update a member's HOA row.
     */
    public static function upsert( int $member_id, string $ext_address, ?string $valid_after, ?string $valid_before, string $note ): void {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                'INSERT INTO %i
                    (`member_id`, `ext_address`, `valid_after`, `valid_before`, `note`)
                 VALUES (%d, %s, %s, %s, %s)
                 ON DUPLICATE KEY UPDATE
                    `ext_address`  = VALUES(`ext_address`),
                    `valid_after`  = VALUES(`valid_after`),
                    `valid_before` = VALUES(`valid_before`),
                    `note`         = VALUES(`note`)',
                self::table(),
                $member_id,
                $ext_address,
                $valid_after,
                $valid_before,
                $note
            )
        );
    }

    /**
     * Remove a member's HOA row (called when the core deletes the member).
     */
    public static function delete( int $member_id ): void {
        global $wpdb;
        $wpdb->delete( self::table(), [ 'member_id' => $member_id ], [ '%d' ] );
    }
}
