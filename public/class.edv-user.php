<?php
class edv_User {

	public static function init() {
		
	}

	public static function create_user_for_edpost($email = false, $user_name = false, $user_login = false, $data = false, $user_role = false){

		if( ! $email){
			$post = get_post();
			$email = get_post_meta($post->ID, 'edp-email', true);
		}
		
		$user_id = email_exists( $email );
		if($user_id){
			return self::promote_user_to_blog(new WP_User( $user_id ));
		}

		if(!$user_login) {
			$user_login = sanitize_key( $user_name ? $user_name : $email );
		}
		if(!$user_id && $user_login) {
			$i = 2;
			while(username_exists( $user_login)){
				$user_login .= $i++;
			}
		}

		// Generate the password and create the user
		$password = wp_generate_password( 12, false );
		$user_id = wp_create_user( $user_login ? $user_login : $email, $password, $email );

		if( is_wp_error($user_id) ){
			return $user_id;
		}

		if( ! is_array($data))
			$data = array();
		$data = array_merge($data, 
			array(
				'ID'				=>	$user_id,
				'nickname'			=>	$user_name ? $user_name : ($user_login ? $user_login : $email),
				'first_name'			=>	$user_name ? $user_name : ($user_login ? $user_login : $email),
				'display_name'			=>	$user_name ? $user_name : ($user_login ? $user_login : $email)

			)
		);

		wp_update_user($data);

		// Set the role
		$user = new WP_User( $user_id );
		if($user) {
			if( ! $user_role)
				$user_role = 'subscriber';
			$user->set_role( $user_role );
			/*if($user->Errors){

			}
			else {
				// Email the user
				//wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );
			}*/		
		}
		
		return self::promote_user_to_blog($user);
	}

	private static function promote_user_to_blog( WP_User $user, $blog = false ){
		if( ! $blog )
			$blog_id = get_current_blog_id();
		elseif(is_object($blog))//TODO
			$blog_id = $blog->ID;
		else //TODO
			$blog_id = $blog;

		//copie from wp-admin/user-new.php ligne 64
		// Adding an existing user to this blog.
		if ( ! array_key_exists( $blog_id, get_blogs_of_user( $user->ID ) ) ) {

			if( current_user_can( 'promote_user', $user->ID )  ){
				$result = add_existing_user_to_blog(
					array(
						'user_id' => $user->ID,
						'role'    => edv_Post::user_role,
					)
				);
				if(is_wp_error($result)){
					edv_Admin::add_admin_notice( sprintf(__("L'utilisateur %s n'a pas accès à ce site web pour la raison suivante : %s", EDV_TAG), $user->display_name, $result->get_error_message()), 'error');
				}
				else {
					edv_Admin::add_admin_notice( sprintf(__("Désormais, l'utilisateur %s a accès à ce site web en tant qu'organisateur d'évènements.", EDV_TAG), $user->display_name), 'success');
				}
			}
			else{
				edv_Admin::add_admin_notice( sprintf(__("L'utilisateur %s n'a pas accès à ce site web et vous n'avez pas l'autorisation de le lui accorder. Contactez un administrateur de niveau supérieur.", EDV_TAG), $user->display_name), 'warning');
			}
		}
		return $user;
	}

	//TODO
	public static function get_blog_admin_id(){
		$email = get_bloginfo('admin_email');
		return null;
	}

	/**
	 * Retourne un blog auquel appartient l'utilisateur et en priorité le blog en cours
	 */
	public static function get_current_or_default_blog_id($user){
		$blog_id = get_current_blog_id();
		if($user){
			$blogs = get_blogs_of_user($user->ID);
			if( ! array_key_exists($blog_id, $blogs))
				foreach($blogs as $blog){
					$blog_id = $blog->userblog_id;
					break;
				}
		}
		return $blog_id;
	}


	 
	 

	/**
	 * Dans un email à un utilisateur, ajoute une invitation à saisir un nouveau mot de passe.
	 * Returns a string to add to email for user to reset his password.
	 */
	private static function new_password_link($user_id, $redirect_to = false){
		if(is_a($user_id, 'WP_User')){
			$user = $user_id;
			$user_id = $user->ID;
		}
		if(is_super_admin($user_id)
		|| $user_id == edv_User::get_blog_admin_id()
		)
			return;
		if( ! isset($user))
			$user = new WP_USER($user_id);
		$password_key = get_password_reset_key($user);
		if( ! $password_key)
			return;
		// $redirect_to = get_home_url( get_current_blog_id(), sprintf("wp-login.php?login=%s", rawurlencode( $user->user_login )), 'login' );
		if(!$redirect_to)
			$redirect_to = get_home_url();
		$url = sprintf("wp-login.php?action=rp&key=%s&login=%s&redirect_to=%s", $password_key, rawurlencode( $user->user_login ), esc_url($redirect_to));
		$url = network_site_url( $url );
		$message = sprintf(__( 'Pour définir votre mot de passe, <a href="%s">vous devez cliquer ici</a>.', EDV_TAG) , $url ) . "\r\n";
		return $message;
	}
	
	
	
	/**
	 * Envoye le mail de bienvenu après inscription et renouvellement de mot de passe
	 */
	public static function send_welcome_email($user_id, $subject = false, $message = false, $return_html_result = false){
		if(is_a($user_id, 'WP_User')){
			$user = $user_id;
			$user_id = $user->ID;
		}
		
		if(!$user_id)
			return false;
		
		if( ! isset($user))
			$user = new WP_USER($user_id);
		
		$email = $user->user_email;
		$to = $email;
		
		$site = get_bloginfo( 'name' );
		
		$subject = sprintf('[%s] %s', $site, $subject ? $subject : 'Inscription de votre compte');
		
		$headers = array();
		$attachments = array();
		
		if( ! $message){
			$message = sprintf('Bonjour,<br>Vous recevez ce message suite la création votre compte.');

		}
		else
			$message .= '<br>'.str_repeat('-', 20);
		
		$message .= "<br><br>" . self::new_password_link($user_id);
		
		$message .= '<br>';
		$url = get_permalink(edv::get_option('newsletter_subscribe_page_id'));
		$url = add_query_arg('email', $email, $url);
		$subscription_period_name = edv_Newsletter::subscription_period_name(edv_Newsletter::get_subscription($email), true);
		if($subscription_period_name)
			$message .= sprintf('<br>Votre inscription actuelle à la lettre-info est "%s".', $subscription_period_name);
		$message .= sprintf('<br>Vous pouvez modifier votre inscription à la lettre-info en <a href="%s">cliquant ici</a>.', $url);

		$message .= '<br><br>Bien cordialement,'
			. sprintf('<br>L\'équipe de <a href="%s">%s</a>.', get_bloginfo('url'), get_bloginfo('name'));
		
		
		
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
			error_log(sprintf("send_welcome_email : L'e-mail n'a pas pu être envoyé à %s.\r\nHeaders : %s\r\Subject : %s\r\nMessage : %s", $email, var_export($headers), $subject, $message));
		}
		if($return_html_result){
			if($return_html_result == 'bool')
				return $success;
			else
				return $html;
		}
		echo $html;
	}
}
