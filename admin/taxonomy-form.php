<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_create_taxonomy()
{
    if (!isset($_POST['create_taxonomy'])) {
        return;
    }


    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';

    $taxonomy_name = $_POST['taxonomy_name'];

    $taxonomy = $_POST['taxonomy'];

    $description = $_POST['description'];

    $post_type = $_POST['post_type'];

    if (empty($taxonomy_name) ||empty($taxonomy) ||empty($post_type)) {
        wp_die('Please fill in all required fields.');
    }

    $existing = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $table_name WHERE taxonomy = %s",
            $taxonomy
        )
    );

    if ($existing) {
        wp_die('This taxonomy already exists.');
    }

    $inserted = $wpdb->insert(
        $table_name,
        array('taxonomy' => $taxonomy, 'taxonomy_name' => $taxonomy_name,
            'description' => $description,
            'post_type' => $post_type,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ),
        array('%s','%s','%s','%s','%d','%s')
    );

    if ($inserted === false) {
        wp_die('Database error: ' . $wpdb->last_error);
    }

    wp_redirect(
        admin_url('admin.php?page=cpt-builder-taxonomies&created=1')
    );

    exit;
}

add_action('admin_init', 'cpt_builder_create_taxonomy');

function cpt_builder_taxonomy_form_page()
{
    // echo "its me taxonomy"; 
    global $wpdb;

    // if (isset($_POST['create_taxonomy'])) {
    //     // echo "<script> alert('taxonomy created') </script>";

    //     $table_name = $wpdb->prefix . 'cpt_builder_taxonomies';
    //     $taxonomy_name = $_POST['taxonomy_name'];
    //     echo $taxonomy_name;
    //     $taxonomy = $_POST['taxonomy'];
    //     $description = $_POST['description'];
    //     $post_type = $_POST['post_type'];

    ?>

    <div class="wrap">
        <h1>Add New Taxonomy</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>
                        <label for="taxonomy_name">
                            Taxonomy Name
                        </label>
                    </th>
                    <td>
                        <input type="text" name="taxonomy_name" id="taxonomy_name" class="regular-text" required>

                        <p class="description">
                            Example: Genre, Brand, Department
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="taxonomy">
                            Taxonomy Slug
                        </label>
                    </th>
                    <td>
                        <input type="text" name="taxonomy" id="taxonomy" class="regular-text" required>
                        <p class="description">
                            Example: genre, brand, department
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
                        <textarea name="description" id="description" rows="5" class="large-text"></textarea>
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
                            <option value="">
                                Select CPT
                            </option>
                            <?php
                            $cpt_table = $wpdb->prefix . 'cpt_builder';
                            $cpts = $wpdb->get_results(
                                "SELECT * FROM $cpt_table ORDER BY post_type_name ASC"
                            );
                         foreach ($cpts as $cpt):
                                ?>
                                <option value="<?php echo esc_attr($cpt->post_type); ?>">
                                    <?php echo esc_html($cpt->post_type_name); ?>
                                </option>

                                <?php
                            endforeach;
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" name="create_taxonomy" class="button button-primary">
                    Create Taxonomy
                </button>
            </p>
        </form>
    </div>

    <?php
}