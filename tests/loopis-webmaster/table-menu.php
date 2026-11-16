<?php
/**
 * Run from loopis-develooper with: php tests/loopis-webmaster/table-menu.php
 */

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['table_menu_hooks'] = array();
$GLOBALS['table_menu_args']  = null;

function add_action( $hook, $callback, $priority = 10 ) {
	$GLOBALS['table_menu_hooks'][ $hook ] = $callback;
}

function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}

function add_submenu_page( ...$args ) {
	$GLOBALS['table_menu_args'] = $args;
}

require dirname( __DIR__, 3 ) . '/loopis-webmaster/includes/reusable/table-menu.php';

$editor = new table_menu(
	'wp_example_records',
	array( 'record_id' => '%d' ),
	array( 'label' => '%s' ),
	array(
		'menu_name'   => 'Example records',
		'parent_slug' => 'example-parent',
		'hook_prefix' => 'example_records_',
		'page_name'   => 'example-records',
	)
);
$editor->register_admin_menu();

$submenu = $GLOBALS['table_menu_args'];
if (
	$submenu[0] !== 'example-parent'
	|| $submenu[1] !== 'Example records'
	|| $submenu[4] !== 'example-records'
	|| $submenu[5][0] !== $editor
	|| ! isset( $GLOBALS['table_menu_hooks']['admin_post_example_records_save'] )
) {
	throw new RuntimeException( 'The table editor did not use its configured menu and save hook.' );
}

echo "Reusable table menu test passed.\n";
