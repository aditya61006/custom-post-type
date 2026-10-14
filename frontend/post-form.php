<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_set_post_form_error($message)
{
    $GLOBALS['cpt_builder_post_form_error'] = $message;
}



function cpt_builder_handle_post_submit()
{
    if (!isset($_POST['cpt_builder_create_post'])) {
        return;
    }

    if (!is_user_logged_in()) {
        wp_die('You must be logged in to create a post.');
    }

    if (!isset($_POST['cpt_builder_post_nonce']) || !wp_verify_nonce($_POST['cpt_builder_post_nonce'], 'cpt_builder_create_post')) {
        wp_die('Security check failed.');
    }

    if (!current_user_can('edit_posts')) {
        wp_die('You do not have permission to create posts.');
    }

    $post_type = $_POST['cpt_post_type'];

    $title = $_POST['cpt_post_title'];

    $content = $_POST['cpt_post_content'];

    if (empty($post_type) || empty($title) || empty($content)) {
        wp_die('Please fill in all required fields.');
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder';

    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_type = %s",
            $post_type
        )
    );

    if (!$exists || !post_type_exists($post_type)) {
        wp_die('Invalid custom post type.');
    }

    $user_posts = new WP_Query(array(
        'post_type' => $post_type,
        'post_status' => array('publish', 'pending', 'draft', 'future', 'private'),
        'author' => get_current_user_id(),
        'posts_per_page' => 7,
        'fields' => 'ids',
        'no_found_rows' => true
    ));

    if (count($user_posts->posts) >= 6) {
        cpt_builder_set_post_form_error(
            'You have reached the 6-post limit for this post type.'
        );

        return;
    }

    $post_id = wp_insert_post(array('post_type' => $post_type,
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'pending',
        'post_author' => get_current_user_id()), true);

    if (is_wp_error($post_id)) {
        wp_die('Post creation failed. Please try again.');
    }

    wp_safe_redirect(
        add_query_arg('post_created', '1', get_permalink())
    );
    exit;
}

add_action('init', 'cpt_builder_handle_post_submit');


function cpt_builder_post_form()
{
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to create a post.</p>';
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder';

    $cpts = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY post_type_name ASC"
    );

    ob_start();
    ?>

    <div class="cpt-builder-post-form">

        <h2>Create Custom Post</h2>
        <?php if (!empty($GLOBALS['cpt_builder_post_form_error'])): ?>
            <p class="cpt-error">
                <?php echo esc_html($GLOBALS['cpt_builder_post_form_error']); ?>
            </p>
       
        <?php else :?>
            <p class="cpt-success">
                Your post is submitted successfully and it is pending to review.
            </p>
        <?php endif; ?>

        <form method="post">

            <?php wp_nonce_field('cpt_builder_create_post', 'cpt_builder_post_nonce'); ?>

            <p>
                <label for="cpt_post_type">Select Post Type</label>
            </p>

            <select name="cpt_post_type" id="cpt_post_type" required>

                <option value="">Select CPT</option>

                <?php foreach ($cpts as $cpt): ?>

                    <option value="<?php echo esc_attr($cpt->post_type); ?>">
                        <?php echo esc_html($cpt->post_type_name); ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <p>
                <label for="cpt_post_title">Title</label>
            </p>

            <input type="text" name="cpt_post_title" id="cpt_post_title" required>

            <p>
                <label for="cpt_post_content">Content</label>
            </p>

            <textarea name="cpt_post_content" id="cpt_post_content" rows="6" required></textarea>


            <p>
                <small>Your post will be submitted for review before publishing.</small>
            </p>

            <p>
                <button type="submit" name="cpt_builder_create_post">
                    Create Post
                </button>
            </p>
     </form>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('cpt_builder_post_form', 'cpt_builder_post_form');