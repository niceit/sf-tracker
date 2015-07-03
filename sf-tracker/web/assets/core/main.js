/**
 * Process main requests
 * functionally and predefined
 */

if (typeof ('jQuery') == 'undefined') {
    alert ('jQuery library is missing or too old');
}
else {
    function AdvancedTrackers() {
        this.ajax_content_wrapper = '.layout-wrapper';

        /* Process loading box */
        this.progressLoading = function (loading_string, container) {
            if (container != '') {
                var wrap_element = container;
            }
            else var wrap_element = 'body';

            /* Check if loading element is existed or not */
            if (!($('.progress-loading').length > 0)) {
                var html_content = '<div class="progress-loading">' +
                                        '<div class="bg-overlay"></div>' +
                                        '<div class="loading-content-container">' +
                                            '<div class="loading-content">' + loading_string + '</div>' +
                                            '<div class="loader-img"></div>' +
                                        '</div>' +
                                    '</div>';
                $('body').append (html_content);
            }

             $('.progress-loading').hide().appendTo (wrap_element).fadeIn(1000);
        }

        /* Hide loading */
        this.hideProcessLoading = function (){
             $('.progress-loading').fadeOut(1000);
        }
    }
}
var advancedTracker = new AdvancedTrackers();