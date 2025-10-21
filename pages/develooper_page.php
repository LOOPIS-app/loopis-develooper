<?php
/**
 * WP Admin page for configuring develooper user.
 *
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function develooper_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧑‍💻 LOOPIS Develooper</h1>
        <p class="description">💡 Insert develooper user for testing and development.</p>

        <!-- Page content-->
        <h2>Current user</h2>
        <?php
        $my_user = wp_get_current_user();
        echo '<p>You are currently logged in as <strong>' . esc_html($my_user->user_login) . ' (ID: ' . esc_html($my_user->ID) . ')</strong></p>';
        ?>
        <p>&nbsp;</p> <!-- Spacer -->

        <h2>Insert develooper user</h2>
        <form method="POST">
            <select id="develooper_select" name="develooper_choice" class="regular-text">
            <option value="" disabled selected>Select</option>
            <option value="develooper_1">Develooper 1</option>
            <option value="develooper_2">Develooper 2</option>
            <option value="develooper_3">Develooper 3</option>
            </select>
            <button class="button button-primary" type="submit" name="insert_develooper" value="1">Insert</button>
        </form>
        <p><i>[Not yet implemented... Select your name from the dropdown to quickly create your user account?]</i></p>
    <?php
    }