<?php
/**
 * WP Admin page for configuring developer plugins.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

if (isset($_POST['develooper_plugins_install'])) {
    require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_install.php';
    develooper_plugins_install();

    set_transient('plugin installation confirmations', $inserted_users, 30);

    wp_redirect(add_query_arg('action', 'installed', wp_get_referer()));
    exit;
}

// Function to render the page
function develooper_plugins_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧩 Plugins <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
        <p class="description">💡 Useful plugins for develoopers.</p>

        <!-- Page content-->
        <h2>Plugins recommended</h2>
        <form method="POST">
            <button class="button button-primary" type="submit" name="develooper_plugins_install">Install</button>
        </form>
        <p><i>[Add list of recommended plugins + button to install/update.]</i></p>

        <h2>Installed plugins</h2>
        <p><i>[Add list of recommended plugins installed + button to delete.]</i></p>
    </div>
<?php
}