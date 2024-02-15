<?php

/**
 * edv -> Covoiturages
 * Collection de covoiturages
 */
class edv_Covoiturages_Import {
	
	
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
			, date_i18n('d/m/Y H:i:s', strtotime($iCal['covoiturages'][0]['dtstamp']))
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
		debug_log("\r\nimport_ics covoiturages", $iCal['covoiturages'], "\r\n\r\n\r\n\r\n");
		foreach($iCal['covoiturages'] as $covoiturage){
			
			switch(strtoupper($covoiturage['status'])){
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
					debug_log('[UNKNOWN]$covoiturage->status = ' . $covoiturage['status']);
					$ignoreCounter++;
					continue 2;
			}
			// if(($successCounter + $ignoreCounter) > 5) break;//debug
			
			$dateStart = $covoiturage['dtstart'];
			$dateEnd = empty($covoiturage['dtend']) ? '' : $covoiturage['dtend'];
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
				'cov-date-debut' => $dateStart,
				'cov-date-fin' => $dateEnd,
				'cov-heure-debut' =>$timeStart,
				'cov-heure-fin' => $timeEnd,
				'cov-localisation' => empty($covoiturage['location']) ? '' : trim($covoiturage['location']),
				'cov-organisateur' => empty($covoiturage['organisateur']) ? '' : trim($covoiturage['organisateur']),
				'cov-email' => empty($covoiturage['email']) ? '' : trim($covoiturage['email']),
				'cov-phone' => empty($covoiturage['phone']) ? '' : trim($covoiturage['phone']),
				'cov-import-uid' => empty($covoiturage['uid']) ? '' : $covoiturage['uid'],
				'cov-date-journee-entiere' => $timeStart ? '' : '1',
				'cov-codesecret' => edv::get_secret_code(6),
				'_post-source' => $import_source
			);
						
			$post_title = $covoiturage['summary'];
			$post_content = empty($covoiturage['description']) ? '' : trim($covoiturage['description']);
			if ($post_content === null) $post_content = '';
			
			//Check doublon
			$doublon = edv_Covoiturage_Edit::get_post_idem($post_title, $inputs);
			if($doublon){
				//var_dump($doublon);var_dump($post_title);var_dump($inputs);
				debug_log('[IGNORE]$doublon = ' . var_export($post_title, true));
				$ignoreCounter++;
				$url = edv_Covoiturage::get_post_permalink($doublon);
				$log[] = sprintf('<li><a href="%s">%s</a> existe déjà, avec le statut "%s".</li>', $url, htmlentities($doublon->post_title), $post_statuses[$doublon->post_status]);
				continue;				
			}
			
			// terms
			$all_taxonomies = edv_Covoiturage_Post_type::get_taxonomies();
			$taxonomies = [];
			foreach([ 
				'INTENTIONS' => edv_Covoiturage::taxonomy_cov_intention
				, 'CITIES' => edv_Covoiturage::taxonomy_city
				, 'DIFFUSIONS' => edv_Covoiturage::taxonomy_diffusion
			] as $node_name => $tax_name){
				$node_name = strtolower($node_name);
				if( empty($covoiturage[$node_name]))
					continue;
				if( is_string($covoiturage[$node_name]))
					$covoiturage[$node_name] = explode(',', $covoiturage[$node_name]);
				$taxonomies[$tax_name] = [];
				$all_terms = edv_Covoiturage_Post_type::get_all_terms($tax_name, 'name'); //indexé par $term->name
				foreach($covoiturage[$node_name] as $term_name){
					if( ! array_key_exists($term_name, $all_terms)){
						$data = [
							'post_type'=>edv_Covoiturage::post_type,
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
				'post_type' => edv_Covoiturage::post_type,
				'post_author' => $post_author,
				'meta_input' => $inputs,
				'post_content' =>  $post_content,
				'post_status' => $post_status,
				'tax_input' => $taxonomies
			);
			
			// terms
			$taxonomies = [];
			foreach([ 
				'INTENTIONS' => edv_Covoiturage::taxonomy_cov_intention
				, 'CITIES' => edv_Covoiturage::taxonomy_city
				, 'DIFFUSIONS' => edv_Covoiturage::taxonomy_diffusion
			] as $node_name => $term_name){
				if( ! empty($covoiturage[strtolower($node_name)]))
					$taxonomies[$term_name] = $covoiturage[strtolower($node_name)];
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
				$log[] = '<li class="error">Erreur de création du covoiturage</li>';
				if(is_wp_error( $post_id)){
					debug_log('[INSERT ERROR+]$post_id = ' . var_export($post_id, true));
					$log[] = sprintf('<pre>%s</pre>', var_export($post_id, true));
				}
				$log[] = sprintf('<pre>%s</pre>', var_export($covoiturage, true));
				$log[] = sprintf('<pre>%s</pre>', var_export($postarr, true));
			}
			else{
				$successCounter++;
				$post = get_post($post_id);
				$url = edv_Covoiturage::get_post_permalink($post);
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
					$vevent = [];
					foreach($node->data as $key => $value) {
						$key = strtolower($key);
						if(is_array($value)){
							$vevent[$key] = [];
							$vevent[$key .'[parameters]'] = [];
							for($i = 0; $i < count($value); $i++) {
								if(is_array($value[$i])){
									array_walk_recursive( $value[$i], function(&$value, $value_key, &$vevent_key_arr){
										if(is_a($value, 'ZCiCalDataNode'))
											$vevent_key_arr[] = $value->value[0];
										else
											$vevent_key_arr[] = $value;
									}, $vevent[$key]);
									debug_log($vevent[$key]);
								}
								else {
									$vevent[$key][] = $value[$i]->getValues();
									$p = $value[$i]->getParameters();
									if($p){
										$vevent[$key .'[parameters]'][] = $p;
									}
								}
							}
						} else {
							if( isset($vevent[$key]) ){
								if( ! is_array($vevent[$key])){
									$vevent[$key] = [$vevent[$key]];
									if(isset($vevent[$key .'[parameters]']))
										$vevent[$key .'[parameters]'] = [$vevent[$key .'[parameters]']];
								}
								$vevent[$key][] = $value->getValues();
							}
							else
								$vevent[$key] = $value->getValues();
							$p = $value->getParameters();
							if($p){
								if(!empty($vevent[$key .'[parameters]']) && is_array($vevent[$key .'[parameters]']))
									$vevent[$key .'[parameters]'][] = $p;
								else
									$vevent[$key .'[parameters]'] = $p;
							}
						}
					}
					//if no hour specified, dtend means the day before
					if(isset($vevent['dtend']) && $vevent['dtend']){
						if(strpos($vevent['dtstart'], 'T') === false
						&& strpos($vevent['dtend'], 'T') === false
						&& $vevent['dtend'] != $vevent['dtstart'])
							$vevent['dtend'] = date('Y-m-d', strtotime($vevent['dtend'] . ' - 1 day')); 
						$vevent['dtend'] = date('Y-m-d H:i:s', strtotime($vevent['dtend'])); 
					}
					$vevent['dtstart'] = date('Y-m-d H:i:s', strtotime($vevent['dtstart'])); 
					$vedposts[] = $vevent;
				}
			}
		}
		
		$vcalendar['covoiturages'] = $vedposts;
		// debug_log($vcalendar);
		return $vcalendar;
	}
	/*
	**/
}
