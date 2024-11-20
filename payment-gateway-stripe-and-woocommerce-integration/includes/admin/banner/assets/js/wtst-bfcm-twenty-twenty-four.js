(function ($) {
    'use strict';

    $(function () {
        var wtst_bfcm_twenty_twenty_four_banner = {
            init: function () { 
                var data_obj = {
                    _wpnonce: wtst_bfcm_twenty_twenty_four_banner_js_params.nonce,
                    action: wtst_bfcm_twenty_twenty_four_banner_js_params.action,
                    wtst_bfcm_twenty_twenty_four_banner_action_type: '',
                };

                $(document).on('click', 'wtst-bfcm-banner-2024 .bfcm_cta_button', function (e) { 
                    e.preventDefault(); 
                    var elm = $(this);
                    window.open(wtst_bfcm_twenty_twenty_four_banner_js_params.cta_link, '_blank'); 
                    elm.parents('.wtst-bfcm-banner-2024').hide();
                    data_obj['wtst_bfcm_twenty_twenty_four_banner_action_type'] = 3; // Clicked the button.
                    
                    $.ajax({
                        url: wtst_bfcm_twenty_twenty_four_banner_js_params.ajax_url,
                        data: data_obj,
                        type: 'POST'
                    });
                }).on('click', '.wtst-bfcm-banner-2024 .notice-dismiss', function(e) {
                    e.preventDefault();
                    data_obj['wtst_bfcm_twenty_twenty_four_banner_action_type'] = 2; // Closed by user
                    
                    $.ajax({
                        url: wtst_bfcm_twenty_twenty_four_banner_js_params.ajax_url,
                        data: data_obj,
                        type: 'POST',
                    });
                });
            }
        };
        wtst_bfcm_twenty_twenty_four_banner.init();
    });

})(jQuery);