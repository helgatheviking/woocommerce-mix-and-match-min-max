;( function ( $, window, document, undefined ) {

	$( 'body' ).on( 'wc-mnm-initialized', '.mnm_form', function( e, form ){
        
    } );


    $( 'body' ).on( 'wc-mnm-validation', '.mnm_form', function( e, form, total_qty ){

    	var min_container_size    = form.$mnm_cart.data( 'min_container_size' );
    	var max_container_size    = form.$mnm_cart.data( 'max_container_size' );

    	// only process script if item has min/max container settings
    	if( max_container_size > 0 || min_container_size > 0 ){

	    	var container_size        = form.get_container_size();
	    	var total_qty_valid     = false;
	    	var error_message = '';

	    	// if not set, min_container_size is always 1, because the container can't be empty
			min_container_size = min_container_size > 0 ? min_container_size : 1;

			// validate the container items against new critieria & build a specific error message
			
			// validate that an unlimited container is in min/max range
			if( container_size === 0 && max_container_size > 0 && min_container_size > 0  ){
				if( total_qty >= min_container_size && total_qty <= max_container_size ){
					total_qty_valid = true;
				} else {
					error_message = error_message = wc_mnm_min_max_params.i18n_min_max_qty_error.replace( '%max', max_container_size ).replace( '%min', min_container_size );
				}
			}  
			// validate that an unlimited container has minimum number of items
			else if( container_size === 0 && min_container_size > 0 ){ console.log('min bacon');
				if( total_qty >= min_container_size ){
					total_qty_valid = true;
				} else {
					error_message = wc_mnm_min_max_params.i18n_min_qty_error.replace( '%min', min_container_size );
				}
			} 

			// Add error message
			if ( ! total_qty_valid ) {
				form.reset_messages();
				// "Selected X total"
				var selected_qty_message = form.selected_quantity_message( total_qty );
				// add error message, replacing placeholders with current values
				form.add_message( error_message.replace( '%v', selected_qty_message ), 'error' );
			}

    	}

    } );

} ) ( jQuery, window, document );