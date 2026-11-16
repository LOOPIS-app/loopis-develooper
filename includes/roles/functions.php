<?php
/**
 * Retrieve the current site's roles and their granted capabilities.
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

/** Return visible roles with their granted capabilities in alphabetical order. */
function loopis_develooper_get_roles() {
    $roles = array();

    foreach (wp_roles()->roles as $slug => $role) {
        if (in_array($slug, array('administrator', 'develooper'), true)) {
            continue;
        }

        $capabilities = array_keys(array_filter($role['capabilities']));
        sort($capabilities, SORT_STRING);
        $roles[$slug] = array(
            'name' => translate_user_role($role['name']),
            'capabilities' => $capabilities,
        );
    }

    return $roles;
}
