<?php

if (!defined('ABSPATH')) {
    exit;
}

function form_page()
{
    $errors = array();
    $success = false;

    if (isset($_POST['create_cpt'])) {

       

        check_admin_referer('cpt_builder_create_cpt');
        $post_type_name = $_POST['post_type_name'] ;
        $post_type =$_POST['post_type'] ;
        $tag_field_name =$_POST['tag_field_name'];
        $description = $_POST['description'];
        $custom_fields =$_POST['custom_fields'];

        $allowed_types = array(
            'text',
            'email',
            'number',
            'textarea',
            'date',
            'select',
            'radio',
            'checkbox'
        );

     
        // Validate custom field 
        $clean_fields = array();
    
        foreach ($custom_fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $label = sanitize_text_field($field['field_label'] ?? '');
            $name = sanitize_key($field['field_name'] ?? '');
            $type = sanitize_key($field['field_type'] ?? 'text');
            $required = !empty($field['required']) ? 1 : 0;
            $options_text = sanitize_textarea_field(
                $field['field_options'] ?? ''
            );
            $options = array();
          if (in_array($type, array('select', 'radio', 'checkbox'), true))  {
                $option_lines = preg_split('/\r\n|\r|\n/', $options_text);
                foreach ($option_lines as $option) {
                    $option = sanitize_text_field($option);

                    if ($option !== '') {
                        $options[] = $option;
                    }
                }
                if (empty($options)) {
                    $errors[] = 'Select and Radio fields need at least one option.';
                    continue;
                }
            }
            $clean_fields[] = array(
                'field_name' => $name,
                'field_label' => $label,
                'field_type' => $type,
                'field_options' => wp_json_encode(array(
                    'options' => $options,
                    'required' => $required
                ))
            );
        }
        if (empty($errors)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'cpt_builder';
            $fields_table = $wpdb->prefix . 'cpt_builder_fields';

            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE post_type = %s",
                    $post_type
                )
            );
            if ($existing) {

                $errors[] = 'This Post Type already exists.';

            } else {
                $inserted = $wpdb->insert(
                    $table_name,
                    array(
                        'post_type' => $post_type,
                        'post_type_name' => $post_type_name,
                        'tag_field_name' => $tag_field_name,
                        'description' => $description,
                        'created_by' => get_current_user_id(),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%d', '%s')
                );
                if ($inserted) {
                    $saved_all_fields = true;
                    foreach ($clean_fields as $field) {
                        $saved = $wpdb->insert(
                            $fields_table,
                            array(
                                'post_type' => $post_type,
                                'field_name' => $field['field_name'],
                                'field_label' => $field['field_label'],
                                'field_type' => $field['field_type'],
                                'field_options' => $field['field_options'],
                                'created_at' => current_time('mysql')
                            ),
                            array('%s', '%s', '%s', '%s', '%s', '%s')
                        );
                        if ($saved === false) {
                            $saved_all_fields = false;
                            break;
                        }
                    }
                    if ($saved_all_fields) {
                        $success = true;
                    } else {
                        $errors[] = 'The CPT was created, but some fields could not be saved. Check the database table structure.';
                    }

                } else {
                    $errors[] = 'Unable to create the Custom Post Type.';
                }
            }
        }
    }
    ?>

    <div class="wrap">
        <h1>Add New Custom Post Type</h1>

        <?php foreach ($errors as $error): ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error); ?></p>
            </div>
        <?php endforeach; ?>

        <?php if ($success): ?>
            <div class="notice notice-success">
                <p>Custom Post Type and its custom fields created successfully!</p>
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field('cpt_builder_create_cpt'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="post_type_name">Post Type Name</label></th>
                    <td>
                        <input type="text" name="post_type_name" id="post_type_name" class="regular-text" required>
                    </td>
                </tr>

                <tr>
                    <th><label for="post_type">Post Type Slug</label></th>
                    <td>
                        <input type="text" name="post_type" id="post_type" class="regular-text" required>
                    </td>
                </tr>

                <tr>
                    <th><label for="tag_field_name">Tag Field Name</label></th>
                    <td>
                        <input type="text" name="tag_field_name" id="tag_field_name" class="regular-text"
                            placeholder="e.g. Hashtags" required>
                        <p class="description">
                            Enter the name to display for this post type's tag field.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th><label for="description">Description</label></th>
                    <td>
                        <textarea name="description" id="description" rows="5" class="large-text"></textarea>
                    </td>
                </tr>
            </table>

            <hr>

            <h2>Custom Input Fields</h2>
            <p>Add the fields that should appear when a post of this type is created.</p>

            <div id="cpt-custom-fields"></div>

            <p>
                <button type="button" id="add-custom-field" class="button">
                    + Add Input Field
                </button>
            </p>

            <p>
                <button type="submit" name="create_cpt" class="button button-primary">
                    Create CPT
                </button>
            </p>
        </form>
    </div>

    <script>
        jQuery(function ($) {

            let fieldIndex = 0;

            const fieldTypes = {
                text: 'Text',
                email: 'Email',
                number: 'Number',
                textarea: 'Textarea',
                date: 'Date',
                select: 'Select',
                radio: 'Radio',
                checkbox: 'Checkbox'
            };
function updateOptions(row) {
    const type = row.find('.field-type').val();
    const optionsBox = row.find('.field-options-box');

    if (['select', 'radio', 'checkbox'].includes(type)) {
        optionsBox.show();
    } else {
        optionsBox.hide();
    }
}

            $('#add-custom-field').on('click', function () {

                const index = fieldIndex++;

                const typeOptions = Object.entries(fieldTypes)
                    .map(([value, label]) =>
                        `<option value="${value}">${label}</option>`
                    ).join('');

                const row = $(`
                <div class="cpt-custom-field-row"
                     style="border:1px solid #ccd0d4; padding:15px; margin:15px 0; background:#fff;">

                    <h3>Custom Field</h3>

                    <p>
                        <label>Field Label<br>
                            <input type="text"
                                name="custom_fields[${index}][field_label]"
                                class="regular-text field-label"
                                placeholder="e.g. Student Name" required>
                        </label>
                    </p>

                    <p>
                        <label>Field Name<br>
                            <input type="text"
                                name="custom_fields[${index}][field_name]"
                                class="regular-text field-name"
                                placeholder="e.g. student_name" required>
                        </label>
                        <br>
                        <span class="description">
                            Use a unique name containing letters, numbers, or underscores.
                        </span>
                    </p>

                    <p>
                        <label>Field Type<br>
                            <select name="custom_fields[${index}][field_type]"
                                class="field-type">
                                ${typeOptions}
                            </select>
                        </label>
                    </p>

                    <p class="field-options-box">
                        <label>Options (one per line)<br>
                            <textarea
                                name="custom_fields[${index}][field_options]"
                                rows="4" class="large-text"
                                placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox"
                                name="custom_fields[${index}][required]"
                                value="1">
                            Required field
                        </label>
                    </p>

                    <button type="button" class="button remove-custom-field">
                        Remove Field
                    </button>
                </div>
            `);

                $('#cpt-custom-fields').append(row);
                updateOptions(row);
            });

            $('#cpt-custom-fields').on('change', '.field-type', function () {
                updateOptions($(this).closest('.cpt-custom-field-row'));
            });

            $('#cpt-custom-fields').on('input', '.field-label', function () {
                const row = $(this).closest('.cpt-custom-field-row');
                const nameInput = row.find('.field-name');

                if (!nameInput.data('manually-edited')) {
                    const generated = $(this).val()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');

                    nameInput.val(generated);
                }
            });

            $('#cpt-custom-fields').on('input', '.field-name', function () {
                $(this).data('manually-edited', true);
            });

            $('#cpt-custom-fields').on('click', '.remove-custom-field', function () {
                $(this).closest('.cpt-custom-field-row').remove();
            });
        });
    </script>

    <?php
}
