<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_register_post()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder';

    $cpts = $wpdb->get_results(
        "SELECT * FROM $table_name"
    );

    if (!$cpts) {
        return;
    }

    foreach ($cpts as $cpt) {

        register_post_type(
            $cpt->post_type,
            array(
                'labels' => array(
                    'name' => $cpt->post_type_name,
                    'singular_name' => $cpt->post_type_name,
                ),
                'description' => $cpt->description,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'supports' => array(
                    'title',
                    'editor',
                    'thumbnail',
                    'custom-fields',
                ),
                'has_archive' => true,
                'rewrite' => array(
                    'slug' => $cpt->post_type,
                ),
            )
        );

        $tag_name = !empty($cpt->tag_field_name) ? $cpt->tag_field_name : 'Tags';

        $taxonomy_slug = sanitize_key($cpt->post_type . '_tags');

        register_taxonomy(
            $taxonomy_slug,
            array($cpt->post_type),
            array(
                'labels' => array(
                    'name' => $tag_name,
                    'singular_name' => $tag_name,
                    'search_items' => 'Search ' . $tag_name,
                    'popular_items' => 'Popular ' . $tag_name,
                    'all_items' => 'All ' . $tag_name,
                    'edit_item' => 'Edit ' . $tag_name,
                    'update_item' => 'Update ' . $tag_name,
                    'add_new_item' => 'Add New ' . $tag_name,
                    'new_item_name' => 'New ' . $tag_name,
                    'menu_name' => $tag_name,
                ),
                'public' => true,
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => false,
                'show_in_rest' => true,
                'rewrite' => array(
                    'slug' => $taxonomy_slug,
                ),
            )
        );
    }
}

add_action('init', 'cpt_builder_register_post');
