<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_search_posts()
{
   

    if (!is_user_logged_in()) {
        wp_send_json_error('Please log in first.');
    }

    $search = $_POST['search'];
    $sort = $_POST['sort'];
    $post_type = $_POST['post_type'];
    $orderby = 'date';
    $order = 'DESC';

     if($sort === 'oldest'){
        $order = 'ASC';
     }elseif($sort === 'a-z'){
        $orderby = 'title';
        $order = 'ASC';
     }elseif($sort === 'z-a'){
        $orderby = 'title';
        $order = 'DESC';
     }



    $args = array(
        'post_type' => $post_type,
        'post_status' => array('publish', 'pending', 'draft'),
        'posts_per_page' => -1,
        's' => $search,
        'orderby' => $orderby,
        'order' => $order,
        'author' => get_current_user_id(),
    );

     $posts = new WP_Query($args);

    ob_start();

    if ($posts->have_posts()) {
        while ($posts->have_posts()) {
            $posts->the_post();
            ?>
            <div class="cpt-item">
                <h3> <a href="<?php the_permalink(); ?>" class="cpt-item-link">
                    <?php the_title(); ?>
                </a></h3>
                <p><?php echo esc_html(get_post_status()); ?></p>
                <p><?php echo esc_html(get_the_date()); ?></p>
                <div><?php the_excerpt(); ?></div>
            </div>
            <?php
        }
    } else {
        echo '<p>No posts found.</p>';
    }

    wp_reset_postdata();

    wp_send_json_success(ob_get_clean());
}

add_action('wp_ajax_cpt_builder_search', 'cpt_builder_search_posts');
