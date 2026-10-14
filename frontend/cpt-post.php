<?php

if (!defined('ABSPATH')) {
    exit;
}

function single_post_details($content)
{
    $post_id = get_the_ID();
    $post = get_post($post_id);
    global $wpdb;
    $cpt_table = $wpdb->prefix . 'cpt_builder';
    $cpt = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $cpt_table WHERE post_type = %s",
            $post->post_type
        )
    );

    if (!$cpt) {
        return $content;
    }

    ob_start();
    ?>

    <div class="cpt-builder-post-details"
          data-post-id="<?php echo esc_attr($post_id); ?>"
        data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        data-nonce="<?php echo esc_attr(wp_create_nonce('cpt_builder_save_fields')); ?>">


        <p><strong>Post Type:</strong>
            <?php echo esc_html($cpt->post_type_name); ?>
        </p>

        <p><strong>Author:</strong>
            <?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?>
        </p>

        <p><strong>Date:</strong>
            <?php echo esc_html(get_the_date('', $post_id)); ?>
        </p>

        <p><strong>Status:</strong>
            <?php echo esc_html(ucfirst($post->post_status)); ?>
        </p>

        <h2>Post Content</h2>

        <div class="cpt-post-content">
            <?php echo $content; ?>
        </div>

        <?php

        $taxonomy_table = $wpdb->prefix . 'cpt_builder_taxonomies';

        $taxonomies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT taxonomy, taxonomy_name
                 FROM $taxonomy_table
                 WHERE post_type = %s",
                $post->post_type
            )
        );

        if ($taxonomies) {
            foreach ($taxonomies as $taxonomy) {
                if (!taxonomy_exists($taxonomy->taxonomy)) {
                    continue;

                }
                $terms = get_the_terms($post_id, $taxonomy->taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    ?>
                    <p>
                        <strong><?php echo esc_html($taxonomy->taxonomy_name); ?>:</strong>
                        <?php
                        echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
                        ?>
                    </p>
                    <?php
                }
            }
        }


        $tag_taxonomy = sanitize_key($post->post_type . '_tags');
        if (taxonomy_exists($tag_taxonomy)) {
            $tags = get_the_terms($post_id, $tag_taxonomy);
            if ($tags && !is_wp_error($tags)) {
                ?>
                <p>
                    <strong><?php echo esc_html($cpt->tag_field_name ?: 'Tags'); ?>:</strong>
                    <?php
                    echo esc_html(implode(', ', wp_list_pluck($tags, 'name')));
                    ?>
                </p>
                <?php
            }
        }



        $metadata = get_post_meta($post_id);

        foreach ($metadata as $key => $values) {
            if (strpos($key, '_') === 0) {
                continue;
            }

            $display_values = array();

            foreach ($values as $value) {
                if (is_scalar($value)) {
                    $display_values[] = (string) $value;
                }
            }

            if ($display_values) {
                ?>
                <p>
                    <strong><?php echo esc_html($key); ?>:</strong>
                    <?php echo esc_html(implode(', ', $display_values)); ?>
                </p>
                <?php
            }
        }



        // display  custom input fields 
        $fields_table = $wpdb->prefix . 'cpt_builder_fields';

        $custom_fields = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT field_name, field_label, field_type, field_options
         FROM $fields_table
         WHERE post_type = %s
         ORDER BY created_at ASC",
                $post->post_type
            )
        );

        if (!empty($custom_fields)) {
            echo '<h2>Custom Fields</h2>';

            foreach ($custom_fields as $field) {
                $field_name = sanitize_key($field->field_name);
                $field_label = $field->field_label;
                $field_type = strtolower($field->field_type);


                $meta_key = '_cpt_builder_field_' . $field_name;
                $value = get_post_meta($post_id, $meta_key, true);


                $settings = json_decode($field->field_options, true);
                $options = isset($settings['options']) && is_array($settings['options'])
                    ? $settings['options']
                    : array();

                $required = !empty($settings['required']);


                $allowed_types = array(
                    'text',
                    'number',
                    'email',
                    'url',
                    'tel',
                    'date',
                    'time',
                    'color'
                );

                echo '<div class="cpt-builder-custom-field"
    data-field-name="' . esc_attr($field_name) . '"
    style="margin-bottom:15px;">';

                echo '<label for="' . esc_attr($meta_key) . '" style="display:block;margin-bottom:5px;">';
                echo esc_html($field_label);

                if ($required) {
                    echo ' <span aria-hidden="true">*</span>';
                }

                echo '</label>';

                switch ($field_type) {
                    case 'textarea':
                        echo '<textarea id="' . esc_attr($meta_key) . '" name="cpt_builder_fields[' . esc_attr($field_name) . ']" rows="4" style="width:100%;"' . ($required ? ' required' : '') . '>';
                        echo esc_textarea(is_scalar($value) ? (string) $value : '');
                        echo '</textarea>';
                        break;

                    case 'select':
                        echo '<select id="' . esc_attr($meta_key) . '" name="cpt_builder_fields[' . esc_attr($field_name) . ']" style="width:100%;"' . ($required ? ' required' : '') . '>';
                        echo '<option value="">Select ' . esc_html($field_label) . '</option>';

                        foreach ($options as $option) {
                            if (!is_scalar($option)) {
                                continue;
                            }

                            echo '<option value="' . esc_attr((string) $option) . '"' .
                                selected((string) $value, (string) $option, false) . '>';
                            echo esc_html((string) $option);
                            echo '</option>';
                        }

                        echo '</select>';
                        break;

                    case 'checkbox':
                        $checked_values = is_array($value) ? $value : array($value);

                        foreach ($options as $option) {
                            if (!is_scalar($option)) {
                                continue;
                            }

                            $option = (string) $option;

                            echo '<label style="display:block;">';
                            echo '<input type="checkbox" name="cpt_builder_fields[' . esc_attr($field_name) . '][]" value="' . esc_attr($option) . '"' .
                                checked(in_array($option, $checked_values, true), true, false) . '>';
                            echo ' ' . esc_html($option);
                            echo '</label>';
                        }
                        break;

                    default:
                        $input_type = in_array($field_type, $allowed_types, true)
                            ? $field_type
                            : 'text';

                        echo '<input type="' . esc_attr($input_type) . '" id="' . esc_attr($meta_key) . '" name="cpt_builder_fields[' . esc_attr($field_name) . ']" value="' . esc_attr(is_scalar($value) ? (string) $value : '') . '" style="width:100%;"' . ($required ? ' required' : '') . '>';
                        break;
                }

                echo '</div>';
            }
        }

        ?>

    </div>

    <?php

    return ob_get_clean();
}


function save_custom_field_ajax()
{
    check_ajax_referer('cpt_builder_save_fields', 'nonce');



    $post_id = $_POST['post_id'];

    $field_name = $_POST['field_name'];
    $post = get_post($post_id);

    global $wpdb;

    $fields_table = $wpdb->prefix . 'cpt_builder_fields';

    $field = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT field_name, field_type, field_options
             FROM $fields_table
             WHERE post_type = %s AND field_name = %s",
            $post->post_type,
            $field_name
        )
    );

    if (!$field) {
        wp_send_json_error(array('message' => 'Invalid custom field.'), 400);
    }

    if (!isset($_POST['value'])) {
        wp_send_json_error(array('message' => 'Missing field value.'), 400);
    }

    $raw_value = json_decode(
        wp_unslash($_POST['value']),
        true
    );

    $settings = json_decode($field->field_options, true);

    if (!is_array($settings)) {
        $settings = array();
    }

    $options = isset($settings['options']) && is_array($settings['options'])
        ? array_map('strval', $settings['options'])
        : array();

    $type = strtolower($field->field_type);
    $required = !empty($settings['required']);

    // Validate and sanitize checkbox values.
    if ($type === 'checkbox') {
        if (!is_array($raw_value)) {
            wp_send_json_error(array('message' => 'Invalid checkbox value.'), 400);
        }

        $value = array_map('sanitize_text_field', $raw_value);
        $value = array_values(array_unique($value));

        foreach ($value as $item) {
            if (!in_array($item, $options, true)) {
                wp_send_json_error(array('message' => 'Invalid option.'), 400);
            }
        }

        if ($required && empty($value)) {
            wp_send_json_error(array('message' => 'Select at least one option.'), 400);
        }
    } else {
        if (!is_string($raw_value) && !is_numeric($raw_value)) {
            wp_send_json_error(array('message' => 'Invalid field value.'), 400);
        }

        $raw_value = (string) $raw_value;

        if ($required && trim($raw_value) === '') {
            wp_send_json_error(array('message' => 'This field is required.'), 400);
        }

        switch ($type) {
            case 'email':
                $value = sanitize_email($raw_value);

                if ($value !== '' && !is_email($value)) {
                    wp_send_json_error(array('message' => 'Invalid email.'), 400);
                }
                break;

            case 'number':
                if ($raw_value !== '' && !is_numeric($raw_value)) {
                    wp_send_json_error(array('message' => 'Enter a valid number.'), 400);
                }
                $value = $raw_value;
                break;

            case 'url':
                $value = esc_url_raw($raw_value);

                if ($raw_value !== '' && $value === '') {
                    wp_send_json_error(array('message' => 'Invalid URL.'), 400);
                }
                break;

            case 'select':
                if ($raw_value !== '' && !in_array($raw_value, $options, true)) {
                    wp_send_json_error(array('message' => 'Invalid option.'), 400);
                }
                $value = sanitize_text_field($raw_value);
                break;

            case 'textarea':
                $value = sanitize_textarea_field($raw_value);
                break;

            case 'date':
                $date = DateTime::createFromFormat('!Y-m-d', $raw_value);

                if (
                    $raw_value !== '' &&
                    (!$date || $date->format('Y-m-d') !== $raw_value)
                ) {
                    wp_send_json_error(array('message' => 'Invalid date.'), 400);
                }
                $value = $raw_value;
                break;

            default:
                $value = sanitize_text_field($raw_value);
                break;
        }
    }

    update_post_meta(
        $post_id,
        '_cpt_builder_field_' . $field_name,
        $value
    );

    wp_send_json_success(array('message' => 'Saved successfully.'));
}

add_action('wp_ajax_cpt_builder_save_custom_field', 'save_custom_field_ajax');


add_filter('the_content', 'single_post_details', 20);



add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('jquery');
});

add_action('wp_footer', 'cpt_builder_custom_field_autosave_script');

function cpt_builder_custom_field_autosave_script()
{
    if (is_admin() || !is_singular()) {
        return;
    }
    ?>
        <script>
        jQuery(function ($) {

            $('.cpt-builder-post-details').each(function () {
                const container = $(this);
                const timers = new WeakMap();

                function saveField(fieldBox) {
                    const postId = container.data('post-id');
                    const fieldName = fieldBox.data('field-name');
                    const inputs = fieldBox.find('input, select, textarea');

                    if (!inputs.length) {
                        return;
                    }

                    let value;

                    if (inputs.first().attr('type') === 'checkbox') {
                        value = inputs
                            .filter(':checked')
                            .map(function () {
                                return this.value;
                            })
                            .get();
                    } else {
                        value = inputs.first().val();
                    }

                    let status = fieldBox.find('.cpt-field-save-status');

                    if (!status.length) {
                        status = $('<small>', {
                            class: 'cpt-field-save-status'
                        }).css('display', 'block');

                        fieldBox.append(status);
                    }

                    status.text('Saving...').css('color', '');

                    $.ajax({
                        url: container.data('ajax-url'),
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'cpt_builder_save_custom_field',
                            nonce: container.data('nonce'),
                            post_id: postId,
                            field_name: fieldName,
                            value: JSON.stringify(value)
                        }
                    })
                    .done(function (response) {
                        if (response.success) {
                            status.text('Saved').css('color', 'green');
                        } else {
                            status.text(
                                response.data && response.data.message
                                    ? response.data.message
                                    : 'Could not save.'
                            ).css('color', 'red');
                        }
                    })
                    .fail(function (xhr) {
                        console.error(
                            'CPT Builder auto-save error:',
                            xhr.status,
                            xhr.responseText
                        );

                        let message = 'Save failed. Check Console.';

                        if (xhr.responseJSON &&
                            xhr.responseJSON.data &&
                            xhr.responseJSON.data.message) {
                            message = xhr.responseJSON.data.message;
                        } else if (xhr.responseText === '-1') {
                            message = 'Security check failed. Refresh the page.';
                        }

                        status.text(message).css('color', 'red');
                    });
                }

                // Text fields save after the user pauses typing.
                container.on(
                    'input',
                    'input:not([type="checkbox"]):not([type="radio"]):not([type="date"]):not([type="time"]):not([type="color"]), textarea',
                    function () {
                        const fieldBox = $(this).closest(
                            '.cpt-builder-custom-field'
                        )[0];

                        if (!fieldBox) {
                            return;
                        }

                        clearTimeout(timers.get(fieldBox));

                        timers.set(fieldBox, setTimeout(function () {
                            saveField($(fieldBox));
                        }, 700));
                    }
                );

                // Dropdowns, checkboxes, dates, times and colors save on change.
                container.on(
                    'change',
                    'select, input[type="checkbox"], input[type="radio"], input[type="date"], input[type="time"], input[type="color"]',
                    function () {
                        const fieldBox = $(this).closest(
                            '.cpt-builder-custom-field'
                        )[0];

                        if (!fieldBox) {
                            return;
                        }

                        clearTimeout(timers.get(fieldBox));
                        saveField($(fieldBox));
                    }
                );
            });

        });
        </script>
        <?php
}
