<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_register_taxonomies()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';
    $taxonomies = $wpdb->get_results(
        "SELECT * FROM $table_name"
    );
    if (!$taxonomies) {
        return;
    }

    foreach ($taxonomies as $taxonomy) {

        register_taxonomy(
            $taxonomy->taxonomy,
            array($taxonomy->post_type),
            array(
                'labels' => array(
                    'name' => $taxonomy->taxonomy_name,
                    'singular_name' => $taxonomy->taxonomy_name
                ),
                'description' => $taxonomy->description,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'hierarchical' => true,
                'rewrite' => array(
                    'slug' => $taxonomy->taxonomy
                )
            )
        );
    }
}

add_action(
    'init',
    'cpt_builder_register_taxonomies'
);