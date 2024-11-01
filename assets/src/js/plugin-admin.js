( function( $ ) {
	'use strict';

	// Javascript.

	// $( 'body' ).on( 'click', 'input[name="shortcode-list__generate"]', function(){
		
		setInterval( function(){
			console.log('Rebuilding Table');
			$.ajax( {
				type:     'POST',
				url:      ajax_object.ajaxurl,
				data:     {
					action: 'build_table',
					security: ajax_object.ajaxnonce,
				},
				success: function( table ) {
					$( '.shortcode-list' ).html( table );
				}
			} );
		}, 10 * 1000 );
	// });

	$( 'body' ).on( 'click', '.shortcode-finder', function() {

		$( '.wrap > h2 > img' ).remove();
		$( '.wrap > h2' ).append( ' <img src="/wp-admin/images/spinner-2x.gif" height="20px width="20px;"/>');

		$( '.wrap .shortcode-finder__warning' ).remove();
		$( '.wrap > h2').after( '<div class="notice notice-warning"><p class="shortcode-finder__warning">On larger sites it can a few minutes to find all of your shortcodes, this is perfectly normal, please be patient.</p></div>' );

		$.ajax( {
			type:     'POST',
			url:      ajax_object.ajaxurl,
			data:     {
				action: 'get_all_posts',
				security: ajax_object.ajaxnonce,
			},
			success: function( posts ) {
				if ( $.isArray( posts ) && posts.length > 0 ) {
					var posts_count     = posts.length;
					var posts_processed = 0;
					$.each( posts, function( index, post_id ) {
						var timout = setTimeout( function() {
							posts_processed++;
							$.ajax( {
								type:     'POST',
								url:      ajax_object.ajaxurl,
								data:     {
									post_id: post_id,
									action: 'has_shortcode',
									security: ajax_object.ajaxnonce,
								},
								success: function( response ) {
									$.ajax( {
										type:     'POST',
										url:      ajax_object.ajaxurl,
										data:     {
											action: 'build_table',
											security: ajax_object.ajaxnonce,
										},
										success: function( table ) {
											$( '.shortcode-list' ).html( table );
										}
									} );
								},
								finally: function() {
									if ( posts_processed === posts_count ) {
										$( '.wrap > h2 > img' ).remove();
										$( '.wrap .shortcode-finder__warning' ).remove();
									}
								}
							} );

						}, 500 * index );
					} );
				}
			},
		} );
	} );

	$( 'body' ).on( 'click', '.shortcode-list__filter input', function() {
		var keys = [];
		$.each( $( '.shortcode-list__filter input:checkbox:not(:checked)' ), function() {
			keys.push( $( this ).val() );
		} );
		$.ajax( {
			type:     'POST',
			url:      ajax_object.ajaxurl,
			data:     {
				keys: keys,
				action: 'save_list_filter',
				security: ajax_object.ajaxnonce,
			},
			success: function( response ) {
				$.ajax( {
					type:     'POST',
					url:      ajax_object.ajaxurl,
					data:     {
						action: 'build_table',
						security: ajax_object.ajaxnonce,
					},
					success: function( table ) {
						$( '.shortcode-list' ).html( table );
					}
				} );
			},
		} );
	} );

} )( jQuery );
