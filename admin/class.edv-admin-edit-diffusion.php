<?php

/**
 * edv Admin -> Edit -> Post -> diffusion
 * Custom taxonomy term for WordPress in Admin UI.
 * 
 * Edition d'une diffusion
 * Définition des metaboxes et des champs personnalisés des diffusions
 *
 */
class edv_Admin_Edit_Diffusion extends edv_Admin_Edit_Post_Type {

	public static function init() {
		parent::init();

		self::init_hooks();
	}
	
	public static function init_hooks() {
		
		// if(basename($_SERVER['PHP_SELF']) === 'edit-tags.php'
		// && array_key_exists('post_type', $_POST)
		// && $_POST['post_type'] == edv_Post::post_type
		// && array_key_exists('taxonomy', $_POST)
		// && $_POST['taxonomy'] == edv_Post::taxonomy_diffusion)
			add_action( 'saved_' . edv_Post::taxonomy_diffusion , array(__CLASS__, 'saved_term_cb'), 10, 4 );

		add_action( edv_Post::taxonomy_diffusion . '_add_form_fields', array( __CLASS__, 'on_add_form_fields' ), 10, 1 ); //edit
		add_action( edv_Post::taxonomy_diffusion . '_edit_form_fields', array( __CLASS__, 'on_edit_form_fields' ), 10, 2); //edit

		//add custom columns for list view
		add_filter( 'manage_edit-' . edv_Post::taxonomy_diffusion . '_columns', array( __CLASS__, 'manage_columns' ) );
		add_filter( 'manage_' . edv_Post::taxonomy_diffusion . '_custom_column', array( __CLASS__, 'manage_custom_columns' ), 10, 3 );

	}
	/****************/
	public static function manage_columns($columns){
		$columns['default_checked'] = 'Coché par défaut';
		$columns['download_link'] = 'Lien';
		return $columns;
	}
	public static function manage_custom_columns($content, string $column_name, int $term_id){
		switch ( $column_name ) {
			case 'default_checked' :
				if( get_term_meta( $term_id, $column_name, true ) )
					echo 'Coché par défaut';
				else
					echo 'non';
				break;
			case 'download_link' :
				if( get_term_meta( $term_id, $column_name, true ) )
					echo 'Téléchargement';
				else
					echo 'non';
				break;
		}
		return $content;
	}
	
	public static function get_metabox_all_fields(){}//for abstract

	/**
	 * Callback lors de l'enregistrement d'un évènement.
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function saved_term_cb ( int $term_id, int $tt_id, bool $update, array $args ){
		foreach([ 'default_checked', 'download_link' ] as $meta_name)
			if(array_key_exists($meta_name, $args) && $args[$meta_name] ){
				update_term_meta($term_id, $meta_name, $args[$meta_name]);
			}
			else {
				delete_term_meta($term_id, $meta_name);
			}
	}

	/**
	 * Register Meta Boxes (boite en édition du term)
	 */
	public static function on_add_form_fields( string $taxonomy ){
		self::on_edit_form_fields(null, $taxonomy);
	}

	/**
	 * Register Meta Boxes (boite en édition du term)
	 */
	public static function on_edit_form_fields( $tag, string $taxonomy ){
		
    ?><tr class="form-comment">
        <th scope="row">
        <td><i>La description appraitra en information complémentaire lors de l'édition d'un évènement.</td>
    </tr><?php
    ?><tr class="form-field">
        <th scope="row"><label for="default_checked">Coché par défaut</label></th>
        <td><?php
			$meta_name = 'default_checked';
			parent::metabox_html([array('name' => $meta_name,
									'label' => __('Coché par défaut lors de la création d\'un évènement.', EDV_TAG),
									'type' => 'bool',
									// 'default' => $checked
								)], $tag, null);
        ?></td>
    </tr><?php
    ?><tr class="form-field">
        <th scope="row"><label for="download_link">Lien en bas de l'agenda</label></th>
        <td><?php
			$meta_name = 'download_link';
			
			$values = [ '' => '(pas de téléchargement)'
						, 'ics' => 'vCalendar (.ics)'
						, 'txt' => 'texte brut (.txt)'
						, 'bv.txt' => 'texte préformaté BV (.bv.txt)'
					];
			
			parent::metabox_html([array('name' => $meta_name,
									'label' => __('Téléchargement', EDV_TAG),
									'input' => 'select',
									'values' => $values
								)], $tag, null);
        ?></td>
    </tr><?php
		if( $tag === null)
			echo '<br><br>';
	}


	
}
?>