<?php

if (!defined('ABSPATH')) {
    exit;
}



function cpt_builder_update_taxonomy()
{
    if (!isset($_POST['update_taxonomy'])) {
        return;
    }

    $id = $_GET['id'];

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to update taxonomies.');
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';

    $taxonomy_name = $_POST['taxonomy_name'];

    $description = $_POST['description'];

    $post_type = $_POST['post_type'];

    if (empty($taxonomy_name) || empty($post_type)) {
        wp_die('Please fill in all required fields.');
    }

    $updated = $wpdb->update($table_name,array(
            'taxonomy_name' => $taxonomy_name,
            'description' => $description,
            'post_type' => $post_type
        ),
        array('id' => $id),
        array('%s','%s','%s'),
        array('%d'));

    if ($updated === false) {
        wp_die('Database update failed.');
    }

    wp_redirect(
        admin_url('admin.php?page=cpt-builder-taxonomies&updated=1')
    );

    exit;
}

add_action('admin_init', 'cpt_builder_update_taxonomy');



function cpt_builder_delete_taxonomy()
{
    if (!isset($_GET['action']) || $_GET['action'] !== 'delete-taxonomy') {
        return;
    }

    $id =$_GET['id'];

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to delete taxonomies.');
    }


    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';
    $deleted = $wpdb->delete($table_name,array('id' => $id),
        array('%d'));

    if ($deleted === false) {
        wp_die('Failed to delete taxonomy.');
    }

    wp_redirect(
        admin_url('admin.php?page=cpt-builder-taxonomies&deleted=1')
    );

    exit;
}

add_action('admin_init', 'cpt_builder_delete_taxonomy');



function cpt_builder_edit_taxonomy_page()
{
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $id = $_GET['id'];
    $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';

    $taxonomy = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        )
    );

    if (!$taxonomy) {
        wp_die('Taxonomy not found.');
    }
    ?>

    <div class="wrap">

        <h1>Edit Taxonomy</h1>

        <form method="post">

            <?php
           
            ?>

            <table class="form-table">

                <tr>
                    <th>
                        <label for="taxonomy_name">
                            Taxonomy Name
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            name="taxonomy_name"
                            id="taxonomy_name"
                            class="regular-text"
                            value="<?php echo esc_attr($taxonomy->taxonomy_name); ?>"
                            required
                        >
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="taxonomy">
                            Taxonomy Slug
                        </label>
                    </th>

                    <td>
                        <input
                            type="text"
                            name="taxonomy"
                            id="taxonomy"
                            class="regular-text"
                            value="<?php echo esc_attr($taxonomy->taxonomy); ?>"
                            readonly
                        >

                        <p class="description">
                            Taxonomy slug cannot be changed.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="description">
                            Description
                        </label>
                    </th>

                    <td>
                        <textarea
                            name="description"
                            id="description"
                            rows="5"
                            class="large-text"
                        ><?php echo esc_textarea($taxonomy->description); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="post_type">
                            Assign to CPT
                        </label>
                    </th>

                    <td>

                        <select name="post_type" id="post_type" required>

                            <?php

                            $cpt_table = $wpdb->prefix . 'cpt_builder';

                            $cpts = $wpdb->get_results(
                                "SELECT * FROM $cpt_table ORDER BY post_type_name ASC"
                            );

                            foreach ($cpts as $cpt) :
                            ?>

                                <option
                                    value="<?php echo esc_attr($cpt->post_type); ?>"
                                    <?php selected(
                                        $taxonomy->post_type,
                                        $cpt->post_type
                                    ); ?>
                                >
                                    <?php echo esc_html($cpt->post_type_name); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </td>
                </tr>

            </table>

            <p>
                <button
                    type="submit"
                    name="update_taxonomy"
                    class="button button-primary"
                >
                    Update Taxonomy
                </button>

                <a
                    href="<?php echo esc_url(
                        admin_url('admin.php?page=cpt-builder-taxonomies')
                    ); ?>"
                    class="button"
                >
                    Cancel
                </a>
            </p>

        </form>

    </div>

    <?php
}