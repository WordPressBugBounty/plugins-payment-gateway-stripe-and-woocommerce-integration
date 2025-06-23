jQuery(function ($) {
    'use strict';
  
  
    var stripe = Stripe(eh_payment_request_params.key, {apiVersion: eh_payment_request_params.version});
  
    var eh_payment_request_gen = {
  
      eh_generate_cart: function () {
  
        var data = {
          '_wpnonce': eh_payment_request_params.eh_payment_request_cart_nonce
        };
        $( document ).trigger("wt-stripe-payment_request_button_cart-data", data);          
        $.ajax({
          type: 'POST',
          data:data,
          url: eh_payment_request_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_gen_payment_request_button_cart"),
          success: function (response) {
            eh_payment_request_gen.startPaymentRequest( response );
          }
        });
      }, 
  
      ProcessPaymentMethod: function( PaymentMethod ) {
  
        var data = eh_payment_request_gen.OrderDetails( PaymentMethod );
  
          return  $.ajax( {
            type:    'POST',
            data:    data,
          dataType: 'json',
          url: eh_payment_request_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_gen_payment_request_create_order"),
        } );
  
      },
  
      OrderDetails: function( evt ) {
  
              //var payment_method   = evt.paymentMethod;
              var email            = evt.billingDetails.email;
              var phone            = evt.billingDetails.phone;
              var billing          = evt.billingDetails.address;              
              var name             = evt.billingDetails.name;
              var shipping         = evt.shippingAddress;
              var data = {
               _wpnonce:                   eh_payment_request_params.eh_checkout_nonce,
                  billing_first_name:        null !== name ? name.split( ' ' ).slice( 0, 1 ).join( ' ' ) : '',
                  billing_last_name:         null !== name ? name.split( ' ' ).slice( 1 ).join( ' ' ) : '',
                  billing_company:           '',
                  billing_email:             null !== email   ? email : evt.payerEmail,
                  billing_phone:             null !== phone   ? phone : evt.payerPhone.replace( '/[() -]/g', '' ),
                  billing_country:           null !== billing ? billing.country : '',
                  billing_address_1:         null !== billing ? billing.line1 : '',
                  billing_address_2:         null !== billing ? billing.line2 : '',
                  billing_city:              null !== billing ? billing.city : '',
                  billing_state:             null !== billing ? billing.state : '',
                  billing_postcode:          null !== billing ? billing.postal_code : '',
                  shipping_first_name:       '',
                  shipping_last_name:        '',
                  shipping_company:          '',
                  shipping_country:          '',
                  shipping_address_1:        '',
                  shipping_address_2:        '',
                  shipping_city:             '',
                  shipping_state:            '',
                  shipping_postcode:         '',
                  shipping_method:           [ null === evt.shippingRate ? null : evt.shippingRate.id ],
                  order_comments:            '',
                  payment_method:            'eh_stripe_pay',
                  payment_type:              'express_element',
                  ship_to_different_address: 1,
                  terms:                     1,
                  eh_stripe_pay_token:       null,
              };
  
              if ( shipping ) {
                  data.shipping_first_name = shipping.name.split( ' ' ).slice( 0, 1 ).join( ' ' );
                  data.shipping_last_name  = shipping.name.split( ' ' ).slice( 1 ).join( ' ' );
                  data.shipping_company    = '';//shipping.organization;
                  data.shipping_country    = shipping.address.country;
                  data.shipping_address_1  = 'undefined' === typeof shipping.address.line1 ? '' : shipping.address.line1;
                  data.shipping_address_2  =  'undefined' === typeof shipping.address.line2 ? '' : shipping.address.line2;
                  data.shipping_city       = shipping.address.city;
                  data.shipping_state      = shipping.address.state;
                  data.shipping_postcode   = shipping.address.postal_code;
              }
  
              return data;
          },
      
      startPaymentRequest: function (cart) {
  
        if(eh_payment_request_params.product){
          var paymentdetails = {
            country: eh_payment_request_params.country_code,
            currency: eh_payment_request_params.currency_code,
            total: {
              label: eh_payment_request_params.product_data.total.label,
              amount: parseInt(eh_payment_request_params.product_data.total.amount),
                
            },
            requestPayerName: true,
            requestPayerEmail: true,
            requestPayerPhone: true,
            requestShipping: eh_payment_request_params.product_data.needs_shipping,
            displayItems: eh_payment_request_params.product_data.displayItems,
            
          };
        }else{
          var paymentdetails = {
  
          country: eh_payment_request_params.country_code,
          currency: eh_payment_request_params.currency_code,
          total: {
            label: eh_payment_request_params.label,
            amount: parseInt(cart.total),
              
          },
          displayItems: cart.line_items.displayItems,
          requestPayerName: true,
          requestPayerEmail: true,
          requestPayerPhone: true,
          requestShipping: ('yes' === eh_payment_request_params.needs_shipping) ? true : false,
         
          }
        }
        
        $( document ).trigger("wt-stripe-paymentdetails-data", paymentdetails);
        
        var elementOptions =  {
                                mode: 'payment',
                                amount: paymentdetails.total.amount,
                                currency: eh_payment_request_params.currency_code,
                              };
        var elements = stripe.elements(elementOptions);

        var expressOptions = {
          paymentMethods : {
            googlePay : ('yes' === eh_payment_request_params.gpay_enabled) ? 'always' : 'never',
            applePay : ('yes' === eh_payment_request_params.apple_pay_enabled) ? 'always' : 'never',
            amazonPay : 'never',
            link :  'never',
            paypal : 'never',
          },
          buttonTheme: {
            applePay: eh_payment_request_params.apple_pay_color,
            googlePay: eh_payment_request_params.gpay_button_theme,
          },
          buttonType: {
            applePay: eh_payment_request_params.apple_pay_type,
            googlePay: eh_payment_request_params.gpay_button_type,            
          },
          buttonHeight: parseInt(eh_payment_request_params.button_height, 10),
        };
        const expressCheckoutElement = elements.create("expressCheckout", expressOptions);
        expressCheckoutElement.mount("#eh-stripe-payment-request-button");


          var shippingReqData = {
            '_wpnonce': eh_payment_request_params.eh_payment_request_get_shipping_nonce,
            is_product:   (eh_payment_request_params.product) ? 'yes' : 'no',
            country:   eh_payment_request_params.country_code,
            state:     '',
            postcode:  '',
            city:      '',
            address:   '',
            address_2: '',
          };

        
        $( document ).trigger("wt-stripe-get-shipping-request-data", shippingReqData);

        var result = $.ajax( {
          type:    'POST',
          data:    shippingReqData,
          url:     eh_payment_request_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_payment_request_get_shippings"),

        });

        expressCheckoutElement.on('click', (event) => {
          if(eh_payment_request_params.product){ 
            eh_payment_request_gen.add_to_cart(event);
          }

          if((eh_payment_request_params.product && eh_payment_request_params.product_data.needs_shipping)|| 'yes' === eh_payment_request_params.needs_shipping){

            $.when(result).then(
              function (response) { 

			          if ( response.debug === true && ( !Array.isArray(response.shipping_options) || response.shipping_options.length === 0 ) ){   
                  if($('#warningMessage').length === 0){
                    const warningMessage = $('<div id="warningMessage" style="display: block;color: #d93025; background-color: #fce8e6;   border: 1px solid #f5c6cb; padding: 12px 16px; margin: 12px 0; border-radius: 4px; font-weight: 600; font-size: 14px; "> ⚠️ No shipping methods are available for your address or the store’s base address. Please ensure shipping options are properly configured and that at least one shipping method is added for the store’s default address.</div> ');
                  // Insert the warning message above the payment request button
                    $('#eh-stripe-payment-request-button').before(warningMessage);
                  }
                  event.reject();
                }else{
                  warningMessage.hide();

                  // Success callback
                  const options = {
                    emailRequired: true,
                    phoneNumberRequired: true,
                    shippingAddressRequired: true,
                    shippingRates: response.shipping_options,

                  };
                  event.resolve(options);
                }
              },
              function (jqXHR, textStatus, errorThrown) {
                // Error callback
                console.error('Error:', textStatus, errorThrown);
              }

              
            );
          }
          else{
            const options = {
              emailRequired: true,
              phoneNumberRequired: true,
            }; 
            event.resolve(options);
           
          }

          
        });


        expressCheckoutElement.on('confirm', async (evt) => { 

          try {

            const response = await eh_payment_request_gen.ProcessPaymentMethod(evt);

            
            if (response.result === 'success') {
              const { error } = await stripe.confirmPayment({
                // `Elements` instance that's used to create the Express Checkout Element.
                elements,
                // `clientSecret` from the created PaymentIntent
                clientSecret: response.client_secret,
                confirmParams: {
                  return_url: response.redirect,
                },
                // Uncomment below if you only want redirect for redirect-based payments.
                // redirect: 'if_required',
              });

              if (error) {
                console.log(error);
                // This point is reached only if there's an immediate error when confirming the payment. Show the error to your customer (for example, payment details incomplete).
              } else {
                // Your customer will be redirected to your `return_url`.
              }              
            } else {
              eh_payment_request_gen.paymentFailure(evt, response.messages);
            }
          } catch (error) {
            console.log('An error occurred:', error);
            // Handle any errors that occurred during the payment processing
          }
        });

        expressCheckoutElement.on( 'shippingaddresschange', async( evt ) => {
          
          try{
            const response = await eh_payment_request_gen.updateShippingOptions( paymentdetails, evt.address );
            if (response && response.total && response.total.amount !== undefined) {
              var amount = response.total.amount;
            }
            else if (response && response.product_data && response.product_data.total && response.product_data.total.amount !== undefined) {
              var amount = response.product_data.total.amount;
            }  
            elements.update({amount: amount})            
            evt.resolve( { shippingRates: response.shipping_options,  lineItems: response.displayItems } );
              
            
          }
          catch (error) {
            console.log('An error occurred:', error);
            evt.reject(new Error('Unable to update shipping options.'));
            // Handle any errors that occurred during the payment processing
          }          
        });
        
        expressCheckoutElement.on( 'shippingratechange', async( evt ) => {
            
          try{
            const response = await eh_payment_request_gen.updateShippingDetails( paymentdetails, evt.shippingRate );
            if (response && response.total && response.total.amount !== undefined) {
              var amount = response.total.amount;
            }
            else if (response && response.product_data && response.product_data.total && response.product_data.total.amount !== undefined) {
              var amount = response.product_data.total.amount;
            }            
            elements.update({amount: amount})            
            evt.resolve( {  lineItems: response.displayItems } );
              
            
          }
          catch (error) {
            console.log('An error occurred:', error);
            evt.reject(new Error('Unable to update shipping rates.'));
            // Handle any errors that occurred during the payment processing
          }          
        });

      },
  
      add_to_cart: function(e){
       
        if ( $( '.single_add_to_cart_button' ).is( '.disabled' ) ) {
          e.preventDefault();
         
          if ( $( '.single_add_to_cart_button' ).is('.wc-variation-is-unavailable') ) {
            window.alert( wc_add_to_cart_variation_params.i18n_unavailable_text );
          } else if ( $( '.single_add_to_cart_button' ).is('.wc-variation-selection-needed') ) {
            window.alert( wc_add_to_cart_variation_params.i18n_make_a_selection_text );
          }
          return;
        } 
         
        eh_payment_request_gen.add_to_cart_ajax_call();
  
      },
  
      add_to_cart_ajax_call: function(){
  
        var qty = $('.qty').val();
        if(!qty){
          qty = $("input[name=quantity]").val();  
        }
  
        var product_id = $( '.button.single_add_to_cart_button' ).val();
        if(! product_id){
          product_id = $("input[name=add-to-cart]").val();  
        }
        if ( $( '.single_variation_wrap' ).length ) {
          product_id = $( '.single_variation_wrap' ).find( 'input[name="product_id"]' ).val();
          var variation_id = $( '.single_variation_wrap' ).find( 'input[name="variation_id"]' ).val();
        }
        var data = {
          qty: qty,
          product_id: product_id,
          variation_id: variation_id ? variation_id : 0,
          '_wpnonce': eh_payment_request_params.eh_add_to_cart_nonce
  
        };
  
        return  $.ajax( {
          type:    'POST',
          data:    data,
          url:     eh_payment_request_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_add_to_cart"),
        });
  
      },
  
      updateShippingOptions: function( details, address ) {
            
        var data = {
          '_wpnonce': eh_payment_request_params.eh_payment_request_get_shipping_nonce,
          country:   address.country,
          state:     address.state,
          postcode:  address.postal_code,
          city:      address.city,
          address:   'undefined' === typeof address.line1 ? '' : address.line1,
          address_2:  'undefined' === typeof address.line2 ? '' : address.line2,
        };
        
        $( document ).trigger("wt-stripe-get-shippings-data", data);

        return  $.ajax( {
          type:    'POST',
          data:    data,
          url:     eh_payment_request_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_payment_request_get_shippings"),
        });
      },
  
      updateShippingDetails: function( details, shippingOption ) {
        var data = {
          '_wpnonce' : eh_payment_request_params.eh_payment_request_update_shipping_nonce,
        
          shipping_method: [ shippingOption.id ],
  
        };
        
        $( document ).trigger("wt-stripe-update-shippings-data", data);
        return  $.ajax( {
          type: 'POST',
          data: data,
          url:  eh_payment_request_params.wc_ajaxurl.toString().replace( '%%change_end%%', "eh_spg_payment_request_update_shippings")
        } );
        
      },
  
  
      paymentFailure: function( payment, message ) {
  
        var $target = $( '.woocommerce-notices-wrapper:first' ) || $( '.cart-empty' ).closest( '.woocommerce' ) || $( '.woocommerce-cart-form' );
  
        $( '.woocommerce-error' ).remove();
          
        $target.append( message );
        $(window).scrollTop(0);
  
      },
  
      init: function() {
        if(eh_payment_request_params.product){
          eh_payment_request_gen.startPaymentRequest( '' );
        }
            
        eh_payment_request_gen.eh_generate_cart();
  
      },
    };
  
    eh_payment_request_gen.init();
  
    
    $(document.body).on('updated_cart_totals', function () {
      eh_payment_request_gen.init();
    });
  
    
    $(document.body).on('updated_checkout', function () {
      eh_payment_request_gen.init();
    });

     $(document.body).on('found_variation', function () {
      eh_payment_request_gen.init();
    });
  
  });