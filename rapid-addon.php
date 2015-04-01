<?php

if (!class_exists('RapidAddon')) {
	
	class RapidAddon {

		public $name;
		public $slug;
		public $fields;
		public $import_function;

		function __construct($name, $slug) {

			$this->name = $name;
			$this->slug = $slug;

		}

		function set_import_function($name) {
			$this->import_function = $name;
		}

		function is_active_addon($post_type = null) {
			
			$addon_active = false;

			if ($post_type != null) {
				if (@in_array($post_type, $this->active_post_types)) {
					$addon_active = true;
				}
			}

			$current_theme = wp_get_theme();
			$theme_name = $current_theme->get('Name');

			if (@in_array($theme_name, $this->active_themes)) {
				$addon_active = true;
			}

			if ($this->when_to_run == "always") {
				return true;
			}

			return $addon_active;
		}
		
		function run($when_to_run = "always") {

			if ($when_to_run == "always") {
				$this->when_to_run = "always";
			}

			@$this->active_post_types = $when_to_run['post_types'];
			@$this->active_themes = $when_to_run['themes'];

			add_filter('pmxi_addons', array($this, 'wpai_api_register'));
			add_filter('wp_all_import_addon_parse', array($this, 'wpai_api_parse'));
			add_filter('wp_all_import_addon_import', array($this, 'wpai_api_import'));
			add_filter('pmxi_options_options', array($this, 'wpai_api_options'));
			add_action('pmxi_extend_options_featured',  array($this, 'wpai_api_metabox'), 10, 1);
			add_action('admin_init', array($this, 'admin_notice_ignore'));

		}

		function parse($data) {

			$parsedData = $this->helper_parse($data, $this->options_array());
			return $parsedData;

		}


		function add_field($field_slug, $field_name, $field_type, $enum_values = null) {

			$this->fields[$field_slug] = array("name" => $field_name, "type" => $field_type, "enum_values" => $enum_values);

		}

		function options_array() {

			$options_list = array();

			foreach ($this->fields as $field_slug => $field_params) {
				$options_list[$field_slug] = '';
			}

			$options_arr[$this->slug] = $options_list;

			return $options_arr;

		}

		function wpai_api_options($all_options) {

			$all_options = $all_options + $this->options_array();

			return $all_options;

		}


		function wpai_api_register($addons) {

			if (empty($addons[$this->slug])) {
				$addons[$this->slug] = 1;
			}

			return $addons;

		}


		function wpai_api_parse($functions) {

			$functions[$this->slug] = array($this, 'parse');
			return $functions;

		}


		function wpai_api_import($functions) {

			$functions[$this->slug] = array($this, 'import');
			return $functions;

		}



		function import($importData, $parsedData) {

			if (!$this->is_active_addon($importData['import']->options['custom_type'])) {
				return;
			}

			$import_options = $importData['import']['options'][$this->slug];

	//		echo "<pre>";
	//		print_r($import_options);
	//		echo "</pre>";

			if ( ! empty($parsedData) )	{

				$post_id = $importData['pid'];
				$index = $importData['i'];

				foreach ($this->fields as $field_slug => $field_params) {

					if ($field_params['type'] == 'image') {

						// import the specified image, then set the value of the field to the image ID in the media library

						$image_url_or_path = $parsedData[$field_slug][$index];

						$download = $import_options['download_image'][$field_slug];

						$uploaded_image = PMXI_API::upload_image($post_id, $image_url_or_path, $download, $importData['logger'], true);

						$data[$field_slug] = array(
							"attachment_id" => $uploaded_image,
							"image_url_or_path" => $image_url_or_path,
							"download" => $download
						);

					} else {

						// set the field data to the value of the field after it's been parsed
						$data[$field_slug] = $parsedData[$field_slug][$index];

					}

					// apply mapping rules if they exist
					if ($import_options['mapping'][$field_slug]) {
						$mapping_rules = json_decode($import_options['mapping'][$field_slug], true);

						if (!empty($mapping_rules) and is_array($mapping_rules)) {
							foreach ($mapping_rules as $rule_number => $map_to) {
								if (!empty($map_to[trim($data[$field_slug])])){
									$data[$field_slug] = trim($map_to[trim($data[$field_slug])]);
									break;
								}
							}
						}
					}
					// --------------------


				}

				call_user_func($this->import_function, $post_id, $data, $importData['import']);
			}

		}


		function wpai_api_metabox($post_type) {

			if (!$this->is_active_addon($post_type)) {
				return;
			}

			echo $this->helper_metabox_top($this->name);

			$current_values = $this->helper_current_field_values();

			foreach ($this->fields as $field_slug => $field_params) {

				if ($field_params['type'] == 'text') {

					PMXI_API::add_field(
						'simple',
						$field_params['name'],
						array(
							'tooltip' => '',
							'field_name' => $this->slug."[".$field_slug."]",
							'field_value' => $current_values[$this->slug][$field_slug]
						)
					);

				} else if ($field_params['type'] == 'textarea') {

					PMXI_API::add_field(
						'textarea',
						$field_params['name'],
						array(
							'tooltip' => '',
							'field_name' => $this->slug."[".$field_slug."]",
							'field_value' => $current_values[$this->slug][$field_slug]
						)
					);

				} else if ($field_params['type'] == 'image') {

					PMXI_API::add_field(
						'image',
						$field_params['name'],
						array(
							'tooltip' => '',
							'field_name' => $this->slug."[".$field_slug."]",
							'field_value' => $current_values[$this->slug][$field_slug],

							'download_image' => $current_values[$this->slug]['download_image'][$field_slug],
							'field_key' => $field_slug,
							'addon_prefix' => $this->slug

						)
					);

				} else if ($field_params['type'] == 'radio') {

					PMXI_API::add_field(
						'enum',
						$field_params['name'],
						array(
							'tooltip' => '',
							'field_name' => $this->slug."[".$field_slug."]",
							'field_value' => $current_values[$this->slug][$field_slug],

							'enum_values' => $field_params['enum_values'],
							'mapping' => true,
							'field_key' => $field_slug,
							'mapping_rules' => $current_values[$this->slug]['mapping'][$field_slug],
							'xpath' => $current_values[$this->slug]['xpaths'][$field_slug],
							'addon_prefix' => $this->slug
						)
					);


				}


				echo "<br />";

			}

			echo $this->helper_metabox_bottom();

		}



		/* Get values of the add-ons fields for use in the metabox */
		
		function helper_current_field_values($default = array()) {

			$input = new PMXI_Input();

			$id = $input->get('id');

			$import = new PMXI_Import_Record();			
			if ( ! $id or $import->getById($id)->isEmpty()) { // specified import is not found
				$post = $input->post(			
					$default			
				);
			}
			else {
				$post = $input->post(
					$import->options
					+ $default			
				);		
			}
			
			$is_loaded_template = (!empty(PMXI_Plugin::$session->is_loaded_template)) ? PMXI_Plugin::$session->is_loaded_template : false;		

			$load_options = $input->post('load_template');

			if ($load_options) { // init form with template selected
				
				$template = new PMXI_Template_Record();
				if ( ! $template->getById($is_loaded_template)->isEmpty()) {	
					$post = (!empty($template->options) ? $template->options : array()) + $default;				
				}
				
			} elseif ($load_options == -1){
				
				$post = $default;
								
			}

			return $post;

		}



		function helper_metabox_top($name) {

			return '
			<div class="wpallimport-collapsed wpallimport-section '.$this->slug.' closed">
				<div class="wpallimport-content-section">
					<div class="wpallimport-collapsed-header">
						<h3>'.__($name,'pmxi_plugin').'</h3>	
					</div>
					<div class="wpallimport-collapsed-content" style="padding: 0;">
						<div class="wpallimport-collapsed-content-inner">
							<table class="form-table" style="max-width:none;">
								<tr>
									<td colspan="3">';
		}

		function helper_metabox_bottom() {

			return '				</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>';

		}



		function helper_parse($parsingData, $options) {

			extract($parsingData);

			$data = array(); // parsed data

			if ( ! empty($import->options[$this->slug])){

				$cxpath = $xpath_prefix . $import->xpath;

				foreach ($options[$this->slug] as $option_name => $option_value) {

					if ( ! empty($import->options[$this->slug][$option_name]) ) {

						if ($import->options[$this->slug][$option_name] == "xpath") {
							if ($import->options[$this->slug]['xpaths'][$option_name] == ""){
								$count and $this->data[$option_name] = array_fill(0, $count, "");
							} else {
								$data[$option_name] = XmlImportParser::factory($xml, $cxpath, $import->options[$this->slug]['xpaths'][$option_name], $file)->parse($records);
								$tmp_files[] = $file;						
							}
						} else {
							$data[$option_name] = XmlImportParser::factory($xml, $cxpath, $import->options[$this->slug][$option_name], $file)->parse();
							$tmp_files[] = $file;
						}


					} else {
						$data[$option_name] = array_fill(0, $count, "");
					}

				}

				foreach ($tmp_files as $file) { // remove all temporary files created
					unlink($file);
				}

			}

			return $data;
		}


		function can_update_meta($meta_key, $import_options) {

			//echo "<pre>";
			//print_r($import_options['options']);
			//echo "</pre>";
			
			$import_options = $import_options['options'];

			if ($import_options['update_all_data'] == 'yes') return true;

			if ( ! $import_options['is_update_custom_fields'] ) return false;			

			if ($import_options['update_custom_fields_logic'] == "full_update") return true;
			if ($import_options['update_custom_fields_logic'] == "only" and ! empty($import_options['custom_fields_list']) and is_array($import_options['custom_fields_list']) and in_array($meta_key, $import_options['custom_fields_list']) ) return true;
			if ($import_options['update_custom_fields_logic'] == "all_except" and ( empty($import_options['custom_fields_list']) or ! in_array($meta_key, $import_options['custom_fields_list']) )) return true;

			return false;

		}

		function can_update_image($import_options) {

			$import_options = $import_options['options'];

			if ($import_options['update_all_data'] == 'yes') return true;

			if (!$import_options['is_update_images']) return false;			

			if ($import_options['is_update_images']) return true;			

			return false;
		}


		function admin_notice_ignore() {
			if (isset($_GET[$this->slug.'_ignore']) && '0' == $_GET[$this->slug.'_ignore'] ) {
				update_option($this->slug.'_ignore', 'true');
			}
		}

		function display_admin_notice() {


			if ($this->notice_text) {
				$notice_text = $this->notice_text;
			} else {
				$notice_text = $this->name.' requires WP All Import <a href="http://www.wpallimport.com/" target="_blank">Pro</a> or <a href="http://wordpress.org/plugins/wp-all-import" target="_blank">Free</a>.';
			}

			if (!get_option($this->slug.'_ignore')) {

				?>

	    		<div class="error">
	    		    <p><?php _e(
		    		    	sprintf(
	    			    		$notice_text.' | <a href="%1$s">Hide Notice</a>',
	    			    		'?'.$this->slug.'_ignore=0'
	    			    	), 
	    		    		'rapid_addon_'.$this->slug
	    		    	); ?></p>
			    </div>

				<?php

			}

		}

		function admin_notice($notice_text = '') {

			if ($notice_text != '') {
				$this->notice_text = $notice_text;
			}

			add_action('admin_notices', array($this, 'display_admin_notice'));

		}


	}

}





