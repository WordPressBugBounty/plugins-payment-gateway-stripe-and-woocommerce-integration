jQuery( function( $ ) {
	            
    $(".wtst-connect-later").on("click", function(){  
        $.ajax({
        	url: ajaxurl,
        	type: 'post',
        	data: {
        		oauth_connect_later: 'yes',
        		_wpnonce: eh_stripe_oauth_val.nonce,
        		action: 'wtst_oauth_connect_later'
        	},
        	success: function(response) { 
                location.reload(true);
            },
            error: function() { 
                
            }
        });
    });

    $( '#eh_live_mode' ).on( 'click', function(e) { 
        e.preventDefault();
        ConfirmDialog(eh_stripe_oauth_val.disconnect_title, eh_stripe_oauth_val.mode_change_text, eh_stripe_oauth_val.mode_change_primary_btn, eh_stripe_oauth_val.disconnect_secondary_btn_title);

    });

        //User switch to live mode
    $( '#eh_test_mode' ).on( 'click', function(e) { 
        e.preventDefault();
        jQuery('#woocommerce_eh_stripe_pay_eh_stripe_mode').val('live');
        var settingsForm = jQuery('.eh_mainform');                 

        //Delete existing test mode tokens and generate new ones
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                _wpnonce: eh_stripe_oauth_val.nonce,
                action: 'wtst_oauth_disconnect',
                mode: 'live',
                expire: 'access_token'
            },
            success: function(response) {
                settingsForm.submit();
            },
            error: function() {
                alert("Something went wrong!");
                return;
            }
        }); 
    });

    function ConfirmDialog(title, message, confirmButtonText, cancelButtonText) {
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            reverseButtons: true // This will reverse button positions (confirm button will be shown on the left)
        }).then((result) => {
            if (result.isConfirmed) { 
                var test    = jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_test_publishable_key, #woocommerce_eh_stripe_pay_eh_stripe_test_secret_key' ).closest( 'tr' ),
                live = jQuery( '#woocommerce_eh_stripe_pay_eh_stripe_live_publishable_key, #woocommerce_eh_stripe_pay_eh_stripe_live_secret_key' ).closest( 'tr' );
          
                jQuery('#woocommerce_eh_stripe_pay_eh_stripe_mode').val('test');
                var settingsForm = jQuery('.eh_mainform');  

                //Delete existing test mode tokens and generate new ones
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        _wpnonce: eh_stripe_oauth_val.nonce,
                        action: 'wtst_oauth_disconnect',
                        mode: 'test',
                        expire: 'access_token'
                    },
                    success: function(response) { 
                        settingsForm.submit();
                    },
                    error: function() {
                        alert("Something went wrong!");
                        return;
                    }
                }); 

                // Your code to execute when 'Yes' button is clicked
            } else if (result.dismiss === Swal.DismissReason.cancel) {

            }
        });
    };
       
    $("#wtst-deactivate-btn").on("click", function () { 
        //e.preventDefault();
        Swal.fire({
            title: eh_stripe_oauth_val.disconnect_title,
            text: eh_stripe_oauth_val.disconnect_text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: eh_stripe_oauth_val.disconnect_primary_btn_title,
            cancelButtonText: eh_stripe_oauth_val.disconnect_secondary_btn_title,
            reverseButtons: true // This will reverse button positions (confirm button will be shown on the left)
        }).then((result) => {
            if (result.isConfirmed) { 
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        _wpnonce: eh_stripe_oauth_val.nonce,
                        action: 'wtst_oauth_disconnect'
                    },
                    success: function(response) {
                         location.reload(true);
                    },
                    error: function() {
                        
                    }
                });                 
                // Your code to execute when 'Yes' button is clicked
            } else if (result.dismiss === Swal.DismissReason.cancel) {

            }
        });

    })     
});