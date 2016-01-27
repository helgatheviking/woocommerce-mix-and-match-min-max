;( function ( $, window, document, undefined ) {

    $( 'body' ).on( 'wc-mnm-validation', '.mnm_form', function( e, form, total_qty ){

    	var min_container_size    = form.$mnm_cart.data( 'min_container_size' );
    	var max_container_size    = form.$mnm_cart.data( 'max_container_size' );

	    // if not set, min_container_size is always 1, because the container can't be empty
		min_container_size = min_container_size > 0 ? min_container_size : 1;
		
		// not a static-sized container
		if( min_container_size != max_container_size ){

			var error_message = '';

			// validate a range
	    	if( max_container_size > 0 && min_container_size > 0 ){

				if( total_qty < min_container_size || total_qty > max_container_size ){
					error_message = wc_mnm_min_max_params.i18n_min_max_qty_error.replace( '%max', max_container_size ).replace( '%min', min_container_size );
				}
			}  
			// validate that an unlimited container has minimum number of items
			else if( min_container_size > 0 && max_container_size == 0 ){
				if( total_qty < min_container_size ){
					if( min_container_size > 1 ){
						error_message = wc_mnm_min_max_params.i18n_min_qty_error;
					} else {
						error_message = wc_mnm_min_max_params.i18n_min_qty_error_singular;
					}
					error_message = error_message.replace( '%min', min_container_size );
				}
			} 

			// Add error message
			form.reset_messages();
			if ( error_message ) {
				// "Selected X total"
				var selected_qty_message = form.selected_quantity_message( total_qty );
				// add error message, replacing placeholders with current values
				form.add_message( error_message.replace( '%v', selected_qty_message ), 'error' );
			} 

		} 

    } );

} ) ( jQuery, window, document );