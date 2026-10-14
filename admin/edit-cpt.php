
<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_delete_cpt()
{
    if (($_GET['action'] ?? '') !== 'delete-cpt') {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to delete CPTs.');
    }

    check_admin_referer('cpt_builder_delete_cpt');

    $cpt_id = absint($_GET['id'] ?? 0);

    if (!$cpt_id) {
        wp_die('Invalid CPT ID.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'cpt_builder';

    $deleted = $wpdb->delete(
        $table_name,
        array('id' => $cpt_id),
        array('%d')
    );

    if ($deleted === false) {
        wp_die('Database delete failed: ' . $wpdb->last_error);
    }

    wp_safe_redirect(admin_url('admin.php?page=cpt-builder&deleted=1'));
    exit;
}

add_action('admin_init', 'cpt_builder_delete_cpt');


function cpt_builder_update_cpt()
{
    if (!isset($_POST['update_cpt'])) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to update CPTs.');
    }

    check_admin_referer('cpt_builder_update_cpt');

    global $wpdb;

    $cpt_table    = $wpdb->prefix . 'cpt_builder';
    $fields_table = $wpdb->prefix . 'cpt_builder_fields';

    $cpt_id = absint($_POST['cpt_id'] ?? 0);

    $existing_cpt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $cpt_table WHERE id = %d",
            $cpt_id
        )
    );

    if (!$existing_cpt) {
        wp_die('CPT not found.');
    }

    $old_post_type = $existing_cpt->post_type;

    $post_name = $_POST['post_type_name'];

    $post_type = $_POST['post_type'] ;

    $tag_field_name = $_POST['tag_field_name'];

    $description = $_POST['description'];

  
   
    $duplicate = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $cpt_table
             WHERE post_type = %s AND id != %d",
            $post_type,
            $cpt_id
        )
    );

    if ($duplicate) {
        wp_die('This post type slug is already in use.');
    }

    $updated = $wpdb->update(
        $cpt_table,
        array(
            'post_type_name' => $post_name,
            'post_type'      => $post_type,
            'tag_field_name' => $tag_field_name,
            'description'    => $description,
        ),
        array('id' => $cpt_id),
        array('%s', '%s', '%s', '%s'),
        array('%d')
    );

    if ($updated === false) {
        wp_die('CPT update failed: ' . $wpdb->last_error);
    }

        $wpdb->update(
            $fields_table,
            array('post_type' => $post_type),
            array('post_type' => $old_post_type),
            array('%s'),
            array('%s')
        );

        $taxonomy_table = $wpdb->prefix . 'cpt_builder_taxonomies';

        $wpdb->update(
            $taxonomy_table,
            array('post_type' => $post_type),
            array('post_type' => $old_post_type),
            array('%s'),
            array('%s')
        );
    


    $submitted_fields =$_POST['custom_fields'];

    $saved_ids = array();

    $allowed_types = array(
        'text',
        'email',
        'number',
        'textarea',
        'date',
        'select',
        'checkbox',
        'url',
        'tel',
        'color',
        'time',
    );

    foreach ($submitted_fields as $submitted_field) {
        if (!is_array($submitted_field)) {
            continue;
        }

        $field_id = absint($submitted_field['id'] ?? 0);

        $field_label = sanitize_text_field(
            $submitted_field['field_label'] ?? ''
        );

        $field_name = sanitize_key(
            $submitted_field['field_name'] ?? ''
        );

        $field_type = sanitize_key(
            $submitted_field['field_type'] ?? 'text'
        );

        $required = !empty($submitted_field['required']);

        if ($field_label === '' || $field_name === '') {
            continue;
        }

        if (!in_array($field_type, $allowed_types, true)) {
            $field_type = 'text';
        }

        $raw_options = isset($submitted_field['options'])
            ? sanitize_textarea_field($submitted_field['options'])
            : '';

        $options = array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $raw_options)),
            'strlen'
        );

        $field_options = wp_json_encode(array(
            'options'  => array_values($options),
            'required' => $required,
        ));

        $field_data = array(
            'post_type'     => $post_type,
            'field_name'    => $field_name,
            'field_label'   => $field_label,
            'field_type'    => $field_type,
            'field_options' => $field_options,
        );

        if ($field_id) {
     
            $belongs_to_cpt = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $fields_table
                     WHERE id = %d AND post_type = %s",
                    $field_id,
                    $post_type
                )
            );

            if ($belongs_to_cpt) {
                $result = $wpdb->update(
                    $fields_table,
                    $field_data,
                    array(
                        'id'        => $field_id,
                        'post_type' => $post_type,
                    ),
                    array('%s', '%s', '%s', '%s', '%s'),
                    array('%d', '%s')
                );

                if ($result === false) {
                    wp_die('Custom field update failed: ' . $wpdb->last_error);
                }

                $saved_ids[] = $field_id;
                continue;
            }
        }

 
        $inserted = $wpdb->insert(
            $fields_table,
            $field_data,
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            wp_die('Custom field insert failed: ' . $wpdb->last_error);
        }

        $saved_ids[] = (int) $wpdb->insert_id;
    }

    if ($saved_ids) {
        $id_placeholders = implode(
            ', ',
            array_fill(0, count($saved_ids), '%d')
        );

        $query = $wpdb->prepare(
            "DELETE FROM $fields_table
             WHERE post_type = %s
             AND id NOT IN ($id_placeholders)",
            array_merge(array($post_type), $saved_ids)
        );

        $deleted_fields = $wpdb->query($query);
    } else {
        $deleted_fields = $wpdb->delete(
            $fields_table,
            array('post_type' => $post_type),
            array('%s')
        );
    }

    if ($deleted_fields === false) {
        wp_die('Custom field cleanup failed: ' . $wpdb->last_error);
    }

    wp_safe_redirect(admin_url('admin.php?page=cpt-builder&updated=1'));
    exit;
}

add_action('admin_init', 'cpt_builder_update_cpt');

function cpt_builder_edit_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to edit CPTs.');
    }

    global $wpdb;

    $table_name  = $wpdb->prefix . 'cpt_builder';
    $fields_table = $wpdb->prefix . 'cpt_builder_fields';

    $id = absint($_GET['id'] ?? 0);

    if (!$id) {
        wp_die('Invalid CPT ID.');
    }

    $cpt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        )
    );

    if (!$cpt) {
        wp_die('CPT not found.');
    }

    $custom_fields = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $fields_table
             WHERE post_type = %s
             ORDER BY id ASC",
            $cpt->post_type
        )
    );
    ?>

    <div class="wrap">
        <h1>Edit Custom Post Type</h1>

        <form method="post">
            <?php wp_nonce_field('cpt_builder_update_cpt'); ?>

            <input type="hidden" name="cpt_id"
                   value="<?php echo esc_attr($cpt->id); ?>">

            <h2>CPT Details</h2>

            <table class="form-table">
                <tr>
                    <th><label for="post_type_name">Post Type Name</label></th>
                    <td>
                        <input type="text" name="post_type_name"
                               id="post_type_name" class="regular-text"
                               value="<?php echo esc_attr($cpt->post_type_name); ?>"
                               required>
                    </td>
                </tr>

                <tr>
                    <th><label for="post_type">Post Type Slug</label></th>
                    <td>
                        <input type="text" name="post_type"
                               id="post_type" class="regular-text"
                               value="<?php echo esc_attr($cpt->post_type); ?>"
                               required>
                        <p class="description">
                            Use lowercase letters, numbers and underscores.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label for="tag_field_name">Tag Field Name</label></th>
                    <td>
                        <input type="text" name="tag_field_name"
                               id="tag_field_name" class="regular-text"
                               value="<?php echo esc_attr($cpt->tag_field_name ?? 'Tags'); ?>"
                               required>
                    </td>
                </tr>

                <tr>
                    <th><label for="description">Description</label></th>
                    <td>
                        <textarea name="description" id="description"
                                  rows="5" class="large-text"><?php
                            echo esc_textarea($cpt->description);
                        ?></textarea>
                    </td>
                </tr>
            </table>

            <hr>

            <h2>Custom Field Definitions</h2>
            <p>Edit existing fields, add new ones, or remove fields you no longer need.</p>

            <div id="cpt-custom-fields">
                <?php foreach ($custom_fields as $index => $field) :
                    $config = json_decode($field->field_options, true);

                    if (!is_array($config)) {
                        $config = array();
                    }

                    $options = isset($config['options']) && is_array($config['options'])
                        ? $config['options']
                        : array();

                    $required = !empty($config['required']);
                    ?>

                    <div class="cpt-custom-field-row" style="border:1px solid #ccd0d4;padding:15px;margin:15px 0;background:#fff;">
                        <input type="hidden"
                               name="custom_fields[<?php echo esc_attr($index); ?>][id]"
                               value="<?php echo esc_attr($field->id); ?>">

                        <p>
                            <label>Field Label<br>
                                <input type="text"
                                       name="custom_fields[<?php echo esc_attr($index); ?>][field_label]"
                                       value="<?php echo esc_attr($field->field_label); ?>"
                                       required class="regular-text">
                            </label>
                        </p>

                        <p>
                            <label>Field Name<br>
                                <input type="text"
                                       name="custom_fields[<?php echo esc_attr($index); ?>][field_name]"
                                       value="<?php echo esc_attr($field->field_name); ?>"
                                       required class="regular-text">
                            </label>
                        </p>

                        <p>
                            <label>Field Type<br>
                                <select class="cpt-field-type"
                                        name="custom_fields[<?php echo esc_attr($index); ?>][field_type]">
                                    <?php
                                    $types = array(
                                        'text' => 'Text',
                                        'email' => 'Email',
                                        'number' => 'Number',
                                        'textarea' => 'Textarea',
                                        'date' => 'Date',
                                        'select' => 'Select',
                                        'radio' => 'Radio',
                                        'checkbox' => 'Checkbox',
                                        'url' => 'URL',
                                        'tel' => 'Telephone',
                                        'color' => 'Color',
                                        'time' => 'Time',
                                    );

                                    foreach ($types as $type => $label) :
                                    ?>
                                        <option value="<?php echo esc_attr($type); ?>"
                                            <?php selected($field->field_type, $type); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </p>

                        <p class="cpt-field-options"
                           style="<?php echo in_array($field->field_type, array('select', 'radio', 'checkbox'), true) ? '' : 'display:none;'; ?>">
                            <label>Options (one per line)<br>
                                <textarea
                                    name="custom_fields[<?php echo esc_attr($index); ?>][options]"
                                    rows="4" class="large-text"><?php
                                    echo esc_textarea(implode("\n", $options));
                                ?></textarea>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox"
                                       name="custom_fields[<?php echo esc_attr($index); ?>][required]"
                                       value="1"
                                       <?php checked($required); ?>>
                                Required
                            </label>
                        </p>

                        <button type="button" class="button cpt-remove-field">
                            Remove Field
                        </button>
                    </div>

                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" id="cpt-add-field" class="button">
                    Add Custom Field
                </button>
            </p>

            <p>
                <button type="submit" name="update_cpt"
                        class="button button-primary">
                    Update CPT
                </button>
            </p>
        </form>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('cpt-custom-fields');
        const addButton = document.getElementById('cpt-add-field');

        let nextIndex = <?php echo (int) count($custom_fields); ?>;

        function updateOptionsVisibility(row) {
            const type = row.querySelector('.cpt-field-type').value;
            const options = row.querySelector('.cpt-field-options');

            options.style.display = ['select', 'radio', 'checkbox'].includes(type)
                ? ''
                : 'none';
        }

        function createFieldRow(index) {
            const row = document.createElement('div');
            row.className = 'cpt-custom-field-row';
            row.style.cssText = 'border:1px solid #ccd0d4;padding:15px;margin:15px 0;background:#fff;';

            row.innerHTML = `
                <input type="hidden" name="custom_fields[${index}][id]" value="">

                <p>
                    <label>Field Label<br>
                        <input type="text" name="custom_fields[${index}][field_label]"
                               required class="regular-text">
                    </label>
                </p>

                <p>
                    <label>Field Name<br>
                        <input type="text" name="custom_fields[${index}][field_name]"
                               required class="regular-text">
                    </label>
                </p>

                <p>
                    <label>Field Type<br>
                        <select class="cpt-field-type"
                                name="custom_fields[${index}][field_type]">
                            <option value="text">Text</option>
                            <option value="email">Email</option>
                            <option value="number">Number</option>
                            <option value="textarea">Textarea</option>
                            <option value="date">Date</option>
                            <option value="select">Select</option>
                            <option value="radio">Radio</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="url">URL</option>
                            <option value="tel">Telephone</option>
                            <option value="color">Color</option>
                            <option value="time">Time</option>
                        </select>
                    </label>
                </p>

                <p class="cpt-field-options" style="display:none;">
                    <label>Options (one per line)<br>
                        <textarea name="custom_fields[${index}][options]"
                                  rows="4" class="large-text"></textarea>
                    </label>
                </p>

                <p>
                    <label>
                        <input type="checkbox"
                               name="custom_fields[${index}][required]" value="1">
                        Required
                    </label>
                </p>

                <button type="button" class="button cpt-remove-field">
                    Remove Field
                </button>
            `;

            return row;
        }

        addButton.addEventListener('click', function () {
            const row = createFieldRow(nextIndex++);
            container.appendChild(row);
            updateOptionsVisibility(row);
        });

        container.addEventListener('click', function (event) {
            if (event.target.classList.contains('cpt-remove-field')) {
                event.target.closest('.cpt-custom-field-row').remove();
            }
        });

        container.addEventListener('change', function (event) {
            if (event.target.classList.contains('cpt-field-type')) {
                updateOptionsVisibility(event.target.closest('.cpt-custom-field-row'));
            }
        });

        container.querySelectorAll('.cpt-custom-field-row').forEach(updateOptionsVisibility);
    });
    </script>

    <?php
}
