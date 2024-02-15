<?php

/**
 * edv -> Forum -> Messages
 * Collection de messages
 */
class edv_Comments {

	private static $initiated = false;
	public static $default_comments_query = [];
	
	private static $default_comments_per_page = 30;
	
	private static $filters_summary = null;

	public static function init() {
		if ( ! self::$initiated ) {
			
			self::$initiated = true;

			self::init_hooks();
		}
	}

	/**
	 * Hook
	 */
	public static function init_hooks() {

	}
	/*
	 * Hook
	 ******/
	
	public static function get_url($forum, $comment = false){
		$page = edv_Forum::get_page_of_forum($forum);
		if($page){
			$url = get_permalink($page->ID);
			if( $comment ){
				//TODO
			}
		}
		else
			$url = home_url();
		return $url;
	}
	
	/**
	 *
	 * Appelée lors du get_list_html
	 */
	public static function init_default_comments_query( $page ) {
		
		self::$default_comments_query = array(
			'post_id' => $page->ID,
			'comment_type' => 'comment',
			'status' => '1',
			
			// Echec de LEFT JOIN : cf on_get_comments_clauses_cb()
			// 'meta_query' => [ 
				// [
					// 'relation' => 'OR', [
						// [
							// 'key' => 'status',
							// 'value' => 'ended',
							// 'compare' => '!='
						// ],
						// [
							// 'key' => 'status',
							// 'value'   => 'completely',
							// 'compare' => 'NOT EXISTS'
						// ]
					// ]
				// ]
			// ],
			'orderby' => [
				'comment_date' => 'DESC'
			],
			
			'number' => self::$default_comments_per_page
		);

	}
	
	/**
	* Retourne les paramètres pour WP_Query avec les paramètres par défaut.
	* N'inclut pas les filtres.
	*/
	public static function get_comments_query(...$queries){
		$all = self::$default_comments_query;
		// echo "<div style='margin-left: 15em;'>";
		foreach ($queries as $query) {
			if( ! is_array($query)){
				if(is_numeric($query))
					$query = array('number' => $query);
				else
					$query = array();
			}
			if(isset($query['meta_query'])){
				if(isset($all['meta_query'])){
					$all['meta_query'] = array(
						(string)uniqid()=> $all['meta_query']
						, (string)uniqid()=> $query['meta_query']
						, 'relation' => 'AND'
					);
				}
				else
					$all['meta_query'] = $query['meta_query'];
				
				unset($query['meta_query']);
			}
			$all = array_merge($all, $query);
		}
		// var_dump($all);
		// echo "</div>";
		// debug_log('get_comments_query', $all);
		return $all;
		
	}
	
	/**
	 * Recherche des messages d'un mois
	 */
	public static function get_week_comments($year_week){
		if( ! $year_week) $year_week = date('Y-w');
		
		$dates = get_week_dates(substr($year_week, 0,4), substr($year_week, 5,2));
		$date_min = $dates['start'];
		$date_max = $dates['end'];
		
		$query = array(
			'date_query' => [
				'after'     => $date_min,
				'before'    => $date_max,
				'inclusive' => true,
			],
			'orderby' => [
				'comment_date' => 'DESC'
			],
			'nopaging' => true
			
		);
		
		$comments = self::get_comments($query);
		
		return $comments;
    }
	
	/**
	 * Recherche de messages
	 */
	public static function get_comments(...$queries){
		$query = self::get_comments_query(...$queries);
		
		add_filter( 'comments_clauses', array(__CLASS__, 'on_get_comments_clauses_cb' ), 10, 2);
        $the_query = new WP_Comment_Query( $query );
		remove_filter( 'comments_clauses', array(__CLASS__, 'on_get_comments_clauses_cb' ), 10);
        
		return $the_query->comments; 
    }
	public static function on_get_comments_clauses_cb($clauses, $query){
		global $wpdb;
		// debug_log('on_get_comments_clauses_cb', $clauses);
		$status_meta_alias = 'commmeta_status';
		$clauses['join'] .= " LEFT JOIN {$wpdb->commentmeta} {$status_meta_alias}"
						. " ON ( {$wpdb->comments}.comment_ID = {$status_meta_alias}.comment_id AND {$status_meta_alias}.meta_key = 'status' )";
		$clauses['where'] .= " AND ({$status_meta_alias}.comment_id IS NULL OR {$status_meta_alias}.meta_value != 'ended')";
		return $clauses;
	}
	
	/**
	 * Recherche de tous les mois contenant des messages mais aussi les mois sans.
	 * Return array($week => $count)
	 */
	public static function get_comments_weeks( $page ){
		global $wpdb;
		$blog_prefix = $wpdb->get_blog_prefix();
		$status_meta_alias = 'commmeta_status';
		
		//Find this in other blog 
		$sql = "SELECT DISTINCT DATE_FORMAT(comment_date, '%Y') as year
				, DATE_FORMAT(comment_date, '%u') as week
				, COUNT(comments.comment_ID) as count
				FROM {$blog_prefix}comments comments
				LEFT JOIN {$blog_prefix}commentmeta {$status_meta_alias}
					ON comments.comment_ID = {$status_meta_alias}.comment_id
					AND {$status_meta_alias}.meta_key = 'status'
				WHERE comment_post_ID = {$page->ID}
					AND comment_date > DATE_SUB(NOW(), INTERVAL 1 MONTH)
					AND comment_type = 'comment'
					AND comment_approved = '1'
					AND ({$status_meta_alias}.meta_value IS NULL OR {$status_meta_alias}.meta_value != 'ended')
		";
		
		$sql .= "GROUP BY year, week
				ORDER BY year DESC, week DESC
		";
		$result = $wpdb->get_results($sql);
		// debug_log('get_comments_weeks', $sql, $result);
		$weeks = [];
		$prev_row = false;
		foreach($result as $row){
			if($prev_row)
				if($prev_row->year === $row->year){
					for($m = (int)$prev_row->week + 1; $m < (int)$row->week; $m++)
						$weeks[$prev_row->year . '-' . sprintf("%02d",$m)] = 0;
				}
				elseif((int)$prev_row->year === (int)$row->year - 1){
					for($m = (int)$prev_row->week + 1; $m <= 12; $m++)
						$weeks[$prev_row->year . '-' . $m] = 0;
					for($m = 1; $m < (int)$row->week; $m++)
						$weeks[$row->year . '-' . sprintf("%02d",$m)] = 0;
				}
				elseif((int)$prev_row->year < (int)$row->year - 1)
					break;
			$weeks[$row->year . '-' . $row->week] = (int)$row->count;
			$prev_row = $row;
		}
		return $weeks;
    }

	/**
	* Rendu Html des messages sous forme d'arborescence par mois
	*
	*/
	public static function get_list_html($forum, $content = '', $options = false){
		
		$page = edv_Forum::get_page_of_forum($forum);
		if( ! $page )
			return false;
		self::init_default_comments_query($page);
			
		if(!isset($options) || !is_array($options))
			$options = array();
		
		$options = array_merge(
			array(
				'max_messages' => 30,
				'weeks' => -1,
				'mode' => 'list' //list|email|text|calendar|TODO...
			), $options);
		if( $options['mode'] == 'email' ){
		}
		
		$weeks = self::get_comments_weeks( $page );
		
		if($options['weeks'] > 0 && count($weeks) > $options['weeks'])
			$weeks = array_slice($weeks, 0, $options['weeks'], true);
		
		$messages_count = 0;
		$weeks_count = 0;
		foreach($weeks as $week => $week_messages_count) {
			$messages_count += $week_messages_count;
			$weeks_count++;
			if($messages_count >= $options['max_messages']){
				break;
			}
		}
		
		if( $messages_count === 0)
			return false;
		
		$html = sprintf('<div class="edv-edvforummsgs edv-edvforummsgs-%s">', $options['mode']);
			
		$not_empty_week_index = 0;
		$messages_count = 0;
		
		$html .= '<ul>';
		foreach($weeks as $week => $week_messages_count) {
			
			$week_dates = get_week_dates(substr($week, 0,4), substr($week, 5,2));
			if( substr($week_dates['start'], 5,2) === substr($week_dates['end'], 5,2)){
				$week_dates['start'] = wp_date('j', strtotime($week_dates['start']));
				$week_dates['end'] = wp_date('j F Y', strtotime($week_dates['end']));
			}
			else {
				$week_dates['start'] = wp_date('j F', strtotime($week_dates['start']));
				$week_dates['end'] = wp_date('j F Y', strtotime($week_dates['end']));
			}
			if(trim(substr($week_dates['start'], 0,2)) == '1')
				$week_dates['start'] = '1er ' . substr($week_dates['start'],3);
			if(trim(substr($week_dates['end'], 0,2)) == '1')
				$week_dates['end'] = '1er ' . substr($week_dates['end'],3);
			
			$week_label = sprintf('du %s au %s', $week_dates['start'], $week_dates['end']);
			
			$html .= sprintf(
				'<li><div class="week-title toggle-trigger %s %s">%s <span class="nb-items">(%d)</span></div>
				<ul id="week-%s" class="edvforummsgs-week toggle-container">'
				, $week_messages_count === 0 ? 'no-items' : ''
				, $week_messages_count ? 'active' : ''
				, $week_label
				, $week_messages_count
				, $week
			);
			if( $week_messages_count){
				$html .= self::get_week_comments_list_html( $week, $options );
			}
		
			$html .= '</ul></li>';
			
			if($week_messages_count > 0)
				$not_empty_week_index++;
			$messages_count += $week_messages_count;
		}
		
		$html .= '</ul>';
		
		$html .= '</div>' . $content;
		return $html;
	}
	
	/**
	* Rendu Html des messages destinés au corps d'un email (newsletter)
	*
	*/
	public static function get_list_for_email($forum, $content = '', $options = false){
		edv_Forum::init_page($forum);
		
		if(!isset($options) || !is_array($options))
			$options = array();
		$options = array_merge(
			array(
				'weeks' => date('d') < 10 ? 1 : 2, //à partir du 10, on met le mois suivant aussi
				'mode' => 'email'
			), $options);
				
		$css = '<style>'
			. '
.entry-content {
	font-family: arial;
}
.toggle-trigger {
	margin: 8px 0px 0px 0px;
	font-size: larger;
	padding-left: 10px;
	background-color: #F5F5F5;
} 
.toggle-trigger a {
	color: #333;
	text-decoration: none;
	display: block;
}
.toggle-container {
	overflow: hidden;
	padding-left: 10px;
}
.toggle-container pre {
	background-color: #F5F5F5;
	color: #333;
	white-space: pre-line;
}
.edv-edvforummsgs-email .week-title {
	margin-top: 1em;
	margin-bottom: 1em;
	font-size: larger;
	font-weight: bold;
	text-decoration: underline;
	text-transform: uppercase;
} 
.edv-edvforummsgs-email .edpost .dates {
	font-size: larger;
	font-weight: bold;
} 
.edv-edvforummsgs-email a-li a-li {
	margin-left: 1em;
	padding-top: 2em;
}
.edv-edvforummsgs-email div.titre, .edv-edvforummsgs-email div.localisation, .edv-edvforummsgs-email div.ev-cities {
	font-weight: bold;
}
.edv-edvforummsgs-email i {
	font-style: normal;
}
.created-since {
	font-size: smaller;
	font-style: italic;
}
.footer {
	border-bottom: solid gray 2px;
	margin-bottom: 2em;
}
'
			. '</style>';
		$html = self::get_list_html($forum, $content, $options );
		
		if( ! $html ){
			if ( edv_Newsletter::is_sending_email() )
				edv_Newsletter::content_is_empty( true );
			return false;
		}
		
		$html = $css . $html;

		foreach([
			'edv-edvforummsgs'=> 'aevs'
			, 'edvforummsgs'=> 'evs'
			, 'edpost-'=> 'edp-'
			, 'edpost '=> 'ev '
			, 'toggle-trigger' => 'tgt'
			, 'toggle-container' => 'tgc'
			
			, '<ul' => '<div class="a-ul"'
			, '</ul' => '</div'
			, '<li' => '<div class="a-li"'
			, '</li' => '</div'
			
			] as $search=>$replace)
			$html = str_replace($search, $replace, $html);
		
		if(false) '{{';//bugg notepad++ functions list
		foreach([
			'/\sedpost="\{[^\}]*\}"/' => '',
			'/\sid="\w*"/' => '',
			'/([\}\>\;]\s)\s+/m' => '$1'
			] as $search=>$replace)
			$html = preg_replace($search, $replace, $html);
		return $html;
	}
	
		
	/**
	* Rendu Html des messages d'un mois sous forme de liste
	*/
	public static function get_week_comments_list_html($week, $options = false){
		
		$messages = self::get_week_comments($week);
		
		if(is_wp_error( $messages)){
			$html = sprintf('<p class="alerte no-messages">%s</p>%s', __('Erreur lors de la recherche des messages.', EDV_TAG), var_export($messages, true));
		}
		elseif($messages){
			$html = '';
			if(count($messages) === 0){
				$html .= sprintf('<p class="alerte no-messages">%s</p>', __('Aucun message trouvé', EDV_TAG));
			}
			else {
				foreach($messages as $event){
					$html .= '<li>' . self::get_list_item_html($event, $options) . '</li>';
				}
				
				//Ce n'est plus nécessaire, les mois sont chargés complètement
				//TODO comment_per_page
				if(count($messages) == self::$default_comments_per_page){
					$html .= sprintf('<li class="show-more"><h3 class="edpost toggle-trigger" ajax="show-more">%s</h3></li>'
						, __('Afficher plus de résultats', EDV_TAG));
				}
			}
		}
		else{
				$html = sprintf('<p class="alerte no-messages">%s</p>', __('Aucun message trouvé', EDV_TAG));
			}
			
		return $html;
	}
	
	public static function get_list_item_html($comment, $options){
		$email_mode = is_array($options) && isset($options['mode']) && $options['mode'] == 'email';
			
		$date_debut = $comment->comment_date;
					
		$url = get_comment_link( $comment );
		$html = '';
		
		if( ! $email_mode )
			$html .= sprintf(
					'<div class="show-comment"><a href="%s">%s</a></div>'
				, $url
				, edv::icon('media-default')
			);
			
		$html .= sprintf('<div id="fmsgid%d" class="edvcomment toggle-trigger" edvcomment="%s">'
			, $comment->comment_ID
			, esc_attr( json_encode(['id'=> $comment->comment_ID, 'date' => $date_debut]) )
		);
		
		if(mysql2date( 'j', $date_debut ) === '1')
			$format_date_debut = 'l j\e\r M Y';
		else
			$format_date_debut = 'l j M Y';
		
		$value = get_comment_meta($comment->comment_ID, 'title', true);
		$html .= sprintf(
				'<div class="date">%s à %s</div>'
				.'<div class="titre">%s</div>'
			.''
			, str_replace(' mar ', ' mars ', strtolower(mysql2date( $format_date_debut, $date_debut)))
			, date('H:i')
			, htmlentities($value));
		
		$html .= '</div>';
		
		$html .= '<div class="toggle-container">';
		
		
		$value = $comment->comment_content;
		if($value){
			$value = preg_replace('/\n[\s\n]+/', "\n", $value);
			$more = '';
			$max_len = 1000;
			if( strlen($value) > $max_len ){
				$more = sprintf('<a href="%s">... <b><i>[continuez la lecture sur le site]</i></b></a>', $url);
				//bugg sic
				while( strlen(substr($value, 0, $max_len)) === 0 && $max_len < 9999)
					$max_len += 7;
				$value = substr($value, 0, $max_len);
			}
			$html .= sprintf('<pre>%s%s</pre>', htmlentities($value), $more );
		}
		
		$value = $comment->comment_author;
		if($value){
			if($comment->comment_author_email && $comment->comment_author != $comment->comment_author_email);
				$value .= sprintf(' <%s>', $comment->comment_author_email);
			$html .= sprintf('<div>Publié par : %s</div>',  htmlentities($value) );
		}
		
		$html .= date_diff_text($comment->comment_date, true, '<div class="created-since">', '</div>');
		
		$html .= '<div class="footer">';
				
			$html .= '<table><tbody><tr>';
			
			if( ! $email_mode )
				$html .= '<td class="trigger-collapser"><a href="#replier">'
					.edv::icon('arrow-up-alt2')
					.'</a></td>';

			$html .= sprintf(
				'<td class="comment-edit"><a href="%s">'
					.'Afficher le message'
					. ($email_mode  ? '' : edv::icon('media-default'))
				.'</a></td>'
				, $url);
				
			$html .= '</tr></tbody></table>';
			
		$html .= '</div>';

		$html .= '</div>';
		
		return $html;
	}
	
}
