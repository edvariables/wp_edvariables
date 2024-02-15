 
const EDV_TAG = 'edv';

jQuery( function( $ ) {
	
	/**
	 */
	$( document ).ready(function() {
	
		/**
		 * Ajax action
		 *
		 */
		function edv_ajax_action(event){
			var $actionElnt = $(this);
			var data = $actionElnt.attr('data');
			if( ! data)
				return false;
			data = JSON.parse( data );
			if(this.tagName.toUpperCase() == 'FORM'){
				$actionElnt.find('input[name]').each(function(event){ data[this.attributes['name'].value] = this.value;});
			}
			if( data.confirm ){
				var msg;
				if((typeof data.confirm === 'string' || data.confirm instanceof String)
				&& '0 1 true false'.indexOf(data.confirm.toLowerCase()) === -1 ){
					msg = data.confirm;
				}
				else {
					msg = "Confirmez-vous ?";
				}
				if(! confirm(msg) ) return false;
			}
			jQuery.ajax({
				url : agendapartage_ajax.ajax_url,
				type : 'post', 
				data : Object.assign(data, {
					_nonce : agendapartage_ajax.check_nonce
				}),
				success : function( response ) {
					if(response){
						if(typeof response === 'string' || response instanceof String){
							if(response.endsWith('null'))
								response = substr(response, 0, response.length-4);
							var action = response.split(':')[0];
							switch(action){
								case 'redir' :
									response = response.substring(action.length + 1);
									$msg = $('<div class="ajax_action_info info">La page va être rechargée. Merci de patienter.</div>');
									$spinner.after($msg);
									// $msg.get(0).scrollIntoView();
									response = response.substring('redir:'.length);
									document.location = response;
									return;
								case 'js' :
									response = response.substring(action.length + 1);
									eval('(function(){'+response+';})').apply($actionElnt);
									return;
								case 'replace' :
									response = response.substring(action.length + 1);
									$actionElnt.replaceWith(response);
									response = false;
									break;
								case 'download' :
									var url = response.substring(action.length + 1);
									response = 'Le téléchargement est lancé. Consultez vos téléchargements.';
									window.location.href = url;
									break;
							}
							if(response) {
								var $msg = $('<div class="ajax_action-response"><span class="dashicons dashicons-no-alt close-box"></span>'+response+'</div>')
									.click(function(){$msg.remove()});
								$actionElnt.after($msg);
								// $msg.get(0).scrollIntoView();
							}
						}
					}
					$spinner.remove();
				},
				fail : function( response ){
					$spinner.remove();
					var $msg = $('<div class="ajax_action-response alerte"><span class="dashicons dashicons-no-alt close-box"></span>'+response+'</div>')
						.click(function(){$msg.remove()});
					$actionElnt.after($msg);
					// $msg.get(0).scrollIntoView();
				}
			});
			var $spinner = $actionElnt.next('.wpcf7-spinner');
			if($spinner.length == 0)
					$actionElnt.after($spinner = $('<span class="wpcf7-spinner" style="visibility: visible;"></span>'));
			event.preventDefault();  
			return false;
		};
		$(' body ')
			.on('click', "a.edv-ajax-action", edv_ajax_action)
			.on('submit', "form.edv-ajax-action", edv_ajax_action);

/**
 * Toggler
 */
		$( 'body' ).on('toggle-init', function() {
			$( ".toggle-trigger:not(.active)" )
				.next( ".toggle-container" ).hide();
			$( ".toggle-trigger.active" )
				.next( ".toggle-container" ).show();
		}).trigger('toggle-init');
		$( 'body' ).on('click', ".toggle-trigger", function(event) {
			var $toggler = $(this);
			var isActive = $toggler.is( ".active" );
			var ajaxData = !isActive && $toggler.is( "[ajax][data]" ) ? $toggler.attr('data') : false;
			if(ajaxData){
				var ajaxAttr = $toggler.attr('ajax');
				switch(ajaxAttr){
					case '0':
					case 'false':
					case 'done':
						ajaxData = false;
						break;
					case 'once':
						$toggler.attr('ajax', 'done');
						break;
				}
			}
			if(ajaxData){
				var ajaxData = JSON.parse(ajaxData);
				jQuery.ajax({
					url : agendapartage_ajax.ajax_url,
					type : 'post',
					data : Object.assign(ajaxData, {
						_nonce : agendapartage_ajax.check_nonce
					}),
					success : function( response ) {
						$spinner.remove();
						if(response){
							if(typeof response === 'string' || response instanceof String){
								var $container = $toggler.next('.toggle-container');
								if($container.length == 0)
									$container = $('<div class="toggle-container"/>').appendTo($toggler);
								$container
									.html( response )
										.trigger('toggle-init')
										.find('.wpcf7 > form')
											.trigger('edvevent_edit_form-init')
											.each(function(e){
												wpcf7.init(this);
												if( typeof fire_wpcf7_init_after_ajax === "function" )
													fire_wpcf7_init_after_ajax(this);
											})
								;
								$toggler
									.toggleClass( "active" ).next(".toggle-container").slideDown( "normal" );
							}
						}
					},
					fail : function( response ){
						$spinner.remove();
						var $msg = $('<div class="ajax_action-response alerte"><span class="dashicons dashicons-no-alt close-box"></span>'+response+'</div>')
							.click(function(){$msg.remove()});
						$actionElnt.after($msg);
					}
				});
				$toggler.addClass('loading');//Todo Hourglass
				var $spinner = $toggler.children('.wpcf7-spinner');
				if($spinner.length == 0)
						$spinner = $('<span class="wpcf7-spinner" style="visibility: visible;"></span>')
								.appendTo($toggler.children(':first'));
			}
			else if(isActive) {
				$toggler.removeClass( "active" ).next(".toggle-container").slideUp( "normal" );
				//Si la touche Control est enfoncé, redéplie (et recharge ajax si besoin est)
				 if( event.ctrlKey )
					$toggler.click( );
			}
			else {
				$toggler.addClass( "active" ).next(".toggle-container").slideDown( "normal" );
			}
			return false;
		} );
		$( 'body' ).on('click', '.toggle-container .trigger-collapser', function() {
			$(this)
				.parents('.toggle-container:first').hide()
				.prev('.toggle-trigger').removeClass('active');
			return false;
		} );
		$( 'body' ).on('toggle-active', '.toggle-trigger', function(activate = true) {
			if(activate)
				$(this).addClass( "active" ).next(".toggle-container").slideDown( "normal" );
			else
				$(this).removeClass( "active" ).next(".toggle-container").slideUp( "normal" );
		} );
	} );
} );

