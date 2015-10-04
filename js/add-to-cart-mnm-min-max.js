jQuery( document ).ready( function($) {

	$( '.mnm_form' ).on( 'wc-mnm-validation', function(e, total_qty, container_size ) { 

		var min_container_size    = $(this).find( '.mnm_cart' ).data( 'min_container_size' );
		
		// validate the container items against new critieria
		if( ( container_size == 0 && total_qty > min_container_size ) ) {
			wc_mnm_params.validation_status = "passed";
			// if infinite container show count message
			wc_mnm_params.validation_message = wc_mnm_get_quantity_message( total_qty );
		// failed, so create appropriate error message
		} else {
			wc_mnm_params.validation_status = "failed";
			// "Selected X total"
			selected_message = wc_mnm_get_quantity_message( total_qty ); 
			// replace placeholders with current values
			wc_mnm_params.validation_message = wc_mnm_min_params.i18n_min_qty_error.replace( '%s', container_size ).replace( '%v', selected_message );
		}

	} );

	$( '.mnm_form' ).trigger( 'wc-mnm-validation');

});