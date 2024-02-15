<?php
class edv_Admin_User {

	public static function init() {
		//Add custom user contact Methods
		add_filter( 'user_contactmethods', array( __CLASS__, 'custom_user_contact_methods' ), 10, 2);
		
		if( ! is_network_admin()
		&& (basename($_SERVER['PHP_SELF']) === 'profile.php' 
		|| basename($_SERVER['PHP_SELF']) === 'user-edit.php')) {
			add_action( 'show_user_profile', array(__CLASS__, 'on_custom_user_profil'), 10, 1 );
			add_action( 'edit_user_profile', array(__CLASS__, 'on_custom_user_profil'), 10, 1 );
			add_action( 'insert_custom_user_meta', array(__CLASS__, 'on_insert_custom_user_meta'), 10, 4);
		
		}
		add_action( 'wp_pre_insert_user_data', array(__CLASS__, 'on_wp_pre_insert_user_data'), 10, 4);
		
	}
	
	/**
	 * Surveillance avant création de nouvel utilisateur.
	 * Lutte anti-hack
	 * - wpcore interdit
	 * - Les administrateurs créés ne peuvent pas avoir d'url associée
	 */
	public static function on_wp_pre_insert_user_data($data, $update, $user_id, $userdata){
		if( $data['user_nicename'] === 'wpcore' || $data['user_login'] === 'wpcore'){
			if(empty($data['user_activation_key'])){
				debug_log('on_wp_pre_insert_user_data : wpcore = bastard',$data, $update, $user_id, $userdata);
				return false;
			}
			$data['user_login'] .= '@bastard';
			$data['user_email'] = 'bastard.'.$data['user_email'];
			$data['display_name'] = 'bastard';
		}
		if($update)
			return $data;
		//Les administrateurs créés ne peuvent pas avoir d'url associée
		if($userdata['role'] === 'administrator' && ! empty($userdata['user_url'])){
			debug_log('on_wp_pre_insert_user_data : administrator + user_url = bastard');
			return false;
		}
		return $data;
	}
	
	
	/**
	 * Mise à jour des paramètres spécifiques de l'utilisateur
	 */
	public static function on_insert_custom_user_meta($custom_meta, $user, $update, $userdata){
		foreach(edv_Newsletter::get_newsletters_names() as $newsletter_id =>  $newsletter_name){
			$meta_key = edv_Newsletter::get_subscription_meta_key($newsletter_id);
			if( array_key_exists($meta_key, $_POST))
				$custom_meta[$meta_key] = $_POST[$meta_key];
		}
		return $custom_meta;
	}
	
	/**
	 * A l'affichage de l'édition d'un utilisateur, ajoute des champs spécifiques
	 */
	public static function on_custom_user_profil( $profile_user ) {
		$user_histories = [];
		?><br><h2>Abonnement aux lettres-info du site</h2>

		<table class="form-table" role="presentation"><?php
			$active_newsletters = edv_Newsletter::get_active_newsletters();
			foreach(edv_Newsletter::get_newsletters_names() as $newsletter_id =>  $newsletter_name){
				$subscription_periods = edv_Newsletter::subscription_periods($newsletter_id);		
				$user_subscription = edv_Newsletter::get_subscription($profile_user->user_email, $newsletter_id);
				$user_history = edv_Newsletter::get_user_mailings($profile_user->user_email, $newsletter_id);
				$user_histories = array_merge($user_histories, $user_history);
				$meta_key = edv_Newsletter::get_subscription_meta_key($newsletter_id);
				?><tr class="edv-newsletter-subscription">
					<th><label>Lettre-info "<?=htmlentities($newsletter_name)?>"</label><?php 
						if( empty($active_newsletters[$newsletter_id]))
							echo '<br>' . edv::icon('info', 'inactive');
					?></th>
					<td><ul>
						<?php 
							foreach( $subscription_periods as $subscribe_code => $label){
								$selected = $user_subscription == $subscribe_code ? 'checked' : '';
								echo sprintf('<li><label><input type="radio" name="%s" value="%s" %s>%s</label></li>'
									, $meta_key, $subscribe_code, $selected, $label);
							}
						?></ul>
					</td>
				</tr><?php
			}
			if( count($user_histories) ){?>
			<tr id="edv-newsletter-history">
				<th><label>Derniers envois</label></th>
				<td><ul>
					<?php 
						$newsletters_names = edv_Newsletter::get_newsletters_names();
						foreach( $user_histories as $key => $mailing_date){
							//debug_log('$key = ' . $key);
							$newsletter = [];
							if(is_numeric($key)){
								$newsletter_id = (int)$key;
								$newsletter_name = $newsletters_names[$newsletter_id];	
							}else{
								preg_match_all('/^([^\|]+)\|(.+)$/', $key, $newsletter);
								$newsletter_id = $newsletter[1][0];
								$newsletter_name = $newsletter[2][0];
							}
							$ajax_data = [
								'ID' => $profile_user->ID,
								'nl_id' => $newsletter_id
							];
							echo sprintf('<li><label>%s le %s</label>  %s</li>'
								, $newsletter_name
								, wp_date('d/m/Y', strtotime($mailing_date))
								, edv::get_ajax_action_link($profile_user, 'remove_mailing', 'trash', ' ', 'Cliquez ici pour supprimer cet historique', false, $ajax_data)
							); //TODO h:i:s
						}
					?></ul>
				</td>
			</tr><?php
			}?>
		</table><?php
	}

	// Register User Contact Methods
	public static function custom_user_contact_methods( $user_contact_method, $user ) {

/*		$user_contact_method['email3'] = __( 'Autre email', EDV_TAG );

		$user_contact_method['tel'] = __( 'Téléphone', EDV_TAG );
		$user_contact_method['tel2'] = __( 'Autre téléphone', EDV_TAG );

		$user_contact_method['facebook'] = __( 'Compte Facebook', EDV_TAG );
		$user_contact_method['twitter'] = __( 'Compte Twitter', EDV_TAG );

		$user_contact_method['address'] = __( 'Adresse', EDV_TAG );
		$user_contact_method['address2'] = __( 'Adresse (suite)', EDV_TAG );
		$user_contact_method['city'] = __( 'Code postal et commune', EDV_TAG );
*/
		return $user_contact_method;

	}
	
	/**
	 * Exécution ajax
	 */
	public static function user_action_remove_mailing($data){
		$meta_key = edv_Newsletter::get_mailing_meta_key($_POST['data']['nl_id']);
		if(delete_user_meta($_POST['user_id'], $meta_key))
			return "js:jQuery(this).parents('li:first').remove();";
		return "Echec de suppression de l'information.";
	}

}
