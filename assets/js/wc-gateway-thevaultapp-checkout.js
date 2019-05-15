;(function ( $, window, document ) {
	'use strict';
    var $checkout_form = $( 'form.checkout' );
    var $payment_result = null;
    
	$(document).on( 'click', '#place_order', function(e) {
        e.preventDefault();      
        if (get_payment_method() == "thevaultapp") {
            submit();            
        } else {
            jQuery( 'form.checkout' ).submit(); 
        }
		if (!do_the_validation()) {            
		    
		} else {
		    return false;
		}	
    });
    
    function get_payment_method() {
        return $checkout_form.find( 'input[name="payment_method"]:checked' ).val();
    }

    function blockOnSubmit( $form ) {
        var form_data = $form.data();

        if ( 1 !== form_data['blockUI.isBlocked'] ) {
            $form.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }
    }

    function is_valid_json( raw_json ) {
        try {
            var json = $.parseJSON( raw_json );

            return ( json && 'object' === typeof json );
        } catch ( e ) {
            return false;
        }
    }
    
    function submit() {
        var $form = $checkout_form;

        if ( $form.is( '.processing' ) ) {
            return false;
        }

        // Trigger a handler to let gateways manipulate the checkout if needed
        if ( $form.triggerHandler( 'checkout_place_order' ) !== false && $form.triggerHandler( 'checkout_place_order_' + get_payment_method() ) !== false ) {

            $form.addClass( 'processing' );

            blockOnSubmit( $form );

            // ajaxSetup is global, but we use it to ensure JSON is valid once returned.
            $.ajaxSetup( {
                dataFilter: function( raw_response, dataType ) {
                    // We only want to work with JSON
                    if ( 'json' !== dataType ) {
                        return raw_response;
                    }

                    if ( is_valid_json( raw_response ) ) {
                        return raw_response;
                    } else {
                        // Attempt to fix the malformed JSON
                        var maybe_valid_json = raw_response.match( /{"result.*}/ );

                        if ( null === maybe_valid_json ) {
                            console.log( 'Unable to fix malformed JSON' );
                        } else if ( is_valid_json( maybe_valid_json[0] ) ) {
                            console.log( 'Fixed malformed JSON. Original:' );
                            console.log( raw_response );
                            raw_response = maybe_valid_json[0];
                        } else {
                            console.log( 'Unable to fix malformed JSON' );
                        }
                    }

                    return raw_response;
                }
            } );

            $.ajax({
                type:		'POST',
                url:		wc_checkout_params.checkout_url,
                data:		$form.serialize(),
                dataType:   'json',
                success:	function( result ) {
                    try {
                        if ( 'success' === result.result ) {
                            $payment_result = result;
                            show_success_modal(result);
                        } else if ( 'failure' === result.result ) {
                            throw 'Result failure';
                        } else {
                            throw 'Invalid response';
                        }
                    } catch( err ) {
                        // Reload page
                        if ( true === result.reload ) {
                            window.location.reload();
                            return;
                        }

                        // Trigger update in case we need a fresh nonce
                        if ( true === result.refresh ) {
                            $( document.body ).trigger( 'update_checkout' );
                        }

                        // Add new errors
                        if ( result.messages ) {
                            submit_error( result.messages );
                        } else {
                            submit_error( '<div class="woocommerce-error">' + wc_checkout_params.i18n_checkout_error + '</div>' );
                        }
                    }
                },
                error:	function( jqXHR, textStatus, errorThrown ) {
                    submit_error( '<div class="woocommerce-error">' + errorThrown + '</div>' );
                }
            });
        }

        return false;
    }

    function submit_error( error_message ) {
        $( '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message' ).remove();
        $checkout_form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>' );
        $checkout_form.removeClass( 'processing' ).unblock();
        $checkout_form.find( '.input-text, select, input:checkbox' ).trigger( 'validate' ).blur();
        scroll_to_notices();
        $( document.body ).trigger( 'checkout_error' );
    }

	function do_the_validation() {
		// wc_checkout_params is required to continue, ensure the object exists
		if ( typeof wc_checkout_form === 'undefined' ) {
			return false;
		}
		return true;
    }
    
    function scroll_to_notices() {
        var scrollElement           = $( '.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout' );

        if ( ! scrollElement.length ) {
            scrollElement = $( '.form.checkout' );
        }
        $.scroll_to_notices( scrollElement );
    }

    var pollingVaultPaymentStatus = (function(data) {        
        var timer = undefined;        
        var paymentInfo = data;
        function fetchVaultPaymentStatus() {
            var self = this;            
            $.ajax({
                type: "POST",
                url: '/wp-admin/admin-ajax.php?action=fetch_order_status',
                data: {
                    order_id: self.data.data.subid1
                },
                success: function(res) {                    
                    if (res === 'completed') {
                        show_complete_modal(paymentInfo);                        
                        timer = undefined;
                        return;
                    } else if (res === 'cancelled') {
                        show_cancel_modal(paymentInfo);
                        timer = undefined;
                        return;
                    }                    
                    timer = setTimeout(fetchVaultPaymentStatus.bind(self), 1000);
                },
                error: function(request, status, error) {
                    timer = undefined;
                    alert(error.message || error);                    
                }
            })
        }

        return function () {
            var self = this;
            if (!!timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(fetchVaultPaymentStatus.bind(self), 0);
        }
    })();

    function show_success_modal( result ) {
        var content_html = '<div id="thevaultapp_alert" class="thevaultapp-hide" style="display: block;">';
        content_html += '<div style="font-size:30px">Almost Complete!</div>';
        content_html += '<div style="font-size:18px;margin-bottom:20px;">';
        content_html += 'A payment request has been sent to your phone.<br>In order to complete this checkout press "Accept" from with the app.</div>';
        content_html += '<div style="font-size:15px">';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Phone\'">Phone</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.phone +'</span>';
        content_html += '</div>';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Amount\'">Amount</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.amount +'</span>';
        content_html += '</div>';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Order ID\'">Order ID</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.subid1 +'</span>';
        content_html += '</div>';
        content_html += '</div>';
        content_html += '</div>';

        var header = "";
        var content = content_html;
        var strSubmitFunc = "applyButtonFunc()";
        var btnText = "Just do it!";

        do_modal('idMyModal', header, content, strSubmitFunc, btnText);

        // Quick & dirty toggle to demonstrate modal toggle behavior
        $('.modal-toggle').on('click', function(e) {
            e.preventDefault();
            $('.modal').toggleClass('is-visible');
            pollingVaultPaymentStatus.call($payment_result);
        });
    }

    function show_complete_modal( result ) {
        var content_html = '<div id="thevaultapp_alert" class="thevaultapp-hide" style="display: block;">';
        content_html += '<div style="font-size:30px">Almost Complete!</div>';
        content_html += '<div style="font-size:18px;margin-bottom:20px;">';
        content_html += 'Your order is completed.<br>View your payment from within The Vault App.</div>';
        content_html += '<div style="font-size:15px">';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Phone\'">Phone</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.phone +'</span>';
        content_html += '</div>';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Amount\'">Amount</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.amount +'</span>';
        content_html += '</div>';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Order ID\'">Order ID</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.subid1 +'</span>';
        content_html += '</div>';
        content_html += '</div>';
        content_html += '</div>';

        var header = "";
        var content = content_html;
        var strSubmitFunc = "applyButtonFunc()";
        var btnText = "Just do it!";

        do_modal('idMyModal', header, content, strSubmitFunc, btnText);

        // Quick & dirty toggle to demonstrate modal toggle behavior
        $('.modal-toggle').on('click', function(e) {
            e.preventDefault();
            $('.modal').toggleClass('is-visible');            
            if ( -1 === $payment_result.redirect.indexOf( 'https://' ) || -1 === $payment_result.redirect.indexOf( 'http://' ) ) {
                window.location = $payment_result.redirect;
            } else {
                window.location = decodeURI( $payment_result.redirect );
            }
        });
    }

    function show_cancel_modal( result ) {
        var content_html = '<div id="thevaultapp_alert" class="thevaultapp-hide" style="display: block;">';
        content_html += '<div style="font-size:30px">Your order was cancelled!</div>';        
        content_html += '<div style="font-size:15px">';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Phone\'">Phone</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.phone +'</span>';
        content_html += '</div>';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Amount\'">Amount</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.amount +'</span>';
        content_html += '</div>';
        content_html += '<div>';
        content_html += '<span data-bind="i18n: \'Order ID\'">Order ID</span>&nbsp;:&nbsp;&nbsp;';
        content_html += '<span>' + result.data.data.subid1 +'</span>';
        content_html += '</div>';
        content_html += '</div>';
        content_html += '</div>';

        var header = "";
        var content = content_html;
        var strSubmitFunc = "applyButtonFunc()";
        var btnText = "Just do it!";

        do_modal('idMyModal', header, content, strSubmitFunc, btnText);

        // Quick & dirty toggle to demonstrate modal toggle behavior
        $('.modal-toggle').on('click', function(e) {
            e.preventDefault();            
            $('.modal').toggleClass('is-visible');
            window.location.reload();
        });
    } 

    function do_modal(placementId, heading, formContent, strSubmitFunc, btnText)
    {
        var html =  '<div id="modalWindow" class="modal">';
        html += '<div class="modal-overlay modal-toggle"></div>';
        html += '<div class="modal-wrapper modal-transition">';
        html += '<div class="modal-header">';
        html += '<button class="modal-close modal-toggle">X<use xlink:href="#icon-close"></use></svg></button>';
        html += '<h2 class="modal-heading">' + heading + '</h2>';
        html += '</div>';
        html += '<div class="modal-body">';
        html += '<div class="modal-content">';
        html += formContent;        
        html += '</div>';
        html += '<div class="modal-footer">';
        html += '<button class="modal-toggle">Ok</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        if (!$('#wc_modal').length)
            $('body').append('<div id="wc_modal"></div>');
        $("#wc_modal").html(html);
        $('.modal').toggleClass('is-visible');  
    }    
    
})( jQuery, window, document );