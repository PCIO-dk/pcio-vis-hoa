=== PCIO VIS HOA ===
Contributors:      pcio
Author:            PCIO
Author URI:        https://www.pcio.dk
Plugin URI:        https://www.pcio.dk/vis-plugins
Tags:              members, hoa, homeowners, association, fields
Requires at least: 6.2
Tested up to:      7.1
Requires PHP:      8.1
Requires Plugins:  pcio-vis-member-event
Stable tag:        1.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Extends PCIO VIS Member Event with homeowners-association member fields: external address, membership validity and notes.

== Description ==

PCIO VIS HOA is an Home Owners Association extension for the PCIO VIS Member Event plugin. It adds HOA-specific member fields — external address, membership validity and notes — to the member records managed by the core plugin.

This plugin requires the PCIO VIS Member Event core plugin to be installed and active.

== Installation ==

1. Install and activate the **PCIO VIS Member Event** core plugin first.
2. Upload the `pcio-vis-hoa` directory to `/wp-content/plugins/`.
3. Activate the plugin in **Plugins** in the WordPress admin.
4. The HOA fields appear on the member records under the **Vis** admin menu.

== Frequently Asked Questions ==

= Which fields does this add? =
External address, membership validity and notes.


== Screenshots ==

1. Create member with more fields added


== Upgrade Notice ==

= 1.1.0 =
Requires PCIO VIS Member Event 1.6 or later.

== Changelog ==

= 1.1.0 =
* Added: **Change history with undo** — all writes to Vis tables are logged in the new `me_change_history` table, with undo data in `me_change_undo`. A new **Vis → History** admin page lists every change with filters and an Undo button, guarded by the new `vis_undo_changes` capability (granted to System Admin).

= 1.0.0 =
* Initial release.
