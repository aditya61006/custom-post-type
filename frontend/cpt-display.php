<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpt_builder_frontend_page()
{

    if (!is_user_logged_in()) {
        wp_die('You must be logged in to access CPT data.');
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'cpt_builder';

    $cpts = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY created_at DESC"
    );
    ?>

    <div class="cpt-builder-frontend">

        <h1>Custom Post Types</h1>
       
<div class="cpt-search-controls">
    

<select id="cpt-post-type">

    <option value="any">All Post Types</option>
<?php foreach ($cpts as $cpt) {?>
    <option value="<?php echo $cpt->post_type_name; ?>"><?php echo $cpt->post_type_name; ?></option>  
    <?php   } ?>
   
</select>


    <input
        type="text"
        id="cpt-search"
        placeholder="Search posts..."
    >

    <select id="cpt-sort">
        <option value="newest">Newest </option>
        <option value="oldest">Oldest </option>
        <option value="a-z"> A–Z</option>
        <option value="z-a"> Z–A</option>
    </select>
</div>

<div id="cpt-search-results">
       </div>

    </div>

    <?php
}


function cpt_builder_data_shortcode()
{
    ob_start();

    cpt_builder_frontend_page();

    return ob_get_clean();
}

add_shortcode('cpt_builder_data', 'cpt_builder_data_shortcode');

