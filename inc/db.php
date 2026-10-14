<?php

if (!defined('ABSPATH')) {
    exit;
}


function cpt_builder_create_database()
{


    global $wpdb;
    $tbl_name = $wpdb->prefix . 'cpt_builder';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $tbl_name (
        id INT  NOT NULL AUTO_INCREMENT,
        post_type VARCHAR(100) NOT NULL,
        post_type_name VARCHAR(255) NOT NULL,
        tag_field_name VARCHAR(100) NOT NULL DEFAULT 'Tags',
        description TEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)) $charset_collate;";


    
    $taxonomy_table = $wpdb->prefix .'cpt_builder_taxonomies';

    $sql_taxonomy = "CREATE TABLE $taxonomy_table (
    id int  NOT NULL AUTO_INCREMENT,
    taxonomy varchar(100) NOT NULL,
    taxonomy_name varchar(255) NOT NULL,
    description text NULL,
    post_type varchar(100) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)) $charset_collate;";

    
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    dbDelta($sql_taxonomy);


    $fields_table = $wpdb->prefix . 'cpt_builder_fields';

    $sql_fields = "CREATE TABLE $fields_table (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_type VARCHAR(100) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    field_type VARCHAR(30) NOT NULL DEFAULT 'text',
    field_options TEXT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY post_type (post_type)
) $charset_collate;";

    dbDelta($sql_fields);
            
}