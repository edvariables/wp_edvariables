<?php

/**
 * edv Admin -> Edit -> Post_type implement
 * Custom post type for WordPress in Admin UI.
 * 
 * Classe d'implémentation pour l'édition d'un post quel que soit son type 
 */
abstract class edv_Admin_Edit_Post_Type {

	static $the_post_is_new = false;

	public static function init() {
		self::$the_post_is_new = basename($_SERVER['PHP_SELF']) == 'post-new.php';
	}
	
	
	/**
	 * HTML render in metaboxes
	 */
	public static function metabox_html($fields, $post, $metabox, $parent_field = null){
		if( ! is_array($fields) )
			return;
		foreach ($fields as $field) {
			$name = $field['name'];
			if($parent_field !== null)
				$name = sprintf($name, $parent_field['name']);
			if($name == 'post_content')
				$meta_value = $post->post_content;
			elseif( ! $post )
				$meta_value = '';
			elseif(is_a($post, 'WP_Term'))
				$meta_value = get_term_meta($post->term_id, $name, true);
			else
				$meta_value = get_post_meta($post->ID, $name, true);
			$id = ! array_key_exists ( 'id', $field ) || ! $field['id'] ? $name : $field['id'];
			if($parent_field !== null)
				$id = sprintf('%s.%s', $id, array_key_exists('id', $parent_field) ? $parent_field['id'] : $parent_field['name']); //TODO A vérifier à l'enregistrement
			$val = ! array_key_exists ( 'value', $field ) || ! $field['value'] ? $meta_value : $field['value'];
			$label = ! array_key_exists ( 'label', $field ) || ! $field['label'] ? false : $field['label'];
			$input = ! array_key_exists ( 'input', $field ) || ! $field['input'] ? '' : $field['input'];
			$input_type = ! array_key_exists ( 'type', $field ) || ! $field['type'] ? 'text' : $field['type'];
			$style = ! array_key_exists ( 'style', $field ) || ! $field['style'] ? '' : $field['style'];
			$class = ! array_key_exists ( 'class', $field ) || ! $field['class'] ? '' : $field['class'];
			$container_class = ! array_key_exists ( 'container_class', $field ) || ! $field['container_class'] ? '' : $field['container_class'];
			$readonly = ! array_key_exists ( 'readonly', $field ) || ! $field['readonly'] ? false : $field['readonly'];
			$unit = ! array_key_exists ( 'unit', $field ) || ! $field['unit'] ? false : $field['unit'];
			$learn_more = ! array_key_exists ( 'learn-more', $field ) || ! $field['learn-more'] ? false : $field['learn-more'];
			if( $learn_more && ! is_array($learn_more))
				$learn_more = [$learn_more];
			$warning = ! array_key_exists ( 'warning', $field ) || ! $field['warning'] ? false : $field['warning'];
			if( $warning && ! is_array($warning))
				$warning = [$warning];
			
			$container_class .= ' edv-metabox-row';
			$container_class .= ' is' . ( current_user_can('manage_options') ? '' : '_not') . '_admin';
			if($parent_field != null)
				$container_class .= ' edv-metabox-subfields';

			?><div class="<?php echo trim($container_class);?>"><?php

			switch ($input_type) {
				case 'number' :
				case 'int' :
					$input = 'text';
					$input_type = 'number';
					break;

				case 'checkbox' :
				case 'bool' :
					$input = 'checkbox';
					break;

				default:
					if(!$input_type)
						$input_type = 'text';
					break;
			}

			// Label , sous pour checkbox
			if($label && ! in_array( $input, ['label', 'link', 'checkbox'])) {
				echo '<label for="'.$name.'">' . htmlentities($label) . ' : </label>';
			}

			switch ($input) {
				////////////////
				case 'label':
					echo '<label id="'.$id.'" for="'.$name.'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. '>' . htmlentities($label).'</label>';
					break;

				////////////////
				case 'link':
					echo '<label id="'.$id.'" for="'.$name.'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. '>' . $label.'</label>';
					break;

				////////////////
				case 'textarea':
					echo '<textarea id="'.$id.'" name="'.$name
						. ($readonly ? ' readonly ' : '')
						.'">'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. htmlentities($val).'</textarea>'
						. ($unit ? ' ' . $unit : '');;
					break;
				
				////////////////
				case 'tinymce':
					$editor_settings = ! array_key_exists ( 'settings', $field ) || ! $field['settings'] ? null : $field['settings'];
					$editor_settings = wp_parse_args($editor_settings, array( //valeurs par défaut
						'textarea_rows' => 10,
						'readonly' => $readonly
					));
				    wp_editor( $val, $id, $editor_settings);
					break;
				
				
				////////////////
				case 'select':
					echo '<select id="'.$id.'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						.' name="' . $name
						. ($readonly ? ' readonly ' : '')
						. '">';

					$values = ! array_key_exists ( 'values', $field ) || ! $field['values'] ? false : $field['values'];
					if(is_array($values)){
						foreach($values as $item_key => $item_label){
							echo '<option ' . selected( $val, $item_key ) . ' value="' . $item_key . '">'. htmlentities($item_label) . '</option>';
						}
					}
					echo '</select>'
						. ($unit ? ' ' . $unit : '');
					break;
				
				////////////////
				case 'checkbox':
					echo '<label>';
					echo '<input id="'.$id.'" type="checkbox" name="'.$name.'" '
						. ($val && $val !== 'unchecked' ? ' checked="checked"' : '')
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '') 
						. ($readonly ? '  onclick="return false" ' : '')
						. ' value="1" />';
					echo htmlentities($label) . '</label>'
						. ($unit ? ' ' . $unit : '');
					break;
				
				////////////////
				case 'date':
					//<input class="wpcf7-form-control wpcf7-date wpcf7-validates-as-required wpcf7-validates-as-date" aria-required="true" aria-invalid="false" value="" type="date" name="event-date-debut">
					$class = " wpcf7-date" . ($class ? " $class" : "");
					echo '<input id="'.$id.'" type="date" name="'.$name.'" '
						. ($val ? ' value="'.htmlentities($val) .'"' : '')
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '') 
						. ($readonly ? ' readonly ' : '')
						. ' />'
						. ($unit ? ' ' . $unit : '');
					break;
				
				////////////////
				/*case 'time':
					//<input class="wpcf7-form-control wpcf7-date wpcf7-validates-as-required wpcf7-validates-as-date" aria-required="true" aria-invalid="false" value="" type="date" name="event-date-debut">
					$class = " time-picker" . ($class ? " $class" : "");
					$options = '';
					for($h = 0; $h < 24; $h++)
						for($m = 0; $m < 4; $m++){
							$option = sprintf("%02d", $h) . ':' .sprintf("%02d", $m*15);
							$options .= '<option value="$option"'. ($option == $val ? ' selected' : '') . ">$option</option>";
						}
					echo '<select id="'.$id.'" name="'.$name.'" '
						. ($val ? ' value="'.htmlentities($val) .'"' : '')
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '') 
						. '>' . $options
						. ' </select>';
					break;*/
				case 'time':
					echo '<input id="'.$id.'"'
						. ' type="' . $input_type .'"'
						. ' name="'.$name.'"'
						. ' value="'.htmlentities($val) .'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '')
						. ' placeholder="hh:mm"'
						. ' maxlength="5" size="5"'
						. ($readonly ? ' readonly ' : '')
						. '/>'
						. ($unit ? ' ' . $unit : '');
					break;
				
				////////////////
				case 'input':
				default:
					//TODO phone, email, checkbox, number, int, bool, yes|no, ...
					echo '<input id="'.$id.'"'
						. ' type="' . $input_type .'"'
						. ' name="'.$name.'"'
						. ' value="'.htmlentities($val) .'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '')
						. ($readonly ? ' readonly ' : '')
						. '/>'
						. ($unit ? ' ' . $unit : '');
					break;
			}
		
			if($learn_more)
				foreach($learn_more as $comment){
					echo '<br>';
					if($input != 'checkbox')
						echo '<label></label>';
					?><span class="dashicons-before dashicons-welcome-learn-more"><?=$comment?></span><?php
				}
		
			if($warning)
				foreach($warning as $comment){
					echo '<br>';
					if($input != 'checkbox')
						echo '<label></label>';
					?><span class="dashicons-before dashicons-warning"><?=$comment?></span><?php
				}
		

			//sub fields
			if( array_key_exists('fields', $field) && is_array($field['fields'])){
				self::metabox_html($field['fields'], $post, $metabox, $field);
			}
		
			
			?></div><?php
		}
	}
	
	/**
	* Should be overrided
	**/
	abstract public static function get_metabox_all_fields();
	
	/**
	 * Save metaboxes' input values
	 * Field can contain sub fields
	 */
	public static function save_metaboxes($post_ID, $post, $parent_field = null){
		if($parent_field === null){
			$fields = static::get_metabox_all_fields();
		}
		else
			$fields = $parent_field['fields'];
		foreach ($fields as $field) {
			if(!isset($field['type']) || $field['type'] != 'label'){
				$name = $field['name'];
				if($parent_field !== null && isset($parent_field['name']))
					$name = sprintf($name, $parent_field['name']);//TODO check
				// remember : a checkbox unchecked does not return any value
				if( array_key_exists($name, $_POST)){
					$val = $_POST[$name];
				}
				else {
					// TODO "remember : a checkbox unchecked does not return any value" so is 'default' = true correct ?
					if(self::$the_post_is_new
					&& isset($field['default']) && $field['default'])
						$val = $field['default'];
					elseif( (isset($field['input']) && ($field['input'] === 'checkbox' || $field['input'] === 'bool'))
						 || (isset($field['type'])  && ($field['type']  === 'checkbox' || $field['type']  === 'bool')) ) {
						$val = '0';
					}
					else
						$val = null;
				}
				update_post_meta($post_ID, $name, $val);
			}

			//sub fields
			if(isset($field['fields']) && is_array($field['fields'])){
				self::save_metaboxes($post_ID, $post, $field);
			}
		}
		
		return false;
	}
}
?>