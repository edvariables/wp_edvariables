<?php

/**
 * edv -> Message
 * Custom post type for WordPress.
 * 
 * Définition des shortcodes 
 *
 * Voir aussi edv_Admin_Post
 */
class edv_Post_Shortcodes {


	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::$initiated = true;

			self::init_hooks();
			self::init_shortcodes();
		}
	}

	/**
	 * Hook
	 */
	public static function init_hooks() {
		
		add_action( 'wp_ajax_'.EDV_TAG.'_shortcode', array(__CLASS__, 'on_wp_ajax_edpost_shortcode_cb') );
		add_action( 'wp_ajax_nopriv_'.EDV_TAG.'_shortcode', array(__CLASS__, 'on_wp_ajax_edpost_shortcode_cb') );
		add_filter('wpcf7_mail_components', array(__CLASS__, 'on_wpcf7_mail_components'), 10, 3);
	}

	/////////////////
 	// shortcodes //
 	/**
 	 * init_shortcodes
 	 */
	public static function init_shortcodes(){

		add_shortcode( 'edp-titre', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-categories', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-cities', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-diffusions', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-description', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-dates', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-localisation', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-details', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-message-contact', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edpost', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-avec-email', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'edp-cree-depuis', array(__CLASS__, 'shortcodes_callback') );
		
		add_shortcode( 'edp-modifier', array(__CLASS__, 'shortcodes_callback') );
		
		add_shortcode( 'edposts', array(__CLASS__, 'shortcodes_callback') );
		
		add_shortcode( 'edvstats', array(__CLASS__, 'shortcodes_callback') );
		
		add_shortcode( 'post', array(__CLASS__, 'shortcode_post_callback') );

	}

	/**
	 * [post]
	 * [post info="edp-email"]
	 * [post info="edp-telephone"]
	 * [post info="mailto"]
	 * [post info="uri"] [post info="url"]
	 * [post info="a"] [post info="link"]
	 * [post info="post_type"]
	 * [post info="dump"]
	 */
	public static function shortcode_post_callback($atts, $content = '', $shortcode = null){
		$post = get_post();
		if(!$post){
			echo $content;
			return;
		}

		if( ! is_array($atts)){
			$atts = array();
		}

		if(! array_key_exists('info', $atts)
		|| ! ($info = $atts['info']))
			$info = 'post_title';

		switch($info){
			case 'uri':
			case 'url':
				return $_SERVER['HTTP_REFERER'];
			case 'link':
			case 'a':
				return sprintf('<a href="%s">%s - %s</a>', edv_Post::get_post_permalink($post), 'edv', $post->post_title);

			case 'mailto':
				$email = get_post_meta( $post->ID, 'edp-email', true);
				return sprintf('<a href="mailto:%s">%s</a>', antispambot(sanitize_email($email)), $post->post_title);//TODO anti-spam

			case 'dump':
				return sprintf('<pre>%s</pre>', 'shortcodes dump : ' . var_export($post, true));

			case 'title':
				$info = 'post_title';

			default :
				if(isset($post->$info))
					return $post->$info;
				return get_post_meta( $post->ID, $info, true);

		}
	}

	/**
	* Callback des shortcodes
	*/
	public static function shortcodes_callback($atts, $content = '', $shortcode = null){

		if(is_admin() 
		&& ! wp_doing_ajax()
		&& ! edv_Newsletter::is_sending_email())
			return;
		
		if( ! is_array($atts)){
			$atts = array();
		}
		
		//champs sans valeur transformer en champ=true
		foreach($atts as $key=>$value){
			if(is_numeric($key) && ! array_key_exists($value, $atts)){
				$atts[$value] = true;
				unset($atts[$key]);
			}
		}
		if(array_key_exists('toggle-ajax', $atts)){
			$atts['toggle'] = $atts['toggle-ajax'];
			$atts['ajax'] = true;
			unset($atts['toggle-ajax']);
		}
		
		$key = 'ajax';
		if(array_key_exists($key, $atts)){
			$ajax = $atts[$key] ? $atts[$key] : true;
			unset($atts[$key]);
		}
		else{
			$ajax = false;
			$key = 'post_id';
			if(array_key_exists($key, $atts)){
				global $post;
				if(!$post){
					$post = get_post($atts[$key]);
					
					//Nécessaire pour WPCF7 pour affecter une valeur à _wpcf7_container_post
					global $wp_query;
					if($post)
						$wp_query->in_the_loop = true;
				}
				$_POST[$key] = $_REQUEST[$key] = $atts[$key];
				unset($atts[$key]);
			}
			$key = EDV_POST_SECRETCODE ;
			if(array_key_exists($key, $atts)){
				$_POST[$key] = $_REQUEST[$key] = $atts[$key];
				unset($atts[$key]);
			}
		}
		// Si attribut toggle [edpost-details toggle="Contactez-nous !"]
		// Fait un appel récursif si il y a l'attribut "ajax"
		// TODO Sauf shortcode conditionnel
		if(array_key_exists('toggle', $atts)){
			
			$shortcode_atts = '';
			foreach($atts as $key=>$value){
				if($key == 'toggle'){
					$title = array_key_exists('title', $atts) && $atts['title'] 
						? $atts['title']
						: ( $atts['toggle'] 
							? $atts['toggle'] 
							: __($shortcode, EDV_TAG)) ;
				}
				elseif( ! is_numeric($key) ){
					if(is_numeric($value))
						$shortcode_atts .= sprintf('%s=%s ', $key, $value);
					else
						$shortcode_atts .= sprintf('%s="%s" ', $key, esc_attr($value));
				}
			}
			
			//Inner
			$html = sprintf('[%s %s]%s[/%s]', $shortcode, $shortcode_atts , $content, $shortcode);
			
			if( ! $ajax){
				$html = do_shortcode($html);
			}
			else{
				$ajax = sprintf('ajax="%s"', esc_attr($ajax));
				$html = esc_attr(str_replace('"', '\\"', $html));
			}
			//toggle
			//Bugg du toggle qui supprime des éléments
			$guid = uniqid(EDV_TAG);
			$toogler = do_shortcode(sprintf('[toggle title="%s" %s]%s[/toggle]'
				, esc_attr($title)
				, $ajax 
				, $guid
			));
			return str_replace($guid, $html, $toogler);
		}

		//De la forme [edposts liste] ou [edposts-calendrier]
		if($shortcode == 'edposts' || str_starts_with($shortcode, 'edposts-')){
			return self::shortcodes_edposts_callback($atts, $content, $shortcode);
		}
		//De la forme [edvstats stat] ou [edvstats-stat]
		if($shortcode == 'edvstats' || str_starts_with($shortcode, 'edvstats-')){
			return self::shortcodes_edvstats_callback($atts, $content, $shortcode);
		}
		return self::shortcodes_edpost_callback($atts, $content, $shortcode);
	}
	
	/**
	* [edpost info=titre|description|dates|localisation|details|message-contact|modifier]
	* [edpost-titre]
	* [edpost-description]
	* [edpost-dates]
	* [edpost-localisation]
	* [edpost-details]
	* [edpost-message-contact]
	* [edpost-avec-email]
	* [edpost-modifier]
	*/
	private static function shortcodes_edpost_callback($atts, $content = '', $shortcode = null){
		
		$post = edv_Post::get_post();
		
		if($post)
			$post_id = $post->ID;
		
		$label = isset($atts['label']) ? $atts['label'] : '' ;
				
		$html = '';
		
		foreach($atts as $key=>$value){
			if(is_numeric($key)){
				$atts[$value] = true;
				if($key != '0')
					unset($atts[$key]);
			}
		}
		
		if($shortcode == 'edpost'
		&& count($atts) > 0){
			
			$specificInfos = ['titre', 'localisation', 'description', 'dates', 'message-contact', 'modifier', 'details', 'categories'];
			if(array_key_exists('info', $atts)
			&& in_array($atts['info'], $specificInfos))
				$shortcode .= '-' . $atts['info'];
			if(array_key_exists('0', $atts))
				if(is_numeric($atts['0']))
					$atts['post_id'] = $atts['0'];
				elseif( ! array_key_exists('info', $atts))
					if(in_array($atts['0'], $specificInfos))
						$shortcode .= '-' . $atts['0'];
					else
						$atts['info'] = $atts['0'];
					
		}
		$no_html = isset($atts['no-html']) && $atts['no-html']
				|| isset($atts['html']) && $atts['html'] == 'no';
		
		switch($shortcode){
			case 'edp-titre':

				$meta_name = 'edp-' . substr($shortcode, strlen('edp-')) ;
				$val = isset( $post->post_title ) ? $post->post_title : '';
				if($val || $content){
					$val = do_shortcode( wp_kses_post($val . $content));
					if($no_html)
						$html = $val;
					else
						$html = '<div class="edv-edpost edv-'. $shortcode .'">'
							. $val
							. '</div>';
				}
				return $html;
				break;
				
			case 'edp-description':

				$meta_name = 'edp-' . substr($shortcode, strlen('edp-')) ;
				$val = isset( $post->post_content ) ? $post->post_content : '';
				if($val || $content){
					$val = do_shortcode( wp_kses_post($val . $content));
					if($no_html)
						$html = $val;
					else
						$html = '<div class="edv-edpost edv-'. $shortcode .'">'
							. $val
							. '</div>';
				}
				return $html;
				break;
				
			case 'edp-localisation':

				$meta_name = 'edp-' . substr($shortcode, strlen('edp-')) ;
				$val = get_post_meta($post_id, $meta_name, true);
				if($val || $content){
					$val = do_shortcode( wp_kses_post($val . $content));
					if($no_html)
						$html = $val;
					else
						$html = '<div class="edv-edpost edv-'. $shortcode .'">'
							. $val
							. '</div>';
				}
				return $html;
				break;
				
			case 'edp-dates':

				$meta_name = 'edp-' . substr($shortcode, strlen('edp-')) ;
				$val = edv_Post::get_event_dates_text( $post_id );
				if($val || $content){
					$val = do_shortcode( wp_kses_post($val . $content));
					if($no_html)
						$html = $val;
					else
						$html = '<div class="edv-edpost edv-'. $shortcode .'">'
							. $val
							. '</div>';
				}
				return $html;
				break;
				
			case 'edp-cree-depuis':

				$val = date_diff_text($post->post_date);
				
				if($val || $content){
					$val = do_shortcode( wp_kses_post($val . $content));
					if($no_html)
						$html = $val;
					else
						$html = '<div class="edv-edpost edv-'. $shortcode .'">'
							. $val
							. '</div>';
				}
				return $html;
				break;
				
			case 'edp-diffusions':
				$tax_name = edv_Post::taxonomy_diffusion;
			case 'edp-cities':
				if(!isset($tax_name) || !$tax_name)
					$tax_name = edv_Post::taxonomy_city;
			case 'edp-categories':
				if(!isset($tax_name) || !$tax_name)
					$tax_name = edv_Post::taxonomy_edv_category;
				$meta_name = 'edp-' . substr($shortcode, strlen('edp-')) ;
				$terms = edv_Post::get_post_terms( $tax_name, $post_id, 'names');
				if($terms){
					$val = implode(', ', $terms);
					if($no_html)
						$html = $val;
					else{
						$html = '<div class="edv-edpost edv-'. $shortcode .'">'
							. ($label ? '<span class="label"> '.$label.'<span>' : '')
							. htmlentities($val)
							. '</div>';
					}
				}
				return $html;
				break;

			case 'edp-message-contact':
				
				$meta_name = 'edp-organisateur' ;
				$organisateur = edv_Post::get_post_meta($post_id, $meta_name, true, false);
				if( ! $organisateur) {
					return;
				}

				$meta_name = 'edp-email' ;
				$email = edv_Post::get_post_meta($post_id, $meta_name, true, false);
				if(!$email) {
					return edv::icon('warning'
						, 'Vous ne pouvez pas envoyer de message, l\'message n\'a pas indiqué d\'adresse email.', 'edv-error-light', 'div');
				}

				$form_id = edv::get_option('edpost_message_contact_form_id');
				if(!$form_id){
					return edv::icon('warning'
						, 'Un formulaire de message aux organisteurs de message n\'est pas défini dans les réglages de edv.', 'edv-error-light', 'div');
				}

				$val = sprintf('[contact-form-7 id="%s" title="*** message à l\'organisateur de message ***"]', $form_id);
				return '<div class="edv-edpost edv-'. $shortcode .'">'
					. do_shortcode( $val)
					. '</div>';


			case 'edp-modifier':

				return edv_Post_Edit::get_post_edit_content();

			case 'edp-details':

				$html = '';
				$val = isset( $post->post_title ) ? $post->post_title : '';
					if($val)
						$html .= esc_html($val) . '</br>';
					
				$meta_name = 'edp-dates'; 
					$val = edv_Post::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html($val) . '</br>';

				$meta_name = 'edp-organisateur'; 
					$val = edv_Post::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html($val) . '</br>';

				$meta_name = 'edp-localisation';
					$val = edv_Post::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html($val) . '</br>';

				$meta_name = 'edp-email';
					$val = edv_Post::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= make_mailto($val) . '</br>';

				$meta_name = 'edp-siteweb';
					$val = edv_Post::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= make_clickable(esc_html($val)) . '</br>';

				$meta_name = 'edp-phone';
					$val = edv_Post::get_post_meta($post_id, $meta_name, true, false);
					if($val)
						$html .= antispambot($val) . '</br>';
				
				if(! $html )
					return '';
				
				if($no_html){
					$html = do_shortcode( wp_kses_post($html.$content));
					$html = str_ireplace('<br>', "\r\n", $html);
					//TODO
					$html .= sprintf('Evènement créé le %s.\r\n', get_the_date()) ;
					
				}
				else {
					
					// date de création
					$html .= '<div class="entry-details">' ;
					$html .= sprintf('<span>message créé le %s</span>', get_the_date()) ;
					if(get_the_date() != get_the_modified_date())
						$html .= sprintf('<span>, mise à jour du %s</span>', get_the_modified_date()) ;
					$html .= '</div>' ;
					$html = do_shortcode( wp_kses_post($html.$content));
					$html = '<div class="edv-edpost edv-'. $shortcode .'">'
						. ($label ? '<span class="label"> '.$label.'<span>' : '')
						. $html
						. '</div>';
				}
				return $html;
				
			case 'edpost':
				$meta_name = $atts['info'] ;
				if(!$meta_name)
					return '<div class="error">Le paramètre info="'.$meta_name.'" du shortcode "edpost" est inconnu.</div>';
				$val = edv_Post::get_post_meta($post_id, 'edp-' . $meta_name, true, false);
				
				if($val)
					switch($meta_name){
						case 'siteweb' :
							$val = make_clickable(esc_html($val));
							break;
						case 'phone' :
						case 'email' :
							$val = antispambot(esc_html($val), -0.5);
							break;
					}
				if($val || $content){
					return '<div class="edv-edpost">'
						. ($label ? '<span class="label"> '.$label.'<span>' : '')
						. do_shortcode( wp_kses_post($val . $content))
						. '</div>';
				}
				break;

			// shortcode conditionnel
			case 'edp-condition':
				$meta_name = $atts['info'] ;
				if(!$meta_name)
					return '<div class="error">Le paramètre "info" du shortcode "edp-condition" est inconnu.</div>';
				$val = edv_Post::get_post_meta($post_id, 'edp-' . $meta_name, true, false);
				if($val || $content){
					return do_shortcode( wp_kses_post($val . $content));
				}
				break;


			// shortcode conditionnel sur email
			case 'edp-avec-email':
				$meta_name = 'edp-email' ;
				$email = edv_Post::get_post_meta($post_id, $meta_name, true, false);
				if(is_email($email)){
					return do_shortcode( wp_kses_post($content));
				}
				return '';
				

			default:
			
				return '<div class="error">Le shortcode "'.$shortcode.'" inconnu.</div>';
		}
	}
	
	/**
	* [edposts]
	* [edposts liste|list|calendar|calendrier|week|semaine|ics]
	* [edposts mode:liste|list|calendar|calendrier|week|semaine|ics]
	*/
	public static function shortcodes_edposts_callback($atts, $content = '', $shortcode = null){
		
		if($shortcode == 'edposts'
		&& count($atts) > 0){
			if(array_key_exists('mode', $atts))
				$shortcode .= '-' . $atts['mode'];
			elseif(array_key_exists('0', $atts))
				$shortcode .= '-' . $atts['0'];
		}

		switch($shortcode){
			case 'edposts-liste':
				$shortcode = 'edposts-list';
			case 'edposts-list':
				
				return edv_Posts::get_list_html( $content );
				
			case 'edposts-email':
				
				$html = edv_Posts::get_list_for_email( $content );
				return $html;

			default:
				return '<div class="error">Le shortcode "'.$shortcode.'" inconnu.</div>';
		}
	}

 	
	/**
	 * Get code secret from Ajax query, redirect to post url
	 */
	public static function on_wp_ajax_edpost_shortcode_cb() {
		$ajax_response = '';
		$data = $_POST['data'];
		if($data){ 
			if( is_string( $data ) )
				$data  = str_replace('\\"', '"', wp_specialchars_decode( $data ));
			
			$ajax_response = do_shortcode( $data );
			
		}
		// Make your array as json
		wp_send_json($ajax_response);
	 
		// Don't forget to stop execution afterward.
		wp_die();
	}
	
	
	public static function shortcodes_edvstats_callback($atts, $content = '', $shortcode = null){
		require_once(EDV_PLUGIN_DIR . '/admin/class.edv-admin-stats.php');
		if( count($atts)) {
			if( in_array('postscounters', $atts) )
				return edv_Admin_Stats::posts_stats_counters() . $content;
			if( in_array('eventscounters', $atts) )
				return edv_Admin_Stats::edposts_stats_counters() . $content;
			if( in_array('covoituragescounters', $atts) )
				return edv_Admin_Stats::edposts_stats_counters() . $content;
		}
		return edv_Admin_Stats::get_stats_result() . $content;
	}
	
	// shortcodes //
	///////////////
	
	// define the wpcf7_mail_components callback 
	public static function on_wpcf7_mail_components( $components, $wpcf7_get_current_contact_form, $instance ){ 
		$components['body'] = do_shortcode($components['body']);
		return $components;
	} 
}
