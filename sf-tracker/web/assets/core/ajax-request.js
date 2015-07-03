$(document).ready (function(){
    $(".ajax-request-btn").click (function(){
        advancedTracker.progressLoading('please wait', '.layout-wrapper');
        var data_handle_url = $(this).attr ('data-url');
        $.get(data_handle_url, function(response){
            var main_content = $(response).find (advancedTracker.ajax_content_wrapper).html();
            advancedTracker.hideProcessLoading();
            setTimeout (function(){ $(advancedTracker.ajax_content_wrapper).html (main_content);  }, 2000);
        });
    });
});