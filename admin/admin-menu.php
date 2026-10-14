<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_admin_menu()
{

    add_menu_page(
        'CPT Builder',
        'CPT Builder',
        'manage_options',
        'cpt-builder',
        'cpt_builder_page',
        'dashicons-admin-post',
        20
    );

    add_submenu_page('cpt-builder', 'All CPTs', 'All CPTs', 'manage_options', 'cpt-builder', 'cpt_builder_page');

    add_submenu_page(
        'cpt-builder',
        'Add New CPT',
        'Add New CPT',
        'manage_options',
        'cpt-builder-add',
        'form_page'
    );

    add_submenu_page(
        null,
        'Edit CPT',
        'Edit CPT',
        'manage_options',
        'cpt-builder-edit',
        'cpt_builder_edit_page'
    );
    // add_submenu_page('cpt-builder','All Taxonomies','All Taxonomies','manage_options',
    // 'cpt-builder-taxonomies','cpt_builder_taxonomies_page');

    add_submenu_page(
        'cpt-builder',
        'Add New Taxonomy',
        'Add New Taxonomy',
        'manage_options',
        'cpt-builder-taxonomy-add',
        'cpt_builder_taxonomy_form_page'
    );

    add_submenu_page(
        'cpt-builder',
        'All Taxonomies',
        'All Taxonomies',
        'manage_options',
        'cpt-builder-taxonomies',
        'cpt_builder_taxonomies_page'
    );
    add_submenu_page(
        null,
        'Edit Taxonomy',
        'Edit Taxonomy',
        'manage_options',
        'cpt-builder-taxonomy-edit',
        'cpt_builder_edit_taxonomy_page'
    );
    
    
}

add_action('admin_menu', 'cpt_builder_admin_menu');


function cpt_builder_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'cpt_builder';
    $cpts = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY id DESC"
    );
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            Custom Post Type Builder
        </h1>
        <a href="<?php echo esc_url(
                        admin_url('admin.php?page=cpt-builder-add')
                    ); ?>" class="page-title-action">
            Add New CPT
        </a>

        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Tag Field Name  </th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($cpts)) { ?>
                    <?php foreach ($cpts as $cpt) { ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo esc_html($cpt->post_type_name); ?>
                                </strong>
                            </td>

                            <td><?php echo $cpt->post_type; ?></td>

                            <td> <?php echo $cpt->description; ?>
                            </td>
                            <td> <?php echo $cpt->tag_field_name; ?>
                            </td>
                            <td><?php $user = get_userdata($cpt->created_by);
                                echo esc_html($user->display_name); ?> </td>

                            <td> <?php echo $cpt->created_at; ?> </td>

                            <td><a href=" <?php echo admin_url('admin.php?page=cpt-builder-edit&id=' . $cpt->id) ?> ">Edit</a>

                                <a href="<?php echo esc_url(admin_url('admin.php?page=cpt-builder&action=delete-cpt&id=' . $cpt->id)); ?>"
                                    onclick="return confirm('Are you sure you want to delete this CPT?');">
                                    Delete
                                </a>

                        </tr>


                    <?php }
                } else { ?>

                    <tr>
                        <td colspan="6">
                            No Custom Post Types found.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>
    </div>

<?php
}
