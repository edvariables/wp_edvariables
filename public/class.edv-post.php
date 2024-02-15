<?php

/**
 * edv -> Post
 * Custom post type for WordPress.
 * 
 * Définition du Post Type edpost
 * Définition de la taxonomie edv_category
 * Redirection des emails envoyés depuis une page Post
 * A l'affichage d'un variable, le Content est remplacé par celui du message Modèle
 * En Admin, le bloc d'édition du Content est masqué d'après la définition du Post type : le paramètre 'supports' qui ne contient pas 'editor'
 *
 * Voir aussi edv_Admin_Post
 */
class edv_Post extends edv_Post_Abstract {

	const post_type = 'edpost';
	const taxonomy_edv_category = 'edv_category';
	const taxonomy_city = 'edv_city';
	const taxonomy_diffusion = 'edv_diffusion';

	const secretcode_argument = EDV_POST_SECRETCODE;
	const field_prefix = 'edp-';

	const postid_argument = EDV_ARG_POSTID;
	const posts_page_option = 'posts_page_id';
	const newsletter_option = 'posts_nl_post_id';

	protected static $initiated = false;
	public static function init() {
		if ( ! self::$initiated ) {
			parent::init();
			self::$initiated = true;

			self::init_hooks();
		}
	}

	/**
	 * Hook
	 */
	public static function init_hooks() {
		parent::init_hooks();
		
		add_action( 'wp_ajax_edpost_action', array(__CLASS__, 'on_wp_ajax_edpost_action_cb') );
		add_action( 'wp_ajax_nopriv_edpost_action', array(__CLASS__, 'on_wp_ajax_edpost_action_cb') );
	}
 
 	/**
 	 * Retourne le titre de la page
 	 */
	public static function get_post_title( $edpost = null, $no_html = false) {
 		if( ! isset($edpost) || ! is_object($edpost)){
			global $post;
			$edpost = $post;
		}
		
		$post_title = isset( $edpost->post_title ) ? $edpost->post_title : '';
		$separator = $no_html ? ', ' : '<br>';
		$html = $post_title
			. $separator . self::get_var_dates_text( $edpost->ID )
			. $separator . get_post_meta($edpost->ID, 'edp-localisation', true);
		return $html;
	}
	
 	/**
 	 * Retourne le Content de la page du message
 	 */
	public static function get_post_content( $edpost = null ) {
		global $post;
 		if( ! isset($edpost) || ! is_a($edpost, 'WP_Post')){
			$edpost = $post;
		}

		$codesecret = self::get_secretcode_in_request($edpost);
		
		$html = '[edp-categories label="Catégories : "]
		[edp-cities label="à "]
		[edp-description]
		[edpost info="organisateur" label="Organisateur : "][edp-cree-depuis][/edpost]
		[edpost info="phone" label="Téléphone : "]
		[edpost info="siteweb"]';
		if( edv_Post_Post_type::is_diffusion_managed() )
			$html .='[edp-diffusions label="Diffusion (sous réserve) : "]';

		$meta_name = 'edp-email' ;
		$email = get_post_meta($edpost->ID, $meta_name, true);
		if(is_email($email)){
			$meta_name = 'edp-message-contact';
			$message_contact = get_post_meta($edpost->ID, $meta_name, true);
			if($message_contact){
				$html .= sprintf('[edp-message-contact toggle="Envoyez un message à l\'organisateur" no-ajax post_id="%d" %s]'
						, $edpost->ID
						, $codesecret ? self::secretcode_argument . '=' . $codesecret : ''
				);
			}
		}
		else
			$email = false;
		
		$html .= sprintf('[edp-modifier toggle="Modifier ce message" no-ajax post_id="%d" %s]'
			, $edpost->ID
			, $codesecret ? self::secretcode_argument . '=' . $codesecret : ''
		);
		
		if( $email && current_user_can('manage_options') ){
			$form_id = edv::get_option('admin_message_contact_form_id');
			if(! $form_id){
				return '<p class="">Le formulaire de message à l\'organisateur d\'variable n\'est pas défini dans le paramétrage de edv.</p>';
			}
			$user = wp_get_current_user();
			$html .= sprintf('[toggle title="Message de l\'administrateur (%s) à l\'organisateur du message" no-ajax] [contact-form-7 id="%s"] [/toggle]'
				, $user->display_name, $form_id);
		}
				
		if($email_sent = get_transient(EDV_TAG . '_email_sent_' . $edpost->ID)){
			delete_transient(EDV_TAG . '_email_sent_' . $edpost->ID);
		}
		elseif($no_email = get_transient(EDV_TAG . '_no_email_' . $edpost->ID)){
			delete_transient(EDV_TAG . '_no_email_' . $edpost->ID);
			if(empty($codesecret))
				$secretcode = get_post_meta($post->ID, self::field_prefix.self::secretcode_argument, true);
		}
		
		switch($post->post_status){
			case 'pending':
				$status = 'En attente de relecture';
			case 'future':
				if(empty($status)) $status = 'Pour le futur';
			case 'draft':
				if(empty($status)) $status = 'Brouillon';
				$alerte = sprintf('<p class="alerte">Ce message est <b>en attente de validation</b>, il a le statut "%s".'
					.'<br>Il n\'est <b>pas visible</b> dans les messages.'
					. '</p>'
					. (isset($email_sent) && $email_sent ? '<div class="info">Un e-mail a été envoyé pour permettre la validation de ce nouvel variable. Vérifiez votre boîte mails, la rubrique spam aussi.</div>' : '')
					. (isset($no_email) && $no_email ? '<div class="alerte">Vous n\'avez pas indiqué d\'adresse mail pour permettre la validation de ce nouvel variable.'
											. '<br>Vous devrez attendre la validation par un modérateur pour que ce message soit public.'
											. '<br>Vous pouvez encore modifier ce message et renseigner l\'adresse mail.'
											. '<br>Le code secret de ce message est : <b>'.$secretcode.'</b>'
											. '</div>' : '')
					, $status);
				$html = $alerte . $html;
				break;
				
			case 'publish': 
				if(isset($email_sent) && $email_sent){
					$info = '<div class="info">Ce message est désormais public.'
							. '<br>Un e-mail a été envoyé pour mémoire. Vérifiez votre boîte mails, la rubrique spam aussi.'
						.'</div>';
					$html = $info . $html;
				}
				elseif( isset($no_email) && $no_email) {
					$info = '<div class="alerte">Ce message est désormais public.</div>';
					$html = $info . $html;
				}
				
				$page_id = edv::get_option('posts_page_id');
				if($page_id){
					$url = self::get_post_permalink($page_id, self::secretcode_argument);
					$url = add_query_arg( self::postid_argument, $edpost->ID, $url);
					$url .= '#' . self::postid_argument . $edpost->ID;
					$html .= sprintf('<br><br>Pour voir ce message dans les messages, <a href="%s">cliquez ici %s</a>.'
					, $url
					, edv::icon('calendar-alt'));
				}
				break;
		}
		
			
		if(is_user_logged_in()){
			global $current_user;
			//Rôle autorisé
			if(	$current_user->has_cap( 'edit_posts' ) ){
			
				$creator = new WP_User($edpost->post_author);
				$html .= '<p>créé par "' . $creator->get('user_nicename') . '"</p>';
			}
		}
		return $html;
	}
		
 
 	/**
	 * Dans le cas où WP considère le post comme inaccessible car en statut 'pending' ou 'draft'
	 * alors que le créateur peut le modifier.
 	 */
	/* public static function on_edpost_404( $edpost ) {
		global $post;
		$post = $edpost;
		//Nécessaire pour WPCF7 pour affecter une valeur à _wpcf7_container_post
		global $wp_query;
		$wp_query->in_the_loop = true;
		
		get_header(); ?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main">
			<?php
				get_template_part( 'template-parts/page/content', 'page' );
			?>
		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php
		get_footer();
		exit();
	} */
	
	/*******************
	 * Actions via Ajax
	 *******************/
	/**
	 * Retourne un lien html pour l'envoi d'un mail à l'organisateur
	 */
	public static function get_edpost_contact_email_link($post, $icon = false, $message = null, $title = false, $confirmation = null){
		$html = '';
		$meta_name = 'edp-email' ;
		$email = self::get_post_meta($post, $meta_name, true);
		if(!$email){
			$html .= '<p class="alerte">Ce message n\'a pas d\'adresse e-mail associée.</p>';
		}
		else {
			if(current_user_can('manage_options'))
				$data = [ 'force-new-activation' => true ];
			else
				$data = null;
			$html = self::get_edpost_action_link($post, 'send_email', $icon, $message, $title, $confirmation, $data);
		}
		return $html;
	}
	
	/**
	 * Retourne un lien html pour une action générique
	 */
	public static function get_edpost_action_link($post, $method, $icon = false, $caption = null, $title = false, $confirmation = null, $data = null){
		$need_can_user_change = true;
		switch($method){
			case 'remove':
				if($caption === null)
					$caption = __('Supprimer', EDV_TAG);
				if($confirmation === null || $confirmation === true)
					$confirmation = 'Confirmez-vous la suppression définitive du message ?';
				break;
			case 'duplicate':
				if($caption === null)
					$caption = __('Dupliquer', EDV_TAG);
				if($confirmation === true)
					$confirmation = 'Confirmez-vous la duplication du message ?';
				if($icon === true)
					$icon = 'admin-page';
				break;
			case 'unpublish':
				if($caption === null)
					$caption = __('Masquer dans les messages', EDV_TAG);
				if($icon === true)
					$icon = 'hidden';
				if($confirmation === null || $confirmation === true)
					$confirmation = 'Confirmez-vous que l\'variable ne sera plus visible ?';

				break;
			case 'publish':
				if($caption === null)
					$caption = __('Rendre public dans les messages', EDV_TAG);
				if($icon === true)
					$icon = 'visibility';
				if($confirmation === null || $confirmation === true)
					$confirmation = 'Confirmez-vous de rendre visible l\'variable ?';
				
				break;
			case 'send_email':
				$need_can_user_change = false;
				$meta_name = 'edp-email' ;
				$email = self::get_post_meta($post, $meta_name, true);
				$email_parts = explode('@', $email);
				$email_trunc = substr($email, 0, 3) . str_repeat('*', strlen($email_parts[0])-3);
				if($caption === null){
					$caption = 'E-mail de validation';
					$title = sprintf('Cliquez ici pour envoyer un e-mail de validation du message à l\'adresse %s@%s', $email_trunc, $email_parts[1]);
				}
				if($icon === true)
					$icon = 'email-alt';
				if($confirmation === null || $confirmation === true)
					$confirmation = sprintf('Confirmez-vous l\'envoi d\'un e-mail à l\'adresse %s@%s', $email_trunc, $email_parts[1]);
				break;
			default:
				if(!$caption)
					$caption = __($method, EDV_TAG);
				
				break;
		}
		if(!$title)
			$title = $caption;
		
		if($icon === true)
			$icon = $method;
		$html = '';
		if($need_can_user_change && ! self::user_can_change_post($post)){
			$html .= '<p class="alerte">Ce message ne peut pas être modifié par vos soins.</p>';
		}
		else {
			
			$post_id = is_object($post) ? $post->ID : $post;
			$query = [
				'post_id' => $post_id,
				'action' => self::post_type . '_action',
				'method' => $method
			];
			if($data)
				$query['data'] = $data;
				
			//Maintient la transmission du code secret
			$ekey = self::get_secretcode_in_request($post_id);
			if($ekey)
				$query[self::secretcode_argument] = $ekey;

			if($confirmation){
				$query['confirm'] = $confirmation;
			}
			if($icon)
				$icon = edv::icon($icon);
			$html .= sprintf('<span><a href="#" title="%s" class="edv-ajax-action edv-ajax-%s" data="%s">%s%s</a></span>'
				, $title ? $title : ''
				, $method
				, esc_attr( json_encode($query) )
				, $icon ? $icon . ' ' : ''
				, $caption);
		}
		return $html;
	}
	
	/**
	 * Action required from Ajax query
	 * 
	 */
	public static function on_wp_ajax_edpost_action_cb() {
		
		// debug_log('on_wp_ajax_edpost_action_cb');	
		
		if( ! edv::check_nonce())
			wp_die();
		
		$ajax_response = '0';
		if(!array_key_exists("method", $_POST)){
			wp_die();
		}
		$method = $_POST['method'];
		if(array_key_exists("post_id", $_POST)){
			try{
				//cherche une fonction du nom "edpost_action_{method}"
				$function = array(__CLASS__, sprintf('edpost_action_%s', $method));
				$ajax_response = call_user_func( $function, $_POST['post_id']);
			}
			catch( Exception $e ){
				$ajax_response = sprintf('Erreur dans l\'exécution de la fonction :%s', var_export($e, true));
			}
		}
		echo $ajax_response;
		
		// Make your array as json
		//wp_send_json($ajax_response);
	 
		// Don't forget to stop execution afterward.
		wp_die();
	}
	
	/**
	 * Remove var
	 */
	public static function edpost_action_remove($post_id) {
		if ( self::do_remove($post_id) )
			return 'redir:' . edv_Posts::get_url(); //TODO add month in url
		return 'Impossible de supprimer ce message.';
	}
	
	/**
	 * Duplicate var
	 */
	public static function edpost_action_duplicate($post_id) {
		if ( self::user_can_change_post($post_id) )
			return 'redir:' . add_query_arg(
				'action', 'duplicate'
				, add_query_arg(self::postid_argument, $post_id
					, get_page_link(edv::get_option('new_post_page_id'))
				)
			);
		return 'Impossible de retrouver ce message.';
	}
	
	/**
	 * Unpublish var
	 */
	public static function edpost_action_unpublish($post_id) {
		$post_status = 'pending';
		if( self::change_post_status($post_id, $post_status) )
			return 'redir:' . self::get_post_permalink($post_id, true, self::secretcode_argument, 'etat=en-attente');
		return 'Impossible de modifier ce message.';
	}
	/**
	 * Publish var
	 */
	public static function edpost_action_publish($post_id) {
		$post_status = 'publish';
		if( (! self::waiting_for_activation($post_id)
			|| current_user_can('manage_options') )
		&& self::change_post_status($post_id, $post_status) )
			return 'redir:' . self::get_post_permalink($post_id, self::secretcode_argument);
		return 'Impossible de modifier le statut.<br>Ceci peut être effectué depuis l\'e-mail de validation.';
	}
	/**
	 * Send contact email
	 */
	public static function edpost_action_send_email($post_id) {
		if(isset($_POST['data']) && is_array($_POST['data'])
		&& isset($_POST['data']['force-new-activation']) && $_POST['data']['force-new-activation']){
			self::get_activation_key($post_id, true); //reset
		}
		return self::send_validation_email($post_id);
	}
	
	/**
	 * Remove var
	 */
	public static function do_remove($post_id) {
		if(self::user_can_change_post($post_id)){
			// $post = wp_delete_post($post_id);
			$post = self::change_post_status($post_id, 'trash');
			return ! is_a($post, 'WP_Error');
		}
		// echo self::user_can_change_post($post_id, false, true);
		return false;
	}
	
	/**
	 * Envoye le mail à l'organisateur du message
	 */
	public static function send_validation_email($post, $subject = false, $message = false, $return_html_result = false){
		if(is_numeric($post)){
			$post_id = $post;
			$post = get_post($post_id);
		}
		else
			$post_id = $post->ID;
		
		if(!$post_id)
			return false;
		
		$codesecret = self::get_post_meta($post, self::field_prefix . self::secretcode_argument, true);
		
		$meta_name = 'edp-email' ;
		$email = self::get_post_meta($post, $meta_name, true);
		$to = $email;
		
		$site = get_bloginfo( 'name' );
		
		$subject = sprintf('[%s][Validation] %s', $site, $subject ? $subject : $post->post_title);
		
		$headers = array();
		$attachments = array();
		
		if( ! $message){
			$message = sprintf('Bonjour,<br>Vous recevez ce message suite la création du message ci-dessous ou à une demande depuis le site et parce que votre e-mail est associé à l\'variable.');

		}
		else
			$message .= '<br>'.str_repeat('-', 20);
		
		$url = self::get_post_permalink($post, true);
		
		switch($post->post_status){
			case 'pending':
				$status = 'En attente de relecture';
			case 'draft':
				if(!$status) $status = 'Brouillon';
				$message .= sprintf('<br><br>Ce message n\'est <b>pas visible</b> en ligne, il est marqué comme "%s".', $status);
				
				if( self::waiting_for_activation($post) ){
					$activation_url = add_query_arg(self::secretcode_argument, $codesecret, $url);
					$activation_url = add_query_arg('action', 'activation', $activation_url);
					$activation_url = add_query_arg('ak', self::get_activation_key($post), $activation_url);
					$activation_url = add_query_arg('etat', 'en-attente', $activation_url);
				}
				
				$message .= sprintf('<br><br><a href="%s"><b>Cliquez ici pour rendre ce message public dans les messages</b></a>.<br>', $activation_url);
				break;
			case 'trash':
				$message .= sprintf('<br><br>Ce message a été SUPPRIMÉ.');
				break;
		}
		
		$message .= sprintf('<br><br>Le code secret de ce message est : %s', $codesecret);
		// $args = self::secretcode_argument .'='. $codesecret;
		// $codesecret_url = $url . (strpos($url,'?')>0 || strpos($args,'?') ? '&' : '?') . $args;			
		$codesecret_url = add_query_arg(self::secretcode_argument, $codesecret, $url);
		$message .= sprintf('<br><br>Pour modifier ce message, <a href="%s">cliquez ici</a>', $codesecret_url);
		
		$url = self::get_post_permalink($post);
		$message .= sprintf('<br><br>La page publique de ce message est : <a href="%s">%s</a>', $url, $url);

		$message .= '<br><br>Bien cordialement,<br>L\'équipe du site.';
		
		$message .= '<br>'.str_repeat('-', 20);
		$message .= sprintf('<br><br>Détails du message :<br><code>%s</code>', self::get_post_details_for_email($post));
		
		$message = quoted_printable_encode(str_replace('\n', '<br>', $message));

		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=utf-8';
		$headers[] = 'Content-Transfer-Encoding: quoted-printable';

		if($success = wp_mail( $to
			, '=?UTF-8?B?' . base64_encode($subject). '?='
			, $message
			, $headers, $attachments )){
			$html = '<div class="info email-send">L\'e-mail a été envoyé.</div>';
		}
		else{
			$html = sprintf('<div class="email-send alerte">L\'e-mail n\'a pas pu être envoyé.</div>');
		}
		if($return_html_result){
			if($return_html_result === 'bool')
				return $success;
			else
				return $html;
		}
		echo $html;
	}
	
	/**
	 * Détails du message pour insertion dans un email
	 */
	public static function get_post_details_for_email($post){
		if(is_numeric($post)){
			$post = get_post($post);
		}
		$post_id = $post->ID;
		$html = '<table><tbody>';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Titre', htmlentities($post->post_title));
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Dates', self::get_var_dates_text($post_id));
		$meta_name = 'edp-localisation';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Lieu', htmlentities(get_post_meta($post_id, $meta_name, true)));
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Communes', htmlentities(implode(', ', self::get_var_cities ($post_id, 'names'))));
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Catégories', htmlentities(implode(', ', self::get_var_categories ($post_id, 'names'))));
		$html .= sprintf('<tr><td>%s : </td><td><pre>%s</pre></td></tr>', 'Description', htmlentities($post->post_content));
		$meta_name = 'edp-organisateur';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Organisateur', htmlentities(get_post_meta($post_id, $meta_name, true)));
		$meta_name = 'edp-phone';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Téléphone', htmlentities(get_post_meta($post_id, $meta_name, true)));
		$meta_name = 'edp-siteweb';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Site web', htmlentities(get_post_meta($post_id, $meta_name, true)));
		$meta_name = 'edp-email';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Email', get_post_meta($post_id, $meta_name, true));
		$meta_name = 'edp-email-show';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Afficher l\'e-mail', get_post_meta($post_id, $meta_name, true) ? 'oui' : 'non');
		$meta_name = 'edp-message-contact';
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Recevoir des messages', get_post_meta($post_id, $meta_name, true) ? 'oui' : 'non');
		$html .= sprintf('<tr><td>%s : </td><td>%s</td></tr>', 'Diffusion (sous réserve)', htmlentities(implode(', ', self::get_var_diffusions ($post_id, 'names'))));
		
		$html .= '</tbody></table>';
		return $html;
		
	}
		
	/**
	 * Retourne le texte des dates et heures d'un variable
	 */
	public static function get_var_dates_text( $post_id ) {
		if(is_object($post_id))
			$post_id = $post_id->ID;
		$date_debut    = get_post_meta( $post_id, 'edp-date-debut', true );
		$date_jour_entier    = get_post_meta( $post_id, 'edp-date-journee-entiere', true );
		$heure_debut    = get_post_meta( $post_id, 'edp-heure-debut', true );
		$date_fin    = get_post_meta( $post_id, 'edp-date-fin', true );
		$heure_fin    = get_post_meta( $post_id, 'edp-heure-fin', true );
		if(mysql2date( 'j', $date_debut ) === '1')
			$format_date_debut = 'l j\e\r M Y';
		else
			$format_date_debut = 'l j M Y';
		if($date_fin && mysql2date( 'j', $date_fin ) === '1')
			$format_date_fin = 'l j\e\r M Y';
		else
			$format_date_fin = 'l j M Y';
		return mb_strtolower( trim(
			  ($date_fin && $date_fin != $date_debut ? 'du ' : '')
			. ($date_debut ? str_ireplace(' mar ', ' mars ', mysql2date( $format_date_debut, $date_debut )) : '')
			. (/* !$date_jour_entier && */ $heure_debut 
				? ($heure_fin ? ' de ' : ' à ') . $heure_debut : '')
			. ($date_fin && $date_fin != $date_debut ? ' au ' . str_ireplace(' mar ', ' mars ', mysql2date( $format_date_fin, $date_fin )) : '')
			. (/* !$date_jour_entier && */ $heure_fin 
				? ($heure_debut ? ' à ' : ' jusqu\'à ')  . $heure_fin
				: '')
		));
	}
	
	/**
	 * Retourne les catégories d'un variable
	 */
	public static function get_var_categories( $post_id, $args = 'names' ) {
		return self::get_post_terms( self::taxonomy_edv_category, $post_id, $args);
	}
	/**
	 * Retourne les communes d'un variable
	 */
	public static function get_var_cities( $post_id, $args = 'names' ) {
		return self::get_post_terms( self::taxonomy_city, $post_id, $args);
	}
	/**
	 * Retourne les diffusions possibles d'un variable
	 */
	public static function get_var_diffusions( $post_id, $args = 'names' ) {
		return self::get_post_terms( self::taxonomy_diffusion, $post_id, $args);
	}
	
 	protected static function wpcf7_contact_form_init_tags( $form ) { 
		$html = $form->prop('form');//avec shortcodes du wpcf7
		$requested_id = isset($_REQUEST[self::postid_argument]) ? $_REQUEST[self::postid_argument] : false;
		if( ! ($edpost = self::get_post($requested_id)))
			return;
		
		/** init message **/
		$message = sprintf("Bonjour,\r\nJe vous écris à propos de \"%s\" (%s) du %s.\r\n%s\r\n\r\n-"
			, $edpost->post_title
			, self::get_post_meta($edpost, 'edp-localisation', true)
			, self::get_var_dates_text($edpost)
			, get_post_permalink($edpost)
		);
		$matches = [];
		if( ! preg_match_all('/(\[textarea[^\]]*\])([\s\S]*)(\[\/textarea)?/', $html, $matches))
			return;
		for($i = 0; $i < count($matches[0]); $i++){
			if( strpos( $matches[2][$i], "[/textarea") === false ){
				$message .= '[/textarea]';
			}
			$html = str_replace( $matches[1][$i]
					, sprintf('%s%s', $matches[1][$i], $message)
					, $html);
		}
		$user = wp_get_current_user();
		if( $user ){
		
			/** init name **/	
			$html = preg_replace( '/(autocomplete\:name[^\]]*)\]/'
					, sprintf('$1 "%s"]', $user->display_name)
					, $html);
		
			/** init email **/	
			$html = preg_replace( '/(\[email[^\]]*)\]/'
					, sprintf('$1 "%s"]', $user->user_email)
					, $html);
		}
		
		/** set **/
		$form->set_properties(array('form'=>$html));
		
	}
}
