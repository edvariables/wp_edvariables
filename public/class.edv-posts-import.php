<?php

/**
 * edv -> Posts
 * Collection de messages
 */
class edv_Posts_Import {
	
	
	/**
	* import_ics
	*/
	public static function import_ics($file_name, $default_post_status = 'publish', $original_file_name = null){
		$iCal = self::get_vcalendar($file_name);
		
		$import_source = 'import_ics_' . $iCal['title'];
		
		$post_statuses = get_post_statuses();
		$today = strtotime(wp_date("Y-m-d"));
		$successCounter = 0;
		$failCounter = 0;
		$ignoreCounter = 0;
		$log = array();
		$log[] = sprintf('<ul><b>Importation ICS "%s", %s</b>'
			, isset($original_file_name) && $original_file_name ? $original_file_name : basename( $file_name )
			, date_i18n('Y-m-d H:i'));
		$log[] = sprintf('<ul><b>Source : "%s", le %s - %s</b>'
			, empty($iCal['title']) ? '' : $iCal['title']
			, date_i18n('d/m/Y H:i:s', strtotime($iCal['edposts'][0]['dtstamp']))
			, empty($iCal['description']) ? '' : $iCal['description']);
		
		if(!$default_post_status)
			$default_post_status = 'publish';
		
		if(($user = wp_get_current_user())
		&& $user->ID){
		    $post_author = $user->ID;
		}
		else {
			$post_author = edv_User::get_blog_admin_id();
		}
		debug_log("\r\nimport_ics edposts", $iCal['edposts'], "\r\n\r\n\r\n\r\n");
		foreach($iCal['edposts'] as $edvpost){
			
			switch(strtoupper($edvpost['status'])){
				case 'CONFIRMED':
				case 'TENTATIVE':
					$post_status = $default_post_status;
					break;
				case 'DRAFT':
					$post_status = 'draft';
					break;
				case 'CANCELLED':
					$post_status = 'trash';//TODO signaler
					break;
				default: 
					debug_log('[UNKNOWN]$edvpost->status = ' . $edvpost['status']);
					$ignoreCounter++;
					continue 2;
			}
			// if(($successCounter + $ignoreCounter) > 5) break;//debug
			
			$dateStart = $edvpost['dtstart'];
			$dateEnd = empty($edvpost['dtend']) ? '' : $edvpost['dtend'];
			$timeStart = substr($dateStart, 11, 5);//TODO
			$timeEnd = substr($dateEnd, 11, 5);//TODO 
			if($timeStart == '00:00')
				$timeStart = '';
			if($timeEnd == '00:00')
				$timeEnd = '';
			$dateStart = substr($dateStart, 0, 10);
			$dateEnd = substr($dateEnd, 0, 10);
			if(strtotime($dateStart) < $today) {
				debug_log('[IGNORE]$dateStart = ' . $dateStart);
				$ignoreCounter++;
				continue;
			}
			
			$inputs = array(
				'edp-date-debut' => $dateStart,
				'edp-date-fin' => $dateEnd,
				'edp-heure-debut' =>$timeStart,
				'edp-heure-fin' => $timeEnd,
				'edp-localisation' => empty($edvpost['location']) ? '' : trim($edvpost['location']),
				'edp-organisateur' => empty($edvpost['organisateur']) ? '' : trim($edvpost['organisateur']),
				'edp-email' => empty($edvpost['email']) ? '' : trim($edvpost['email']),
				'edp-phone' => empty($edvpost['phone']) ? '' : trim($edvpost['phone']),
				'edp-import-uid' => empty($edvpost['uid']) ? '' : $edvpost['uid'],
				'edp-date-journee-entiere' => $timeStart ? '' : '1',
				'edp-codesecret' => edv::get_secret_code(6),
				'_post-source' => $import_source
			);
						
			$post_title = $edvpost['summary'];
			$post_content = empty($edvpost['description']) ? '' : trim($edvpost['description']);
			if ($post_content === null) $post_content = '';
			
			//Check doublon
			$doublon = edv_Post_Edit::get_post_idem($post_title, $inputs);
			if($doublon){
				//var_dump($doublon);var_dump($post_title);var_dump($inputs);
				debug_log('[IGNORE]$doublon = ' . var_export($post_title, true));
				$ignoreCounter++;
				$url = edv_Post::get_post_permalink($doublon);
				$log[] = sprintf('<li><a href="%s">%s</a> existe déjà, avec le statut "%s".</li>', $url, htmlentities($doublon->post_title), $post_statuses[$doublon->post_status]);
				continue;				
			}
			
			// terms
			$all_taxonomies = edv_Post_Post_type::get_taxonomies();
			$taxonomies = [];
			foreach([ 
				'CATEGORIES' => edv_Post::taxonomy_ev_category
				, 'CITIES' => edv_Post::taxonomy_city
				, 'DIFFUSIONS' => edv_Post::taxonomy_diffusion
			] as $node_name => $tax_name){
				$node_name = strtolower($node_name);
				if( empty($edvpost[$node_name]))
					continue;
				if( is_string($edvpost[$node_name]))
					$edvpost[$node_name] = explode(',', $edvpost[$node_name]);
				$taxonomies[$tax_name] = [];
				$all_terms = edv_Post_Post_type::get_all_terms($tax_name, 'name'); //indexé par $term->name
				foreach($edvpost[$node_name] as $term_name){
					if( ! array_key_exists($term_name, $all_terms)){
						$data = [
							'post_type'=>edv_Post::post_type,
							'taxonomy'=>$tax_name,
							'term'=>$term_name
						];
						$log[] = sprintf('<li>Dans la taxonomie "%s", le terme "<b>%s</b>" n\'existe pas. %s</li>'
							, $all_taxonomies[$tax_name]['label']
							, htmlentities($term_name)
							, edv::get_ajax_action_link(false, 'insert_term', 'add', 'Cliquez ici pour l\'ajouter', 'Crée un nouveau terme', true, $data)
						);
						continue;
					}
					$taxonomies[$tax_name][] =  $all_terms[$term_name]->term_id;
				}
			}
			
			$postarr = array(
				'post_title' => $post_title,
				'post_name' => sanitize_title( $post_title ),
				'post_type' => edv_Post::post_type,
				'post_author' => $post_author,
				'meta_input' => $inputs,
				'post_content' =>  $post_content,
				'post_status' => $post_status,
				'tax_input' => $taxonomies
			);
			
			// terms
			$taxonomies = [];
			foreach([ 
				'CATEGORIES' => edv_Post::taxonomy_ev_category
				, 'CITIES' => edv_Post::taxonomy_city
				, 'DIFFUSIONS' => edv_Post::taxonomy_diffusion
			] as $node_name => $term_name){
				if( ! empty($edvpost[strtolower($node_name)]))
					$taxonomies[$term_name] = $edvpost[strtolower($node_name)];
			}
			
			#DEBUG
			// if( strlen($postarr['post_title']) >= 10 ){
				// $postarr['post_title'] = substr($postarr['post_title'], 0, 5) . "[...]";
				// $postarr['post_name'] = sanitize_title( $postarr['post_title'] );
			// }
			// if( strlen($postarr['post_content']) >= 10 )
				// $postarr['post_content'] = substr($postarr['post_content'], 0, 5) . "[...]";
			
			$post_id = wp_insert_post( $postarr, true );
			
			if(!$post_id || is_wp_error( $post_id )){
				$failCounter++;
				debug_log('[INSERT ERROR]$post_title = ' . var_export($post_title, true));
				debug_log('[INSERT ERROR+]$post_content = ' . var_export($post_content, true));
				$log[] = '<li class="error">Erreur de création de l\'message</li>';
				if(is_wp_error( $post_id)){
					debug_log('[INSERT ERROR+]$post_id = ' . var_export($post_id, true));
					$log[] = sprintf('<pre>%s</pre>', var_export($post_id, true));
				}
				$log[] = sprintf('<pre>%s</pre>', var_export($edvpost, true));
				$log[] = sprintf('<pre>%s</pre>', var_export($postarr, true));
			}
			else{
				$successCounter++;
				$post = get_post($post_id);
				$url = edv_Post::get_post_permalink($post);
				$log[] = sprintf('<li><a href="%s">%s</a> a été importé avec le statut "%s"%s</li>'
						, $url, htmlentities($post->post_title)
						, $post_statuses[$post->post_status]
						, $post->post_status != $default_post_status ? ' !' : '.'
				);
			}
		}
		
		$log[] = sprintf('<li><b>%d importation(s), %d échec(s), %d ignorée(s)</b></li>', $successCounter, $failCounter, $ignoreCounter);
		debug_log('[FINAL REPORT] ' . sprintf('%d importation(s), %d echec(s), %d ignoree(s)', $successCounter, $failCounter, $ignoreCounter));
		$log[] = '</ul>';
		
		if(class_exists('edv_Admin'))
			edv_Admin::set_import_report ( $log );
		
		return $successCounter;
	}
	/**
	 * get_vcalendar($file_name)
	 */
	public static function get_vcalendar($file_name){
		require_once(EDV_PLUGIN_DIR . "/includes/icalendar/zapcallib.php");	
		$ical= new ZCiCal(file_get_contents($file_name));
		$vcalendar = [];
		
		// debug_log($ical->tree->data);
		
		foreach($ical->tree->data as $key => $value){
			$key = strtolower($key);
			if(is_array($value)){
				$vcalendar[$key] = '';
				for($i = 0; $i < count($value); $i++){
					$p = $value[$i]->getParameters();
					if($vcalendar[$key])
						$vcalendar[$key] .= ',';
					$vcalendar[$key] .= $value[$i]->getValues();
				}
			} else {
				$vcalendar[$key] = $value->getValues();
			}
		}
		
		if( ! empty($vcalendar['x-wr-calname'])){
			if(empty($vcalendar['title']))
				$vcalendar['title'] = $vcalendar['x-wr-calname'];
		}
		
		if(empty($vcalendar['description']))
			$vcalendar['description'] = 'vcalendar_' . wp_date('Y-m-d H:i:s');
		if(empty($vcalendar['title']))
			$vcalendar['title'] = $vcalendar['description'];
		
		$vedposts = [];
		if(isset($ical->tree->child)) {
			foreach($ical->tree->child as $node) {
				// debug_log($node->data);
				if($node->getName() == "VEVENT") {
					$vedvpost = [];
					foreach($node->data as $key => $value) {
						$key = strtolower($key);
						if(is_array($value)){
							$vedvpost[$key] = [];
							$vedvpost[$key .'[parameters]'] = [];
							for($i = 0; $i < count($value); $i++) {
								if(is_array($value[$i])){
									array_walk_recursive( $value[$i], function(&$value, $value_key, &$vedvpost_key_arr){
										if(is_a($value, 'ZCiCalDataNode'))
											$vedvpost_key_arr[] = $value->value[0];
										else
											$vedvpost_key_arr[] = $value;
									}, $vedvpost[$key]);
									debug_log($vedvpost[$key]);
								}
								else {
									$vedvpost[$key][] = $value[$i]->getValues();
									$p = $value[$i]->getParameters();
									if($p){
										$vedvpost[$key .'[parameters]'][] = $p;
									}
								}
							}
						} else {
							if( isset($vedvpost[$key]) ){
								if( ! is_array($vedvpost[$key])){
									$vedvpost[$key] = [$vedvpost[$key]];
									if(isset($vedvpost[$key .'[parameters]']))
										$vedvpost[$key .'[parameters]'] = [$vedvpost[$key .'[parameters]']];
								}
								$vedvpost[$key][] = $value->getValues();
							}
							else
								$vedvpost[$key] = $value->getValues();
							$p = $value->getParameters();
							if($p){
								if(!empty($vedvpost[$key .'[parameters]']) && is_array($vedvpost[$key .'[parameters]']))
									$vedvpost[$key .'[parameters]'][] = $p;
								else
									$vedvpost[$key .'[parameters]'] = $p;
							}
						}
					}
					//if no hour specified, dtend means the day before
					if(isset($vedvpost['dtend']) && $vedvpost['dtend']){
						if(strpos($vedvpost['dtstart'], 'T') === false
						&& strpos($vedvpost['dtend'], 'T') === false
						&& $vedvpost['dtend'] != $vedvpost['dtstart'])
							$vedvpost['dtend'] = date('Y-m-d', strtotime($vedvpost['dtend'] . ' - 1 day')); 
						$vedvpost['dtend'] = date('Y-m-d H:i:s', strtotime($vedvpost['dtend'])); 
					}
					$vedvpost['dtstart'] = date('Y-m-d H:i:s', strtotime($vedvpost['dtstart'])); 
					$vedposts[] = $vedvpost;
				}
			}
		}
		
		$vcalendar['edposts'] = $vedposts;
		// debug_log($vcalendar);
		return $vcalendar;
	}
	/*
	**/
}
