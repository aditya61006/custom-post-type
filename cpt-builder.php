<?php
/*
Plugin Name: CPT Builder
Description: Create and Manage Custom Post Types
Author: Aditya 
version: 1.0
*/

if(!defined('ABSPATH')){
    exit;
}

function cpt_builder_enqueue_frontend_styles()
{

 wp_enqueue_style(
        'cpt-builder-frontend',
        plugin_dir_url(__FILE__) . 'assets/css/frontend.css',
        array(),
        '1.0'
    );
}

add_action('wp_enqueue_scripts', 'cpt_builder_enqueue_frontend_styles');

require_once plugin_dir_path(__FILE__) . 'inc/db.php';
require_once plugin_dir_path(__FILE__) . 'inc/texonomy.php';
require_once plugin_dir_path(__FILE__) . 'inc/cpt.php';

register_activation_hook(__FILE__,'cpt_builder_create_database');


require_once plugin_dir_path(__FILE__). 'admin/cpt-form.php';
require_once plugin_dir_path(__FILE__). 'admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'admin/edit-cpt.php';
require_once plugin_dir_path(__FILE__) . 'admin/taxonomy-form.php';
require_once plugin_dir_path(__FILE__) . 'admin/edit-taxonomy.php';
require_once plugin_dir_path(__FILE__) . 'admin/taxonomies.php';
require_once plugin_dir_path(__FILE__) . 'frontend/cpt-display.php';
require_once plugin_dir_path(__FILE__) . 'frontend/post-form.php';
require_once plugin_dir_path(__FILE__) . 'frontend/cpt-search.php';
require_once plugin_dir_path(__FILE__) . 'frontend/cpt-post.php';

function search_scripts()
{
    if (is_admin()) {
        return;
    }

    wp_enqueue_script(
        'cpt-builder-search',
        plugins_url('/assets/js/search.js', __FILE__),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script(
        'cpt-builder-search',
        'cptBuilderAjax',
        array('ajax_url' => admin_url('admin-ajax.php'))
    );
}
add_action('wp_enqueue_scripts', 'search_scripts');

add_action('wp_enqueue_scripts', function () {
    if (is_admin() || !is_singular()) {
        return;
    }

    wp_enqueue_style(
        'cpt-builder-style',
        plugin_dir_url(__FILE__) . '/assets/css/cpt-builder-style.css',
        array(),
        '1.0.0'
    );
});
