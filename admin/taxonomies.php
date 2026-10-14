<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_taxonomies_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';

    $taxonomies = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY created_at DESC"
    );
    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">All Taxonomies</h1>

        <a href="<?php echo esc_url(
            admin_url('admin.php?page=cpt-builder-taxonomy-add')
        ); ?>" class="page-title-action">
            Add New Taxonomy
        </a>

        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">

            <thead>
                <tr>
                    <th>Taxonomy Name</th>
                    <th>Slug</th>
                    <th>Assigned CPT</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($taxonomies): ?>

                    <?php foreach ($taxonomies as $taxonomy): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php echo esc_html($taxonomy->taxonomy_name); ?>
                                </strong>
                            </td>

                            <td>
                                <?php echo $taxonomy->taxonomy; ?>
                            </td>

                            <td>
                                <?php echo $taxonomy->post_type; ?>
                            </td>
                            <td>
                                <?php echo $taxonomy->description; ?>
                            </td>

                            <td>
                                <?php echo $taxonomy->created_at; ?>
                            </td>

                            <td>
                                <a href="<?php echo admin_url('admin.php?page=cpt-builder-taxonomy-edit&id='.$taxonomy->id) ?>">
                                    Edit
                                </a>
                                |
                                <a href="<?php echo admin_url('admin.php?page=cpt-builder-taxonomy-edit&action=delete-taxonomy&id=' .$taxonomy->id) ?>">
                                    Delete
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            No taxonomies found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php
}