jQuery(document).ready(function($){

    function searchPosts(){
        $.ajax({
            url: cptBuilderAjax.ajax_url,
            type : 'post',
            data: {
                action : 'cpt_builder_search',
                search : $('#cpt-search').val(),
                sort : $('#cpt-sort').val(),
                post_type: $('#cpt-post-type').val(),
            },
            success: function (response){
                if(response.success){
                    $('#cpt-search-results').html(response.data);
                    }
                    else{
                        $('#cpt-search-results').html('<p> Unable to load posts.</p>');
                    }   
            },
            error: function(){
                $('#cpt-search-results').html('<p> Something went wrong</p>');
            } 

        });

    }
    $('#cpt-sort').on('change', searchPosts);
    $('#cpt-post-type').on('change', searchPosts);
    $('#cpt-search').on('input', searchPosts);
    searchPosts();
    
});