<?php

/**
 * edv -> Posts
 * Collection d'messages
 */
class edv_Posts_Export {
	 
	 /**
	  * Export
	  *
	  * $return = url|file|data
	  */
	 public static function do_export($posts, $file_format = 'ics', $return = 'url' ){
		$encode_to = "UTF-8";
		switch( strtolower( $file_format )){
			case 'vcalendar':
				$file_format = 'ics';
			case 'ics':
				$export_data = self::export_posts_ics($posts);
				break;
			case 'txt':
				$encode_to = "Windows-1252";
				$export_data = self::export_posts_txt($posts);
				break;
			case 'bv.txt':
				$encode_to = "Windows-1252";
				$export_data = self::export_posts_bv_txt($posts);
				break;
			default:
				return sprintf('format inconnu : "%s"', $file_format);
		}

		if($return === 'data')
			return $export_data;
		
		if( ! $export_data)
			return sprintf('Aucune donnée à exporter');
		
		$enc = mb_detect_encoding($export_data);
		$export_data = mb_convert_encoding($export_data, $encode_to, $enc);

		self::clear_export_history();
		
		$file = self::get_export_filename( $file_format );

		$handle = fopen($file, "w");
		fwrite($handle, $export_data);
		fclose($handle);
		
		if($return === 'file')
			return $file;
		
		$url = self::get_export_url(basename($file));
		
		return $url;
		
	}
	
	/**
	 * Retourne les données TXT pour le téléchargement de l'export des messages
	 */
	public static function export_posts_txt($posts){

		$txt = ['Exportation du ' . wp_date('d/m/Y \à H:i')];
			$txt[] = str_repeat('*', 36);
			$txt[] = str_repeat('*', 36);
		$txt[] = '';
		foreach($posts as $post){
			$txt[] = $post->post_title;
			$txt[] = str_repeat('-', 24);
			$txt[] = edv_Post::get_edvpost_dates_text( $post->ID );
			$txt[] = get_post_meta($post->ID, 'edp-localisation', true);
			if( $value = edv_Post::get_edvpost_cities($post->ID))
				$txt[] = implode(', ', $value);
			if( $value = edv_Post::get_edvpost_categories($post->ID))
				$txt[] = implode(', ', $value);
			foreach(['edp-organisateur', 'edp-email', 'edp-phone', 'edp-siteweb'] as $meta_key)
				if( $value = get_post_meta($post->ID, $meta_key, true) )
					$txt[] = $value;
			$txt[] = $post->post_content;
			$txt[] = '';
			$txt[] = str_repeat('*', 36);
			$txt[] = '';
			
		}
		return implode("\r\n", $txt);
	}
	
	/**
	 * Retourne les données Bulle-Verte bv.txt pour le téléchargement de l'export des messages
	 */
	public static function export_posts_bv_txt($posts){

		$txt = [];
		foreach($posts as $post){
			if( $cities = edv_Post::get_edvpost_cities($post->ID))
				$cities = ' - ' . implode(', ', $cities);
			else
				$cities = '';
			$txt[] = $post->post_title . $cities;
			
			$localisation = get_post_meta($post->ID, 'edp-localisation', true);
			if($localisation)
				$localisation = ' - ' . $localisation;
			$dates = edv_Post::get_edvpost_dates_text( $post->ID );
			$dates = str_replace([ date('Y'), date('Y + 1 year') ], '', $dates);
			$txt[] = $dates . $localisation;
			
			$txt[] = $post->post_content;
			
			$meta_key = 'edp-organisateur';
			if( $value = get_post_meta($post->ID, $meta_key, true) )
				$txt[] = sprintf('Organisé par : %s', $value);
				
			$infos = '';
			$meta_key = 'edp-phone';
			if( $value = get_post_meta($post->ID, $meta_key, true) )
				$infos = $value;
				
			$meta_key = 'edp-email';
			if( $value = get_post_meta($post->ID, $meta_key, true) )
				if($infos)
					$infos .= '/';
				$infos .= $value;
				
			$meta_key = 'edp-siteweb';
			if( $value = get_post_meta($post->ID, $meta_key, true) )
				$value = str_replace( [ 'http://', 'https://' ], '', $value);
				if($infos)
					$infos .= ' / ';
				$infos .= $value;
				
			$txt[] = 'Infos : ' . $infos;
			
			$txt[] = '';
			$txt[] = '';
			
		}
		return implode("\r\n", $txt);
	}
	
	
	/**
	 * Retourne les données ICS pour le téléchargement de l'export des messages
	 */
	public static function export_posts_ics($posts){

		require_once(EDV_PLUGIN_DIR . "/includes/icalendar/zapcallib.php");
		
		$iCal = self::get_new_ZCiCal();
		foreach($posts as $post){
			self::add_edvpost_to_ZCiCal($post, $iCal);
		}
		return $iCal->export();
		
		/* require_once(EDV_PLUGIN_DIR . '/admin/class.ical.php');
		$iCal = new iCal();
		$iCal->title = get_bloginfo( 'name', 'display' );
		$iCal->description = content_url();
		foreach($posts as $post){
			$iCal->edposts[] = new iCal_Event($post);
		}
		return $iCal->generate(); */
	}
	
	public static function get_new_ZCiCal(){
		$ical= new ZCiCal();
		//TITLE
		$datanode = new ZCiCalDataNode("TITLE:" . ZCiCal::formatContent( get_bloginfo( 'name', 'display' )));
		$ical->curnode->data[$datanode->getName()] = $datanode;
		//DESCRIPTION
		$page_id = edv::get_option('agenda_page_id');
		if($page_id)
			$url = get_permalink($page_id);
		else
			$url = get_site_url();
		$datanode = new ZCiCalDataNode("DESCRIPTION:" . ZCiCal::formatContent( $url ));
		$ical->curnode->data[$datanode->getName()] = $datanode;
		//VTIMEZONE
		$vtimezone = new ZCiCalNode("VTIMEZONE", $ical->curnode);
		$vtimezone->addNode(new ZCiCalDataNode("TZID:Europe/Paris"));
		
		return $ical;
	}
	
	public static function add_edvpost_to_ZCiCal($post, $ical){
		$metas = get_post_meta($post->ID, '', true);
		foreach($metas as $key=>$value)
			if(is_array($value))
				$metas[$key] = implode(', ', $value);
		$metas['date_start'] = self::sanitize_datetime($metas['edp-date-debut'], $metas['edp-heure-debut']);
		$metas['date_end'] = self::sanitize_datetime($metas['edp-date-fin'], $metas['edp-heure-fin'], $metas['edp-date-debut'], $metas['edp-heure-debut']);
				
		$vedvpost = new ZCiCalNode("VEVENT", $ical->curnode);

		// add start date
		$vedvpost->addNode(new ZCiCalDataNode("CREATED;TZID=Europe/Paris:" . ZCiCal::fromSqlDateTime($post->post_date)));

		// DTSTAMP is a required item in VEVENT
		$vedvpost->addNode(new ZCiCalDataNode("DTSTAMP;TZID=Europe/Paris:" . ZCiCal::fromSqlDateTime()));

		// add last modified date
		$vedvpost->addNode(new ZCiCalDataNode("LAST-MODIFIED;TZID=Europe/Paris:" . ZCiCal::fromSqlDateTime($post->post_modified)));

		// Add status
		$vedvpost->addNode(new ZCiCalDataNode("STATUS:" . self::get_vcalendar_status( $post )));

		// add title
		$vedvpost->addNode(new ZCiCalDataNode("SUMMARY:" . $post->post_title));

		// add start date
		$vedvpost->addNode(new ZCiCalDataNode("DTSTART;TZID=Europe/Paris:" . ZCiCal::fromSqlDateTime($metas['date_start'])));

		// add end date
		if($metas['date_end'])
			$vedvpost->addNode(new ZCiCalDataNode("DTEND;TZID=Europe/Paris:" . ZCiCal::fromSqlDateTime($metas['date_end'])));

		// UID is a required item in VEVENT, create unique string for this edvpost
		// Adding your domain to the end is a good way of creating uniqueness
		$parse = parse_url(content_url());
		$uid = sprintf('%s[%d]@%s', edv_Post::post_type, $post->ID, $parse['host']);
		$vedvpost->addNode(new ZCiCalDataNode("UID:" . $uid));

		// Add description
		$vedvpost->addNode(new ZCiCalDataNode("DESCRIPTION:" . ZCiCal::formatContent( $post->post_content)));

		// Add fields
		foreach([
			'LOCATION'=>'edp-localisation'
			, 'ORGANISATEUR'=>'edp-organisateur'
			, 'EMAIL'=>'edp-email'
			, 'PHONE'=>'edp-phone'
		] as $node_name => $meta_key)
			if( ! empty( $metas[$meta_key]))
				$vedvpost->addNode(new ZCiCalDataNode($node_name . ':' . ZCiCal::formatContent( $metas[$meta_key])));

		// Add terms
		foreach([ 
			'CATEGORIES' => edv_Post::taxonomy_ev_category
			, 'CITIES' => edv_Post::taxonomy_city
			, 'DIFFUSIONS' => edv_Post::taxonomy_diffusion
		] as $node_name => $tax_name){
			$terms = edv_Post::get_post_terms ($tax_name, $post->ID, 'names');
			if($terms){
				//$terms = array_map(function($tax_name){ return str_replace(',','-', $tax_name);}, $terms);//escape ','
				foreach($terms as $term_name)
					$vedvpost->addNode(new ZCiCalDataNode($node_name . ':' . ZCiCal::formatContent( $term_name)));
					
				// $vedvpost->addNode(new ZCiCalDataNode($node_name . ':' . ZCiCal::formatContent( implode(',', $terms) )));
			}
		}
		return $vedvpost;
	}
	
	public static function sanitize_datetime($date, $time, $date_start = false, $time_start = false){
		if( ! $date ){
			// debug_log('sanitize_datetime(', $date, $time, $date_start);
			//if not end date, not time and start date contains time, skip dtend
			if($date_start
			&& (! $time || $time == '00:00' || $time == '00:00:00')
			&& $time_start)
				return '';
				
			$date = $date_start;
		}
		if( ! $date )
			return;
		if( $date_start
		&& (! $time || $time == '00:00' || $time == '00:00:00')
		&& ! $time_start){
			//date_start without hour, date_end is the next day, meaning 'full day'
			return date('Y-m-d', strtotime($date . ' + 1 day'));
		}
		$dateTime = rtrim($date . ' ' . str_replace('h', ':', $time));
		if($dateTime[strlen($dateTime)-1] === ':')
			$dateTime .= '00';
		$dateTime = preg_replace('/\s+00\:00(\:00)?$/', '', $dateTime);
		return $dateTime;
	}
		
	public static function get_vcalendar_status($post){
		switch($post->post_status){
			case 'publish' :
				return 'CONFIRMED';
			default :
				return strtoupper($post->post_status);//only CANCELLED or DRAFT or TENTATIVE
		}
	}
	
	
	/****************
	*/
	
	/**
	 * Retourne le nom d'un fichier temporaire pour le téléchargement de l'export des messages
	 */
	public static function get_export_filename($extension, $sub_path = 'export'){
		$folder = self::get_export_folder($sub_path);
		$file = wp_tempnam(EDV_TAG, $folder . '/');
		return str_replace('.tmp', '.' . $extension, $file);
	}
	/**
	 * Retourne le répertoire d'exportation pour téléchargement
	 */
	public static function get_export_folder($sub_path = 'export'){
		$folder = WP_CONTENT_DIR;
		if($sub_path){
			$folder .= '/' . $sub_path;
			if( ! file_exists($folder) )
				mkdir ( $folder );
		}
		$period = wp_date('Y-m');
		$folder .= '/' . $period;
		if( ! file_exists($folder) )
			mkdir ( $folder );
		
		return $folder;
	}
	public static function get_export_url($file = false, $sub_path = 'export'){
		$url = content_url($sub_path);
		$period = wp_date('Y-m');
		$url .= '/' . $period;
		if($file)
			$url .= '/' . $file;
		return $url;
	}
	/**
	 * Nettoie le répertoire d'exportation pour téléchargement
	 */
	public static function clear_export_history($sub_path = 'export'){
		$folder = dirname(self::get_export_folder($sub_path));
		if( ! file_exists($folder) )
			return;
		$period = wp_date('Y-m');
		$periods_to_keep = [ $period, wp_date('Y-m', strtotime($period . '-01 - 1 month'))];
		
		$cdir = scandir($folder);
		foreach ($cdir as $key => $value){
			if (!in_array($value, array(".",".."))){
				if (is_dir($folder . DIRECTORY_SEPARATOR . $value)
				&& ! in_array($value, $periods_to_keep)){
					rrmdir($folder . DIRECTORY_SEPARATOR . $value);
				}
			}
		}
	}
}
