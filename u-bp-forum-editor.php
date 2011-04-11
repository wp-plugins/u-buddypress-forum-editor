<?php
/*
Plugin Name: U BuddyPress Forum Editor
Plugin URI: http://urlless.com/buddypress-plugin-u-buddypress-forum-editor/
Description: This plugin has the ability to convert HTML TEXTAREA fields to TinyMCE for BuddyPress Forum. Several options are provided, such as customizing button group, form validation, etc.
Author: Taehan Lee
Author URI: http://urlless.com
Version: 1.0
Requires at least: WordPress 3.0.0, BuddyPress 1.2.8
Tested up to: WordPress 3.1.1, BuddyPress 1.2.8
*/

class UBPForumEditor {
	
	var $plugin_id = 'ubpfeditor';
	var $plugin_url, $opts, $elements;
	
	function UBPForumEditor(){
		register_activation_hook( __FILE__, array(&$this, 'install') );
		$this->plugin_url = plugin_dir_url(__FILE__);
		load_plugin_textdomain($this->plugin_id, false, dirname(plugin_basename(__FILE__)).'/lang/');
		add_action( 'bp_init', array(&$this, 'bp_init') );
		add_action( 'admin_menu', array(&$this, 'admin_menu') );
	}
	
	function bp_init(){
		global $bp;
		
		if( !$this->options_validate() ) return;
		
		if ( ($bp->current_component=='groups' AND $bp->current_action == 'forum')
			|| ($bp->current_component=='forums' AND $bp->current_action == '') ){
			wp_enqueue_script( 'jquery' );
			if( ! empty($this->opts['form_validate']) )
				wp_enqueue_script( 'bp-forum-form-validate', $this->plugin_url.'inc/form-validate.js' );
			add_filter( 'bp_forums_allowed_tags', array(&$this, 'allowed_tags'), 1);
			add_action( 'wp_footer', array(&$this, 'editor'));
		}
	}
	
	function options_validate(){
		$this->opts = get_option($this->plugin_id);
		
		if( empty($this->opts['enable']) ) return false;
		
		$this->elements = array();
		if( !empty($this->opts['enable_topic']) ) array_push($this->elements, 'textarea[name=topic_text]');
		if( !empty($this->opts['enable_reply']) ) array_push($this->elements, 'textarea[name=reply_text]', 'textarea[name=post_text]');
		if( count($this->elements)==0 ) return false;
		$this->elements = implode(',', $this->elements);
		
		if( empty($this->opts['buttons1']) AND empty($this->opts['buttons2']) ) return false;
		if( empty($this->opts['buttons1']) AND !empty($this->opts['buttons2']) ) {
			$this->opts['buttons1'] = $this->opts['buttons2'];
			$this->opts['buttons2'] = "";
		}
		
		$this->opts['editor_style'] = !empty($this->opts['editor_style']) ? $this->opts['editor_style'] : $this->plugin_url.'inc/content.css';
		return true;
	}
	
	function allowed_tags(){
		global $allowedtags;
		require_once plugin_dir_path(__FILE__).'inc/allowed-tags.php';
		return apply_filters( $this->plugin_id.'_allowedtags', $allowedtags );
	}
	
	function editor( ) {
		global $tinymce_version;
		$baseurl = includes_url('js/tinymce');
		$mce_locale = 'en';
		$plugins = array( 'inlinepopups', 'paste', 'fullscreen' );
		$initArray = array (
			'mode' => 'specific_textareas',
			'editor_selector' => 'theEditor',
			'width' => '100%',
			'height' => $this->opts['height'],
			'theme' => 'advanced',
			'skin' => 'default',
			'theme_advanced_buttons1' => $this->opts['buttons1'],
			'theme_advanced_buttons2' => $this->opts['buttons2'],
			'theme_advanced_buttons3' => '',
			'theme_advanced_buttons4' => '',
			'language' => $mce_locale,
			'theme_advanced_toolbar_location' => 'top',
			'theme_advanced_toolbar_align' => 'left',
			'theme_advanced_statusbar_location' => 'bottom',
			'theme_advanced_resizing' => true,
			'theme_advanced_resize_horizontal' => false,
			'theme_advanced_font_sizes' => "80%,100%,120%,150%,200%,300%",
			'theme_advanced_resizing_use_cookie' => true,
			'dialog_type' => 'modal',
			'relative_urls' => false,
			'remove_script_host' => false,
			'convert_urls' => false,
			'apply_source_formatting' => false,
			'remove_linebreaks' => true,
			'gecko_spellcheck' => true,
			'entities' => '38,amp,60,lt,62,gt',
			'accessibility_focus' => true,
			'tabfocus_elements' => 'major-publishing-actions',
			'media_strict' => false,
			'paste_remove_styles' => true,
			'paste_remove_spans' => true,
			'paste_strip_class_attributes' => 'all',
			'paste_text_use_dialog' => true,
			'plugins' => implode( ',', $plugins ),
			'content_css' => $this->opts['editor_style']
		);
		
		$version = apply_filters('tiny_mce_version', '');
		$version = 'ver=' . $tinymce_version . $version;
		$mce_options = '';
		foreach ( $initArray as $k => $v ) {
			if ( is_bool($v) ) {
				$val = $v ? 'true' : 'false'; $mce_options .= $k . ':' . $val . ', '; continue;
			} elseif ( !empty($v) && is_string($v) && ( '{' == $v{0} || '[' == $v{0} ) ) {
				$mce_options .= $k . ':' . $v . ', '; continue;
			}
			$mce_options .= $k . ':"' . $v . '", ';
		}
		$mce_options = rtrim( trim($mce_options), '\n\r,' ); 
		?>
<script type="text/javascript" src="<?php echo $baseurl?>/tiny_mce.js?<?php echo $version?>"></script>
<script type="text/javascript" src="<?php echo $baseurl?>/langs/wp-langs-en.js?<?php echo $version?>"></script>
<script type="text/javascript">
jQuery('<?php echo $this->elements?>').addClass('theEditor');
tinyMCEPreInit = { base : "<?php echo $baseurl; ?>", suffix : "", query : "<?php echo $version; ?>", mceInit : {<?php echo $mce_options; ?>}, load_ext : function(url,lang){ var sl=tinymce.ScriptLoader; sl.markDone(url+'/langs/'+lang+'.js'); sl.markDone(url+'/langs/'+lang+'_dlg.js');} }; (function(){ var t=tinyMCEPreInit, sl=tinymce.ScriptLoader, ln=t.mceInit.language, th=t.mceInit.theme, pl=t.mceInit.plugins; sl.markDone(t.base+'/langs/'+ln+'.js'); sl.markDone(t.base+'/themes/'+th+'/langs/'+ln+'.js'); sl.markDone(t.base+'/themes/'+th+'/langs/'+ln+'_dlg.js'); })(); tinyMCE.init(tinyMCEPreInit.mceInit);
</script>
<style>table.mceLayout { margin-bottom: 20px; }.mceTop { background: #666 !important; }</style>

<?php global $is_bp_forum_form_validate; if( ! $is_bp_forum_form_validate AND !empty($this->opts['form_validate']) ): ?>
<script type="text/javascript">
jQuery(function(){ bp_forum_form_validate.init({
	form_id: 'forum-topic-form',
	error_div_id: '<?php echo $this->plugin_id?>-error',
	error_msg: {
		title: '<?php echo esc_js(__('Error: Please enter a title.', $this->plugin_id))?>',
		content: '<?php echo esc_js(__('Error: Please enter a content.', $this->plugin_id))?>',
		group_id: '<?php echo esc_js(__('Error: Please select a Group Forum.', $this->plugin_id))?>'
	}
}); });
</script>
<style>#<?php echo $this->plugin_id?>-error { background: #FFEBE8; border: 1px solid #C66; color: #AA0000; padding: 6px 15px; margin: 20px 0; border-radius: 3px; display: none; }</style>
<?php $is_bp_forum_form_validate = true; endif; ?>

		<?php
	}
	
	
	
	
	
	
	
	
	/* admin ----------------------------------------------------*/
	
	function install() {
		$options = array (
			'enable' => '',
			'enable_topic' => '1',
			'enable_reply' => '1',
			'form_validate' => '1',
			'buttons1' => $this->get_default_buttons1(),
			'buttons2' => $this->get_default_buttons2(),
			'height' => 400,
			'editor_style' => '',
		);
		$saved = get_option($this->plugin_id);
		if ( !empty($saved) ) {
			foreach ($saved as $key => $val) $options[$key] = $val;
		}
		if ($saved != $options) update_option($this->plugin_id, $options);
	}
	
	function admin_menu(){
		register_setting($this->plugin_id.'_option', $this->plugin_id, array( &$this, 'admin_page_vailidate'));
		
		$page = is_multisite() ? 'options-general.php' : 'bp-general-settings';
		add_submenu_page( $page, 'U '.__('BuddyPress Forum Editor', $this->plugin_id), 'U '.__('Forum Editor', $this->plugin_id), 'manage_options', $this->plugin_id, array(&$this, 'admin_page') );
	}
	
	function admin_page(){
		$opts = get_option($this->plugin_id);
		$enable_checked = !empty($opts['enable']) ? "checked='checked'" : '';
		$enable_topic_checked = !empty($opts['enable_topic']) ? "checked='checked'" : '';
		$enable_reply_checked = !empty($opts['enable_reply']) ? "checked='checked'" : '';
		$form_validate_checked = !empty($opts['form_validate']) ? "checked='checked'" : '';
		$buttons1 = isset($opts['buttons1']) ? $opts['buttons1'] : '';
		$buttons2 = isset($opts['buttons2']) ? $opts['buttons2'] : '';
		$height = intval($opts['height']);
		$editor_style = $opts['editor_style'];
		?>
		
		<div class="wrap">
			<?php screen_icon("options-general"); ?>
			<h2>U <?php _e('BuddyPress Forum Editor', $this->plugin_id);?></h2>
			
			<p style="padding:10px; color:gray;">* <?php printf(__('This plugin is using %s as a WYSIWYG HTML editor.', $this->plugin_id), '<a href="http://tinymce.moxiecode.com/" target="_blank">TinyMCE</a>')?></p>
			
			<form action="options.php" method="post">
				<?php settings_fields($this->plugin_id.'_option'); ?>
				<table class="form-table">
				
				<tr>
					<th><?php _e('Enable', $this->plugin_id)?></th>
					<td>
						<label><input type="checkbox" name="<?php echo $this->plugin_id?>[enable]" id="enable_cb" value="1" <?php echo $enable_checked;?>> <?php _e('Enable', $this->plugin_id)?></label>
						<div id="enable_scope" style="padding-left:16px">
							<label><input type="checkbox" name="<?php echo $this->plugin_id?>[enable_topic]" value="1" <?php echo $enable_topic_checked;?>> <?php _e('Enable Topic editor', $this->plugin_id)?></label>
							<br><label><input type="checkbox" name="<?php echo $this->plugin_id?>[enable_reply]" value="1" <?php echo $enable_reply_checked;?>> <?php _e('Enable Reply editor', $this->plugin_id)?></label>
						</div>
						<p>&nbsp;</p>
						<script>
						jQuery('#enable_cb').filter(function(){
							jQuery(this).click(function(){
								var area = '#' + this.id.replace(/_enable/, '_area');
								if( this.checked ) jQuery('#enable_scope').slideDown('fast'); else jQuery('#enable_scope').hide();
							});
							if( ! this.checked ) jQuery('#enable_scope').hide();
						});
						</script> 
					</td>
				</tr>
				<tr>
					<th><?php _e('Form Validate', $this->plugin_id)?></th>
					<td>
						<label><input type="checkbox" name="<?php echo $this->plugin_id?>[form_validate]" value="1" <?php echo $form_validate_checked;?>> <?php _e('Enable', $this->plugin_id)?></label>
						<p class="description"><?php _e('Validating whether form fields are filled out before submission.', $this->plugin_id)?></p>
						
					</td>
				</tr>
				<tr>
					<th><?php _e('Primary Buttons group', $this->plugin_id)?></th>
					<td>
						<input type="text" name="<?php echo $this->plugin_id?>[buttons1]" value="<?php echo $buttons1;?>" class="widefat">
						<p class="description"><?php _e('Separate buttons with commas. Pipe character( | ) is visual separator.', $this->plugin_id)?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Secondary Buttons group', $this->plugin_id)?></th>
					<td>
						<input type="text" name="<?php echo $this->plugin_id?>[buttons2]" value="<?php echo $buttons2;?>" class="widefat">
						<p class="description"><?php _e('Separate buttons with commas. Pipe character( | ) is visual separator.', $this->plugin_id)?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Available button reference', $this->plugin_id)?></th>
					<td>
						<p><?php echo $this->get_default_buttons1();?></p>
						<p><?php echo $this->get_default_buttons2();?></p>
					</td>
				</tr>
				<tr>
					<th><?php _e('Editor Size', $this->plugin_id)?></th>
					<td>
						<?php _e('Height', $this->plugin_id)?>: <input type="text" name="<?php echo $this->plugin_id?>[height]" value="<?php echo $height;?>" size="3"> px
					</td>
				</tr>
				<tr>
					<th><?php _e('Editor Content CSS URL', $this->plugin_id)?></th>
					<td>
						<input type="text" name="<?php echo $this->plugin_id?>[editor_style]" value="<?php echo $editor_style;?>" class="widefat">
						<p class="description"><?php _e('If you\'d like to customize the Editor\'s content style, enter your own stylesheet file URL.', $this->plugin_id)?></p>
						<p class="description"><?php printf(__('If you leave a blank, the %s CSS will be used.', $this->plugin_id), '<a href="'.$this->plugin_url.'inc/content.css">'.__('defaults', $this->plugin_id).'</a>')?></p>
					</td>
				</tr>
				</table>
				
				<p class="submit">
					<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e(__('Save Changes', $this->plugin_id)); ?>" />
				</p>
			</form>
			
			<p style="padding:10px; font-weight: bold; color:gray">* <?php _e('Related Plugin', $this->plugin_id)?> : <a href="http://wordpress.org/extend/plugins/u-buddypress-forum-attachment/" target="_blank">U BuddyPress Forum Attachment</a></p>
		</div>
		<?php
	}
	
	function admin_page_vailidate($input){
		$input['buttons1'] = trim($input['buttons1']);
		$input['buttons2'] = trim($input['buttons2']);
		$input['editor_style'] = trim($input['editor_style']);
		$input['height'] = intval( preg_replace('/\D/', '', $input['height']) );
		$input['height'] = $input['height']>100 ? $input['height'] : 100;
		return $input;
	}
	
	function get_default_buttons1(){
		return 'fontselect, fontsizeselect, forecolor, backcolor, |, bold, italic, underline, strikethrough, |, justifyleft, justifycenter, justifyright, justifyfull, | ,sub, sup, |, removeformat';
	}
	
	function get_default_buttons2(){
		return 'undo, redo,|, pastetext, pasteword, |, bullist, numlist, |, outdent, indent, blockquote,|, link, unlink, hr, image, media, charmap, |, code, fullscreen';
	}
	
}
	
new UBPForumEditor;
	
