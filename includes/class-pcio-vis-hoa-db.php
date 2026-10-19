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

    /**
     * Register the HOA member table with the core history service.
     *
     * @param array<string,array> $tables Existing history table definitions.
     * @return array<string,array>
     */
    public static function history_tables( array $tables ): array {
        $tables['hoa_members'] = [
            'table'          => self::TABLE,
            'pk'             => [ 'member_id' ],
            'auto_increment' => false,
            'label'          => __( 'HOA member fields', 'pcio-vis-hoa' ),
            'title'          => [ self::class, 'history_title' ],
            'weight'         => 4,
            'large'          => [ 'note' ],
            'fields'         => [
                'ext_address'  => __( 'External address', 'pcio-vis-hoa' ),
                'valid_after'  => __( 'Valid after', 'pcio-vis-hoa' ),
                'valid_before' => __( 'Valid before', 'pcio-vis-hoa' ),
                'note'         => __( 'Note', 'pcio-vis-hoa' ),
            ],
            'links'          => static function ( array $row ): array {
                return [ 'member_id' => (int) ( $row['member_id'] ?? 0 ) ];
            },
        ];

        return $tables;
    }

    /**
     * Row title for HOA history entries.
     *
     * @param array<string,mixed> $row HOA row.
     */
    public static function history_title( array $row ): string {
        if ( class_exists( 'PCIO_VIS_History_Tables' ) ) {
            $title = PCIO_VIS_History_Tables::member_name( (int) ( $row['member_id'] ?? 0 ) );
            if ( '' !== $title ) {
                return $title;
            }
        }

        return '#' . (int) ( $row['member_id'] ?? 0 );
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
        PCIO_VIS_History::upsert(
            'hoa_members',
            [
                'member_id'    => $member_id,
                'ext_address'  => $ext_address,
                'valid_after'  => $valid_after,
                'valid_before' => $valid_before,
                'note'         => $note,
            ],
            [ 'member_id' ]
        );
    }

    /**
     * Remove a member's HOA row (called when the core deletes the member).
     */
    public static function delete( int $member_id ): void {
        PCIO_VIS_History::delete( 'hoa_members', [ 'member_id' => $member_id ], [ '%d' ] );
    }
}
