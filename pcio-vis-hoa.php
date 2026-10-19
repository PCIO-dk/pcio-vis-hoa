<?php
/*
Plugin Name:  PCIO VIS HOA
Plugin URI:   https://www.pcio.dk/
Description:  Extends PCIO VIS Member Event with HOA-specific member fields (external address, membership validity, and notes).
Version:      1.1.0
Author:       PCIO
Author URI:   https://www.pcio.dk
Requires at least: 6.2
Requires PHP: 8.1
Requires Plugins: pcio-vis-member-event
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  pcio-vis-hoa
Domain Path:  /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/includes/class-pcio-vis-hoa-db.php';

class PCIO_VIS_HOA {

    // ── Schema ────────────────────────────────────────────────────

    /**
     * Ensures the HOA table exists. Delegated to the DB layer.
     */
    public static function install(): void {
        PCIO_VIS_HOA_DB::install();
    }

    /**
     * Remove a member's HOA row when the core deletes the member.
     * Fired via the core 'pcio_me_member_deleted' action.
     */
    public static function on_member_deleted( int $member_id ): void {
        PCIO_VIS_HOA_DB::delete( $member_id );
    }

    // ── Hooks ─────────────────────────────────────────────────────

    public static function register(): void {
        add_action( 'admin_init',             [ self::class, 'install' ] );
        add_action( 'pcio_me_member_deleted', [ self::class, 'on_member_deleted' ] );
        add_filter( 'pcio_me_member_fields',  [ self::class, 'add_fields' ] );
        add_filter( 'pcio_me_member_row',     [ self::class, 'decorate_row' ] );
        add_action( 'pcio_me_member_saved',   [ self::class, 'save_row' ], 10, 2 );
        add_filter( 'pcio_me_members_list',   [ self::class, 'sort_by_id' ] );
        add_filter( 'pcio_me_history_tables', [ 'PCIO_VIS_HOA_DB', 'history_tables' ] );
    }

    // ── Field definitions ─────────────────────────────────────────

    public static function add_fields( array $fields ): array {
        return array_merge( $fields, [
            [ 'key' => 'ext_address',  'label' => __( 'External address', 'pcio-vis-hoa' ), 'type' => 'text',     'sortable' => false, 'list_col' => false, 'meta' => true ],
            [ 'key' => 'valid_after',  'label' => __( 'Valid after',       'pcio-vis-hoa' ), 'type' => 'date',     'sortable' => true,  'list_col' => true,  'meta' => true ],
            [ 'key' => 'valid_before', 'label' => __( 'Valid before',      'pcio-vis-hoa' ), 'type' => 'date',     'sortable' => true,  'list_col' => true,  'meta' => true ],
            [ 'key' => 'note',         'label' => __( 'Note',              'pcio-vis-hoa' ), 'type' => 'textarea', 'sortable' => false, 'list_col' => false, 'meta' => true ],
        ] );
    }

    // ── Read ──────────────────────────────────────────────────────

    /**
     * Merges HOA fields into a member row returned by the main plugin.
     * Called via the pcio_me_member_row filter.
     */
    public static function decorate_row( array $member ): array {
        $row = PCIO_VIS_HOA_DB::get( (int) $member['id'] );

        if ( $row ) {
            // Blank out invalid/zero dates ('0000-00-00') so they are not shown
            // in the member list or on the shortcode page.
            $row['valid_after']  = self::clean_date( $row['valid_after'] );
            $row['valid_before'] = self::clean_date( $row['valid_before'] );
        }

        return array_merge( $member, $row ?? [] );
    }

    /**
     * Returns a valid Y-m-d date string, or '' for null/empty/zero dates.
     */
    private static function clean_date( ?string $date ): string {
        $date = trim( (string) $date );
        if ( $date === '' || str_starts_with( $date, '0000-00-00' ) ) {
            return '';
        }
        return $date;
    }

    /**
     * Sorts the [pcio_me_members] shortcode list by member number (ascending)
     * and drops members without a member number.
     * Called via the pcio_me_members_list filter.
     *
     * @param array $members
     * @return array
     */
    public static function sort_by_id( array $members ): array {
        $members = array_filter(
            $members,
            static fn( $m ) => trim( (string) ( $m['member_number'] ?? '' ) ) !== ''
        );
        usort(
            $members,
            static fn( $a, $b ) => (int) ( $a['member_number'] ?? 0 ) <=> (int) ( $b['member_number'] ?? 0 )
        );
        return $members;
    }

    // ── Write ─────────────────────────────────────────────────────

    /**
     * Upserts the HOA row whenever the main plugin saves a member.
     * Called via the pcio_me_member_saved action.
     *
     * @param int   $id    Member ID.
     * @param array $data  Raw submitted data from the REST request.
     */
    public static function save_row( int $id, array $data ): void {
        PCIO_VIS_HOA_DB::upsert(
            $id,
            sanitize_text_field(     $data['ext_address']  ?? '' ),
            sanitize_text_field(     $data['valid_after']  ?? '' ) ?: null,
            sanitize_text_field(     $data['valid_before'] ?? '' ) ?: null,
            sanitize_textarea_field( $data['note']         ?? '' )
        );
    }
}

register_activation_hook( __FILE__, [ 'PCIO_VIS_HOA', 'install' ] );

add_action( 'plugins_loaded', static function (): void {
    if ( ! class_exists( 'PCIO_VIS_Plugin' ) ) {
        return;
    }
    PCIO_VIS_HOA::register();
} );
