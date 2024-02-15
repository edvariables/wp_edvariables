<?php 

/**
 * Chargé lors du hook admin_menu qui est avant le admin_init
 * Edition des options du plugin
 */
class edv_Admin_Menu {

	static $initialized = false;

	public static function init() {
		if(self::$initialized)
			return;
		self::$initialized = true;
		self::init_includes();
		self::init_hooks();
		self::init_settings();
		self::init_admin_menu();
	}

	public static function init_includes() {
	}

	public static function init_hooks() {

		//TODO
		// Le hook admin_menu est avant le admin_init
		//add_action( 'admin_menu', array( __CLASS__, 'init_admin_menu' ), 5 ); 
		add_action('wp_dashboard_setup', array(__CLASS__, 'init_dashboard_widgets') );
		
		add_action('admin_head', array(__CLASS__, 'add_form_enctype'));
		add_action('admin_head', array(__CLASS__, 'init_js_sections_tabs'));

		
	}

	public static function init_settings(){
		// register a new setting for "edv" page
		register_setting( EDV_TAG, EDV_TAG );
		
		$section_args = array(
			'before_section' => '<div class="edv-tabs-wrap">',
			'after_section' => '</div>'
		);
		
		add_settings_section(
			'edv_section_checkup',
			'',
			array(__CLASS__, 'check_module_health'),
			EDV_TAG, $section_args
		);
		
		add_settings_section(
			'edv_section_pages',
			__( 'Références des pages et formulaires (Contacts)', EDV_TAG ),
			array(__CLASS__, 'settings_sections_cb'),
			EDV_TAG, $section_args
		);

			// 
			$field_id = 'blog_presentation_page_id';
			add_settings_field(
				$field_id, 
				edv::get_option_label($field_id),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_pages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'newsletter_subscribe_page_id';
			add_settings_field(
				$field_id, 
				__( 'Page d\'inscription à la lettre-info', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_pages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'newsletter_subscribe_form_id';
			add_settings_field(
				$field_id, 
				__( 'Formulaire d\'inscription à la lettre-info', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_pages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => WPCF7_ContactForm::post_type
				]
			);

			// 
			$field_id = 'contact_page_id';
			add_settings_field(
				$field_id, 
				__( 'Page "Ecrivez-nous".', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_pages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'contact_form_id';
			add_settings_field(
				$field_id, 
				__( 'Formulaire "Ecrivez-nous"', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_pages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => WPCF7_ContactForm::post_type
				]
			);

			// 
			$field_id = 'admin_nl_post_id';
			add_settings_field(
				$field_id, 
				edv::get_option_label($field_id),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_pages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => edv_Newsletter::post_type
				]
			);

		// register a new section in the "edv" page
		add_settings_section(
			'edv_section_edposts',
			__( 'Messages', EDV_TAG ),
			array(__CLASS__, 'settings_sections_cb'),
			EDV_TAG, $section_args
		);

			// 
			$field_id = 'agenda_page_id';
			add_settings_field(
				$field_id, 
				__( 'Page de l\'agenda', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'new_post_page_id';
			add_settings_field(
				$field_id, 
				edv::get_option_label($field_id),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'edpost_edit_form_id';
			add_settings_field(
				$field_id, 
				__( 'Formulaire d\'ajout et modification des messages', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => WPCF7_ContactForm::post_type
				]
			);

			// 
			$field_id = 'edpost_message_contact_form_id';
			add_settings_field(
				$field_id, 
				__( 'Message à l\'organisateur de message', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => WPCF7_ContactForm::post_type
				]
			);

			// 
			$field_id = 'admin_message_contact_form_id';
			add_settings_field(
				$field_id, 
				__( 'Message de la part de l\'administrateur à l\'organisateur', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => WPCF7_ContactForm::post_type
				]
			);

			// 
			$field_id = 'posts_nl_post_id';
			add_settings_field(
				$field_id, 
				edv::get_option_label($field_id),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => edv_Newsletter::post_type
				]
			);

			// 
			$field_id = 'newsletter_diffusion_term_id';
			add_settings_field(
				$field_id, 
				__( 'Diffusion "Lettre-info"', EDV_TAG ),
				array(__CLASS__, 'edv_combos_terms_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'taxonomy' => edv_Post::taxonomy_diffusion
				]
			);

			// 
			$field_id = 'post_need_validation';
			add_settings_field(
				$field_id, 
				__( 'Statut des nouveaux messages', EDV_TAG ),
				array(__CLASS__, 'edv_input_cb'),
				EDV_TAG,
				'edv_section_edposts',
				[
					'label_for' => $field_id,
					'label' => edv::get_option_label($field_id),
					'learn-more' => [__( 'Si l\'utilisateur est connecté, le message est toujours publié.', EDV_TAG )
									, __( 'Si vous cochez cette option, la saise des messages va se complexifier pour les utilisateurs.', EDV_TAG )
									, __( 'Même si cette option n\'est pas cochée, les utilisateurs non connectés reçoivent un email.', EDV_TAG )
									, __( 'Cochez si vous voulez limiter et tracer les intrusions ou abus.', EDV_TAG )],
					'class' => 'edv_row',
					'input_type' => 'checkbox'
				]
			);
			
		//////////////////////////////////////////
		// register a new section in the "edv" page
		add_settings_section(
			'edv_section_covoiturages',
			__( 'Covoiturages', EDV_TAG ),
			array(__CLASS__, 'settings_sections_cb'),
			EDV_TAG, $section_args
		);

			// 
			$field_id = 'covoiturages_page_id';
			add_settings_field(
				$field_id, 
				__( 'Page des covoiturages', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_covoiturages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'new_covoiturage_page_id';
			add_settings_field(
				$field_id, 
				edv::get_option_label($field_id),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_covoiturages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => 'page'
				]
			);

			// 
			$field_id = 'covoiturage_edit_form_id';
			add_settings_field(
				$field_id, 
				__( 'Formulaire d\'ajout et modification de covoiturage', EDV_TAG ),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_covoiturages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => WPCF7_ContactForm::post_type
				]
			);

			// 
			$field_id = 'covoiturages_nl_post_id';
			add_settings_field(
				$field_id, 
				edv::get_option_label($field_id),
				array(__CLASS__, 'edv_combos_posts_cb'),
				EDV_TAG,
				'edv_section_covoiturages',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
					'post_type' => edv_Newsletter::post_type
				]
			);

			// 
			$field_id = 'covoiturage_need_validation';
			add_settings_field(
				$field_id, 
				__( 'Statut des nouveaux covoiturages', EDV_TAG ),
				array(__CLASS__, 'edv_input_cb'),
				EDV_TAG,
				'edv_section_covoiturages',
				[
					'label_for' => $field_id,
					'label' => edv::get_option_label($field_id),
					'learn-more' => [__( 'Si l\'utilisateur est connecté, le covoiturage est toujours publié.', EDV_TAG )
									, __( 'Si vous cochez cette option, la saise des covoiturages va se complexifier pour les utilisateurs.', EDV_TAG )
									, __( 'Même si cette option n\'est pas cochée, les utilisateurs non connectés reçoivent un email.', EDV_TAG )
									, __( 'Cochez si vous voulez limiter et tracer les intrusions ou abus.', EDV_TAG )],
					'class' => 'edv_row',
					'input_type' => 'checkbox'
				]
			);
			
			
		//////////////////////////////////////////

		// register a new section in the "edv" page
		add_settings_section(
			'edv_section_security',
			__( 'Divers', EDV_TAG ),
			array(__CLASS__, 'settings_sections_cb'),
			EDV_TAG, $section_args
		);

			// 
			$field_id = EDV_CONNECT_MENU_ENABLE;
			add_settings_field(
				$field_id, 
				__( 'Affichage du menu "Se connecter"', EDV_TAG ),
				array(__CLASS__, 'edv_input_cb'),
				EDV_TAG,
				'edv_section_security',
				[
					'label_for' => $field_id,
					'label' => __( 'Afficher', EDV_TAG ),
					'class' => 'edv_row',
					'input_type' => 'checkbox'
				]
			);

			// 
			$field_id = EDV_MAILLOG_ENABLE;
			add_settings_field(
				$field_id, 
				__( 'Traçage des mails', EDV_TAG ),
				array(__CLASS__, 'edv_input_cb'),
				EDV_TAG,
				'edv_section_security',
				[
					'label_for' => $field_id,
					'label' => __( 'Activer', EDV_TAG ),
					'learn-more' => [__( 'A n\'utiliser que pour la poursuite d\'envois abusifs ou de robots.', EDV_TAG )
									, __( 'Pensez à purger les Traces mail et à vider la corbeille.', EDV_TAG )],
					'class' => 'edv_row',
					'input_type' => 'checkbox'
				]
			);

			// 
			$field_id = EDV_DEBUGLOG_ENABLE;
			add_settings_field(
				$field_id, 
				__( 'Traçage des alertes', EDV_TAG ),
				array(__CLASS__, 'edv_input_cb'),
				EDV_TAG,
				'edv_section_security',
				[
					'label_for' => $field_id,
					'label' => __( 'Activer', EDV_TAG ),
					'learn-more' => [sprintf(__( 'Des traces sont disponibles dans le fichier %s.', EDV_TAG ), debug_log_file())],
					'class' => 'edv_row',
					'input_type' => 'checkbox'
				]
			);

		// register a new section in the "edv" page
		add_settings_section(
			'edv_section_edposts_import',
			__( 'Importation de messages', EDV_TAG ),
			array(__CLASS__, 'settings_sections_cb'),
			EDV_TAG, $section_args
		);

			// 
			$field_id = 'edpost_import_ics';
			add_settings_field(
				$field_id, 
				__( 'Importation d\'un fichier ICS', EDV_TAG ),
				array(__CLASS__, 'edv_import_ics_cb'),
				EDV_TAG,
				'edv_section_edposts_import',
				[
					'label_for' => $field_id,
					'class' => 'edv_row',
				]
			);
		
		if(is_multisite()){
			// register a new section in the "edv" page
			add_settings_section(
				'edv_section_blog_import',
				__( 'Initialisation du site', EDV_TAG ),
				array(__CLASS__, 'settings_sections_cb'),
				EDV_TAG, $section_args
			);
			if( get_current_blog_id() !== BLOG_ID_CURRENT_SITE ){
				// 
				$field_id = 'site_import';
				add_settings_field(
					$field_id, 
					__( 'Importation des données du site principal', EDV_TAG ),
					array(__CLASS__, 'edv_site_import_cb'),
					EDV_TAG,
					'edv_section_blog_import',
					[
						'label_for' => $field_id,
						'class' => 'edv_row',
					]
				);
			}
		}
	}


	
	public static function init_js_sections_tabs() {
		
		?><script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('form > div.edv-tabs-wrap:first').each(function(){
			var id = 'edv-tabs';
			var tabs_counter = 0;
			var $tabs_contents = [];
			var $tabs = jQuery(sprintf('<div id="%s"/>', id));
			var $nav = jQuery(sprintf('<ul id="%s-nav"/>', id)).appendTo($tabs);
			var $contents = jQuery('<ul/>').appendTo($tabs);
			var $submit = jQuery(this).find('p.submit');
			jQuery(this).find('div.edv-tabs-wrap > h2').each(function(){
				tabs_counter++;
				$nav.append(sprintf('<li><a href="#%s-%d">%s</a></li>', id, tabs_counter, this.innerText));
				var $content = jQuery(sprintf('<div id="%s-%d" class="edv-panel"><div/>', id, tabs_counter));
				jQuery(this).parent().children().appendTo($content);
				$contents.append($content);
			});
			jQuery(this)
				.html( $tabs.tabs() )
				.append($submit);
		});
	});</script>
		<?php
	}
	
	/**
	* Modifie la balise <from en ajoutant l'attribut enctype="multipart/form-data".
	* Injecté dans le <head>
	*/
	public static function add_form_enctype() {
		?><script type="text/javascript">
				jQuery(document).ready(function(){
					var $form = jQuery('#wpbody-content form:first');
					var $input = $form.children('input[name="option_page"][value="<?php echo(EDV_TAG)?>"]:first');
					if($input.length){
						$form.attr('enctype','multipart/form-data');
						$form.attr('encoding', 'multipart/form-data');
					}
				});
			</script>";<?php
	}
	
	/**
	 * Section
	 */
	public static function settings_sections_cb($args ) {
		
		switch($args['id']){
			case 'edv_section_security' : 
				$message = __('Paramètres de surveillance de l\'activité', EDV_TAG);
				break;
			case 'edv_section_pages' : 
				// $message = __('Paramètres concernant les références de pages et formulaires', EDV_TAG);
				break;
			case 'edv_section_edposts' : 
				$message = __('Paramètres concernant les messages, leurs pages et formulaires.', EDV_TAG);
				break;
			case 'edv_section_edposts_import' : 
				$message = __('Importation de messages depuis une source externe', EDV_TAG);
				break;
			case 'edv_section_blog_import' : 
				if( get_current_blog_id() === BLOG_ID_CURRENT_SITE ){
					?><p id="<?php echo esc_attr( $args['id'] ); ?>" class="dashicons-before dashicons-welcome-learn-more">Vous êtes sur le site principal, vous ne pouvez pas faire d'importation de modèles.</p><?php
					return;
				}
				$message = __('Importation des éléments de base depuis un autre site d\'agenda partagé.', EDV_TAG);
				break;
			default : 
				$message = '';
		}
		if( ! empty($message) ){
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e(  $message, EDV_TAG ); ?></p>
		<?php }
	}
	
	public static function edv_site_import_cb( $args ){
					
		$option_id = $args['label_for'];
		?><label>
			<input id="<?php echo esc_attr( $option_id ); ?>-confirm"
				name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>-confirm]"
				type="checkbox"
			/> Je confirme l'importation des données manquantes ici depuis le site suivant :
		</label>
		<?php $value = edv::get_option($option_id . '-source');
		$this_blog_id = get_current_blog_id();
		?><select name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>-source]"><?php
			foreach(get_sites() as $site)
				if( $this_blog_id != $site->blog_id)
					echo sprintf('<option value="%d" %s>%s</option>'
					, $site->blog_id
					, selected($site->blog_id, $value)
					, $site->domain . rtrim($site->path, '/'));
		?></select>
		<br>
		<br>
		<div class="dashicons-before dashicons-welcome-learn-more">Importe les pages, les formulaires et les lettres-infos référencés par les options de l'extension.</div>
		<ul><span class=""></span>
		<div class="toggle-trigger dashicons-before dashicons-welcome-learn-more"><a href="#">Pour configurer un nouveau site, il faut : </a><span class="dashicons-before dashicons-arrow-right"/></div>
		<div class="toggle-container">
			<li>Configurer le SMTP (menu Réglages/SMTP).</li>
			<li>Configurer l'intégration du reCaptcha (menu Contacts / Intégration).</li>
			<li>Configurer la version du reCaptcha (menu Contacts / reCaptcha version).</li>
			<li>Saisir les communes du territoire du site (menu Messages / communes).</li>
			<li>Contrôler la liste des diffusions (menu Messages / diffusions). En particulier, que pour "La lettre-info", sélectionner "Coché par défaut lors de la création d'un message."</li>
			<li>Contrôler les options de périodicités de la lettre-info.</li>
			<li>Valider toutes les options de cette page de paramètres.</li>
			<li>Editer chaque page pour sélectionner les formulaires associés et contrôler les url.</li>
			<li>Personnaliser le thème, le menu, ...</li>
			<li>Si la première page est l'agenda local, le menu "Agenda local" doit être un lien personnalisé avec pour url "/#main"</li>
			<li>Tester tous les liens.</li>
		</div></ul>
		<?php
		
		
		echo edv_Admin::get_import_report(true);

	}
	
	public static function edv_import_ics_cb( $args ){
		$option_id = $args['label_for'];
		$post_status = edv::get_option($option_id . '-post_status');
		?>
		<input id="<?php echo esc_attr( $option_id ); ?>"
			name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
			type="file"
		/>
		<br/>
		<label>Statut des messages importés</label>
			<select name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>-post_status]">
			<?php 
				foreach(array("publish"=>"Publié", "pending"=>"En attente de relecture", "draft"=>"Brouillon"/*, "future"=>"Future"*/) as $value=>$name)
					echo sprintf('<option value="%s" %s>%s</option>'
						, $value
						, selected($value, $post_status, false)
						, $name);
			?>
			</select>
		<br>
		<?php /*Avec la case à cocher de confirmation on force un changement de valeur dans les options pour s'assurer de provoquer la mise à jour et passer par le hook update_option. 
		Ca pourrait être n'importe quel autre champ qu'on modifierait mais c'est plus pratique comme ça.*/
		?><label>
			<input id="<?php echo esc_attr( $option_id ); ?>-confirm"
				name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>-confirm]"
				type="checkbox"
			/> Je confirme l'importation de nouveaux
		</label>
		<br>
		<br>
		<div class="dashicons-before dashicons-welcome-learn-more">Les messages avec le même nom et la même date qu'un message déjà existant sont ignorés.</div>
		<div class="dashicons-before dashicons-welcome-learn-more">Les messages avec une date ancienne sont ignorés.</div>
		<div class="dashicons-before dashicons-welcome-learn-more">Les messages importés n'ont, à priori, ni catégories ni organisateur ni e-mail associés.</div>
		<?php
		// $option_value = edv::get_option($option_id);
		// echo edv_Admin::get_import_report(true);

	}

	/**
	 * Option parmi la liste des posts du site
	 * Attention, les auteurs de ces pages doivent être administrateurs
	 * $args['post_type'] doit être fourni
	 */
	public static function edv_input_cb( $args ) {
		$option_id = $args['label_for'];
		$input_type = $args['input_type'];
		$value = edv::get_option($option_id);
		
		if($input_type === 'checkbox'){
			echo sprintf('<label><input id="%s" name="%s[%s]" type="%s" class="%s" %s> %s</label>'
				, esc_attr( $option_id )
				, EDV_TAG, esc_attr( $option_id )
				, $input_type
				, esc_attr( $args['class'] )
				, $value ? 'checked' : ''
				, $args['label']
			);
		} else {
			echo sprintf('<input id="%s" name="%s[%s]" type="%s" class="%s" placeholder="%s" value="%s">'
				, esc_attr( $option_id )
				, EDV_TAG, esc_attr( $option_id )
				, $input_type
				, $args['class']
				, isset( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : ''
				, esc_attr( $value )
				
			);
		}
		if(isset($args['learn-more'])){
			echo '<br><br>';
			if( ! is_array($args['learn-more']))
				$args['learn-more'] = [$args['learn-more']];
			foreach($args['learn-more'] as $learn_more){
				?><div class="dashicons-before dashicons-welcome-learn-more"><?=$learn_more?></div><?php
			}
		}
	}

	/**
	 * Option parmi la liste des posts du site
	 * Attention, les auteurs de ces pages doivent être administrateurs
	 * $args['post_type'] doit être fourni
	 */
	public static function edv_combos_posts_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$post_type = $args['post_type'];
		$option_value = edv::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		$the_query = new WP_Query( 
			array(
				'nopaging' => true,
				'post_type'=> $post_type
				//'author__in' => self::get_admin_ids(),
			)
		);
		if($the_query->have_posts() ) {
			// output the field
			?>
			<select id="<?php echo esc_attr( $option_id ); ?>"
				name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
			><option/>
			<?php
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$author_level = get_the_author_meta('user_level');
				if($author_level >= USER_LEVEL_ADMIN) { //Admin authors only
					echo sprintf('<option value="%d" %s>%s</option>'
						, get_the_ID()
						, selected( $option_value, get_the_ID(), false )
						, esc_html(__( get_the_title(), EDV_TAG ))
					);
				}
			}
			echo '</select>';
		}

		if( ! $option_value){
			switch($args['post_type']){
				case WPCF7_ContactForm::post_type :
					?>
					<div class="dashicons-before dashicons-warning">Un formulaire de contact doit être défini !</div>
					<?php
					break;
				default:
					?>
					<div class="dashicons-before dashicons-warning">Une page doit être définie !</div>
					<?php
					break;
			}
		}

		switch($args['label_for']){
			case 'contact_page_id':
				?>
				<div class="dashicons-before dashicons-welcome-learn-more">Depuis la page d'un message, le visiteur peut nous écrire à propos de cet message.</div>
				<?php
				break;
			case 'admin_message_contact_form_id':
				?>
				<div class="dashicons-before dashicons-welcome-learn-more">Dans les pages des messages, seuls les administrateurs voient un formulaire d'envoi de message à l'organisateur.</div>
				<?php
				break;
			case 'edpost_message_contact_form_id':
				?>
				<div class="dashicons-before dashicons-welcome-learn-more">Dans les formulaires, les adresses emails comme organisateur@<?php echo EDV_EMAIL_DOMAIN?> ou client@<?php echo EDV_EMAIL_DOMAIN?> sont remplacées par des valeurs dépendantes du contexte.</div>
				<?php
				break;
		}
	}

	/**
	 * Option parmi la liste des termes d'une taxonomy
	 * $args['taxonomy'] doit être fourni
	 */
	public static function edv_combos_terms_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$taxonomy = $args['taxonomy'];
		$option_value = edv::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		// output the field
		?>
		<select id="<?php echo esc_attr( $option_id ); ?>"
			name="<?php echo EDV_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
		><option/>
		<?php
		$terms = get_terms( array('hide_empty' => false, 'taxonomy' => $taxonomy) );
		switch($taxonomy){
			case edv_Post::taxonomy_diffusion:
				$terms[] = (object)[ 'term_id' => -1, 'name' => '(sans gestion de diffusion)' ];
				break;
		}
		foreach($terms as $term) {
			echo sprintf('<option value="%d" %s>%s</option>'
				, $term->term_id
				, selected( $option_value, $term->term_id, false )
				, esc_attr( $term->name )
			);
		}
		echo '</select>';
	

		if( ! $option_value){
			switch($args['taxonomy']){
				default:
					?>
					<div class="dashicons-before dashicons-warning">Un élément de la liste doit être sélectionné !</div>
					<?php
					break;
			}
		}
	}

	/**
	 * top level menu
	 */
	public static function init_admin_menu() {
		if( is_network_admin())
			return;
		
		// add top level menu page
		add_menu_page(
			__('Paramètres des fonctions EDV', EDV_TAG),
			'Paramètres EDV',
			'manage_options',
			EDV_TAG,
			array(__CLASS__, 'edv_options_page_html'),
			'dashicons-lightbulb',
			25
		);

		if(! current_user_can('manage_options')){

		    $user = wp_get_current_user();
		    $roles = ( array ) $user->roles;
		    if(in_array('edpost', $roles)) {
				remove_menu_page('posts');//TODO
				remove_menu_page('wpcf7');
			}
		}
		else {
			$capability = 'manage_options';
			
			$option = 'admin_nl_post_id';
			if ( $post_id = edv::get_option($option) ){
				$parent_slug = sprintf('edit.php?post_type=%s', edv_Newsletter::post_type) ;
				$page_title =  edv::get_option_label($option);
				$menu_slug = sprintf('post.php?post=%s&action=edit', $post_id);
				add_submenu_page( $parent_slug, $page_title, 'Administrateurices', $capability, $menu_slug);
			}
			$option = 'posts_nl_post_id';
			if ( $post_id = edv::get_option($option) ){
				$parent_slug = sprintf('edit.php?post_type=%s', edv_Newsletter::post_type) ;
				$page_title =  edv::get_option_label($option);
				$menu_slug = sprintf('post.php?post=%s&action=edit', $post_id);
				add_submenu_page( $parent_slug, $page_title, 'Messages à venir', $capability, $menu_slug);
			}
			$option = 'covoiturages_nl_post_id';
			if ( $post_id = edv::get_option($option) ){
				$parent_slug = sprintf('edit.php?post_type=%s', edv_Newsletter::post_type) ;
				$page_title =  edv::get_option_label($option);
				$menu_slug = sprintf('post.php?post=%s&action=edit', $post_id);
				add_submenu_page( $parent_slug, $page_title, 'Covoiturages à venir', $capability, $menu_slug);
			}
			
			$parent_slug = sprintf('edit.php?post_type=%s', edv_Post::post_type) ;
			$page_title =  'Messages en attente de validation';
			$menu_slug = $parent_slug . '&post_status=pending';
			add_submenu_page( $parent_slug, $page_title, 'En attente', $capability, $menu_slug, '', 1);
			
			$parent_slug = sprintf('edit.php?post_type=%s', edv_Covoiturage::post_type) ;
			$page_title =  'Covoiturages en attente de validation';
			$menu_slug = $parent_slug . '&post_status=pending';
			add_submenu_page( $parent_slug, $page_title, 'En attente', $capability, $menu_slug, '', 1);
		}
	}

	/**
	* top level menu:
	* callback functions
	*/
	public static function edv_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// wordpress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'edv_messages', 'edv_message', __( 'Réglages enregistrés', EDV_TAG ), 'updated' );

			
		}

		// show error/update messages
		settings_errors( 'edv_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "edv"
				settings_fields( EDV_TAG );
				// output setting sections and their fields
				// (sections are registered for "edv", each field is registered to a specific section)
				do_settings_sections( EDV_TAG );
				// output save settings button
				submit_button( __('Enregistrer', EDV_TAG) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 *
	 */
	public static function init_dashboard_widgets() {
	    self::remove_dashboard_widgets();
	}

	// TODO parametrage initiale pour chaque utilisateur
	public static function remove_dashboard_widgets() {
	    global $wp_meta_boxes, $current_user;
	    /*var_dump($wp_meta_boxes['dashboard']);*/
		if( ! in_array('administrator',(array)$current_user->roles) ) {
			remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		}
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	}

	/**
	 *
	 */
	public static function check_module_health() {
		
		$source_options = edv::get_option();
	    $logs = [];
		foreach([
		  'admin_message_contact_form_id' => 'WPCF7_Contact_Form'
		, 'newsletter_subscribe_form_id' => 'WPCF7_Contact_Form'
		, 'posts_nl_post_id' => 'edvnl'
		, 'newsletter_subscribe_page_id' => 'page'
		, 'edpost_edit_form_id' => 'WPCF7_Contact_Form'
		, 'contact_page_id' => 'page'
		, 'contact_form_id' => 'WPCF7_Contact_Form'
		, 'edpost_message_contact_form_id' => 'page'
		, 'agenda_page_id' => 'page'
		, 'new_post_page_id' => 'page'
		, 'blog_presentation_page_id' => 'page'
		, 'newsletter_diffusion_term_id' => 'term']
		as $option_name => $post_type){
		
			$option_label = edv::get_option_label($option_name);
			
			if ( ! isset( $source_options[$option_name] ) || ! $source_options[$option_name] ) {
				$logs[] = sprintf('<p>Le paramètre <b>%s</b> (%s) n\'est pas défini.</p>', 
						$option_label, $option_name);
				continue;
			}
			$source_option_value = $source_options[$option_name];
			if($post_type === 'term'){
				if($source_option_value == -1)
					continue;
				$source_term = get_term( $source_option_value );
				if( ! $source_term ){
					$logs[] = sprintf('<p>Impossible de retrouver le terme <b>%s</b> (%s) #%d</p>', 
						$option_label, $option_name, $source_option_value);
					
					continue;
				}
				continue;
			}
			$source_post = get_post( $source_option_value );
			if( ! $source_post ){
				$logs[] = sprintf('<p>Impossible de retrouver le message <b>%s</b> (%s) #%d</p>', 
					$option_label, $option_name, $source_option_value);
				
				continue;
			}

			if( $source_post->post_status !== 'publish' ){
				$logs[] = sprintf('<p>Le message source <b>%s</b> (%s) n\'est pas publié (%s).</p>', 
					$option_label, $option_name, $source_post->post_status);
			}
		}
		
		//TODO default_check "La lettre-info"
		
		if( count($logs) ){
			edv_Admin::add_admin_notice($logs, 'error', true);
		}
	}
}

?>