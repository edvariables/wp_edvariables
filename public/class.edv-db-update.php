<?php

class edv_DB_Update {

	/**
	*/
	public static function update_db_1_1_1(){
		
		if( ! edv::get_option('posts_nl_post_id')){
			edv::update_option('posts_nl_post_id', edv::get_option('newsletter_post_id'));
			edv::update_option('newsletter_post_id', null);
		}
		
		if( ! edv::get_option('newsletter_subscribe_form_id')){
			edv::update_option('newsletter_subscribe_form_id', edv::get_option('newsletter_events_register_form_id'));
			edv::update_option('newsletter_events_register_form_id', null);
		}
		
		return true;
	}

	/**
	*/
	public static function update_db_1_0_23(){
		
		if( ! edv::get_option('edpost_message_contact_form_id')){
			edv::update_option('edpost_message_contact_form_id', edv::get_option('edpost_message_contact_post_id'));
			edv::update_option('edpost_message_contact_post_id', null);
		}
		
		return true;
	}
	
	/**
	*/
	public static function update_db_1_0_22(){
		global $wpdb;
		$blog_prefix = $wpdb->get_blog_prefix();
		$sqls = [];
		$sqls["taxonomy='ev_diffusion'"] = "UPDATE {$blog_prefix}term_taxonomy SET taxonomy='ev_diffusion' WHERE taxonomy = 'publication'";
		$sqls["taxonomy='ev_city'"] = "UPDATE {$blog_prefix}term_taxonomy SET taxonomy='ev_city' WHERE taxonomy = 'city'";
		$sqls["taxonomy='ev_category'"] = "UPDATE {$blog_prefix}term_taxonomy SET taxonomy='ev_category' WHERE taxonomy = 'type_edpost'";
		$sqls["postmeta.meta_key ='edp-diffusion'"] = "UPDATE {$blog_prefix}postmeta SET meta_key='edp-diffusion' WHERE meta_key = 'edp-publication'";
		foreach($sqls as $name => $sql){
			$result = $wpdb->query($sql);
		
			if( $result === false ){
				debug_log('update_db '.$name.' ERROR ', $sql);
			}
			else
				debug_log('update_db '.$name.' OK : ' . $result);
		}
		
		if(edv::get_option('edpost_tax_publication_newsletter_term_id')){
			edv::update_option('newsletter_diffusion_term_id', edv::get_option('edpost_tax_publication_newsletter_term_id'));
			edv::update_option('edpost_tax_publication_newsletter_term_id', null);
		}
		
		$post_id = edv::get_option('edpost_edit_form_id');
		$post = get_post($post_id);
		$post->post_content = str_replace('publication', 'diffusion', $post->post_content);
		$post->post_content = str_replace('Publication', 'Diffusion', $post->post_content);
		$result = wp_update_post([
			'ID' => $post->ID,
			'post_content' => $post->post_content
		]);
		$post_meta = get_post_meta($post_id, '_form', true);
		if( is_array($post_meta) )
			$post_meta = $post_meta[0];
		$post_meta = str_replace('publication', 'diffusion', $post_meta);
		$post_meta = str_replace('Publication', 'Diffusion', $post_meta);
		update_post_meta($post_id, '_form', $post_meta);
		
		debug_log('update_db edpost_edit_form_id : ', $result);
		
		return true;
	}
}
