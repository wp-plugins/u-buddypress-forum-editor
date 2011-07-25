<?php
/*
Plugin Name: U BuddyPress Forum Editor
Plugin URI: http://urlless.com/buddypress-plugin-u-buddypress-forum-editor/
Description: This plugin is tinyMCE WYSIWYG HTML editor for BuddyPress Forum.
Author: Taehan Lee
Author URI: http://urlless.com
Version: 1.1.1
*/

class UBPForumEditor {
	
var $id = 'ubpfeditor';
var $ver = '1.1.1';
var $url;

function UBPForumEditor(){
	$this->url = plugin_dir_url(__FILE__);
	
	register_activation_hook( __FILE__, array(&$this, 'install') );
	register_uninstall_hook( __FILE__, array(&$this, 'uninstall') );
	
	load_plugin_textdomain($this->id, false, dirname(plugin_basename(__FILE__)).'/languages/');
	
	add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array(&$this, 'admin_menu') );
	add_action( 'admin_init', array(&$this, 'admin_init') );
	add_action( 'bp_init', array(&$this, 'bp_init') );
}

function bp_init(){
	global $bp;
	
	if ( ($bp->current_component=='groups' AND $bp->current_action == 'forum')
		|| ($bp->current_component=='forums' AND $bp->current_action == '') ){
		
		$opts = get_option($this->id);
	
		if( empty($opts['enable']) || (empty($opts['enable_topic']) AND empty($opts['enable_reply'])) || empty($opts['buttons1']) ) 
			return false;
		
		if( !empty($opts['form_validate']) ){
			wp_enqueue_script( $this->id.'-form-validate', $this->url.'inc/form-validate.js', array('jquery'), $this->ver);
			wp_localize_script( $this->id.'-form-validate', $this->id.'_form_validate_vars', array(
				'title_error' => __('Error: Please enter a title.', $this->id),
				'content_error' => __('Error: Please enter content.', $this->id),
				'group_id_error' => __('Error: Please select the Group Forum.', $this->id),
			));
		}
		
		remove_filter( 'bp_get_the_topic_latest_post_excerpt', 'bp_forums_filter_kses', 1 );
		remove_filter( 'bp_get_the_topic_post_content', 'bp_forums_filter_kses', 1 );
		add_action( 'wp_footer', array(&$this, 'the_editor'));
	}
}

function the_editor( ) {
	global $tinymce_version;
	$opts = get_option($this->id);
	$baseurl = includes_url('js/tinymce');
	$mce_locale = 'en';
	
	$editor_style = !empty($opts['editor_style']) ? $opts['editor_style'] : $this->url.'inc/content.css';
	
	$enable_textareas = array();
	if( !empty($opts['enable_topic']) ) array_push($enable_textareas, 'textarea[name=topic_text]');
	if( !empty($opts['enable_reply']) ) array_push($enable_textareas, 'textarea[name=reply_text]', 'textarea[name=post_text]');
	$enable_textareas = implode(',', $enable_textareas);
	
	$plugins = array( 'inlinepopups', 'paste', 'fullscreen' );
	$my_ext_plugins = preg_replace('/,\s*/',',',$opts['plugins']);
	$ext_plugins = '';
	if( !empty($my_ext_plugins) AND $opts['plugin_dir']){
		$my_ext_plugins = explode(',', $my_ext_plugins);
		$mce_external_plugins = array();
		foreach($my_ext_plugins as $my_ext_plugin){
			$mce_external_plugins[$my_ext_plugin] = WP_PLUGIN_URL.'/'.$opts['plugin_dir'].'/plugins/'.$my_ext_plugin.'/editor_plugin.js';
		}
		foreach ( $mce_external_plugins as $name => $url ) {
			if( $name=='media' ) continue;
			if ( is_ssl() ) $url = str_replace('http://', 'https://', $url);
			$plugins[] = '-' . $name;
			$plugurl = dirname($url);
			$strings = $str1 = $str2 = '';
			$path = str_replace( WP_PLUGIN_URL, '', $plugurl );
			$path = WP_PLUGIN_DIR . $path . '/langs/';

			if ( function_exists('realpath') )
				$path = trailingslashit( realpath($path) );

			if ( @is_file($path . $mce_locale . '.js') )
				$strings .= @file_get_contents($path . $mce_locale . '.js') . "\n";

			if ( @is_file($path . $mce_locale . '_dlg.js') )
				$strings .= @file_get_contents($path . $mce_locale . '_dlg.js') . "\n";

			if ( 'en' != $mce_locale && empty($strings) ) {
				if ( @is_file($path . 'en.js') ) {
					$str1 = @file_get_contents($path . 'en.js');
					$strings .= preg_replace( '/([\'"])en\./', '$1' . $mce_locale . '.', $str1, 1 ) . "\n";
				}

				if ( @is_file($path . 'en_dlg.js') ) {
					$str2 = @file_get_contents($path . 'en_dlg.js');
					$strings .= preg_replace( '/([\'"])en\./', '$1' . $mce_locale . '.', $str2, 1 ) . "\n";
				}
			}

			if ( ! empty($strings) )
				$ext_plugins .= "\n" . $strings . "\n";
		
			$ext_plugins .= 'tinyMCEPreInit.load_ext("' . $plugurl . '", "' . $mce_locale . '");' . "\n";
			$ext_plugins .= 'tinymce.PluginManager.load("' . $name . '", "' . $url . '");' . "\n";
		}
	}
	
	$allowed_tags_array = array();
	$allowed_tags = $this->allowed_tags();
	foreach( $allowed_tags as $k=>$v){
		$attr = '';
		if( !empty($v) ) {
			$attr = '['.join('|', array_keys($v)).']';
		}
		$allowed_tags_array[] = $k.$attr;
	}
	$allowed_tags = join(',', $allowed_tags_array);
	
	
	$initArray = array (
		'mode' => 'specific_textareas',
		'editor_selector' => 'theEditor',
		'width' => $opts['width'] ? $opts['width'] : '100%',
		'height' => $opts['height'] ? $opts['height'] : 400,
		'theme' => 'advanced',
		'skin' => $opts['skin'] ? $opts['skin'] : 'default',
		'theme_advanced_buttons1' => $opts['buttons1'],
		'theme_advanced_buttons2' => $opts['buttons2'],
		'theme_advanced_buttons3' => '',
		'theme_advanced_buttons4' => '',
		'language' => $mce_locale,
		'plugins' => implode( ',', $plugins ),
		'content_css' => $editor_style,
		'valid_elements' => $allowed_tags,
		'invalid_elements' => 'script,style,link',
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
jQuery('<?php echo $enable_textareas?>').addClass('theEditor');
tinyMCEPreInit = { base : "<?php echo $baseurl; ?>", suffix : "", query : "<?php echo $version; ?>", mceInit : {<?php echo $mce_options; ?>}, load_ext : function(url,lang){ var sl=tinymce.ScriptLoader; sl.markDone(url+'/langs/'+lang+'.js'); sl.markDone(url+'/langs/'+lang+'_dlg.js');} }; (function(){ var t=tinyMCEPreInit, sl=tinymce.ScriptLoader, ln=t.mceInit.language, th=t.mceInit.theme, pl=t.mceInit.plugins; sl.markDone(t.base+'/langs/'+ln+'.js'); sl.markDone(t.base+'/themes/'+th+'/langs/'+ln+'.js'); sl.markDone(t.base+'/themes/'+th+'/langs/'+ln+'_dlg.js'); })(); 
<?php if ( $ext_plugins ) echo "$ext_plugins\n"; ?>
tinyMCE.init(tinyMCEPreInit.mceInit);

<?php if( !empty($opts['form_validate']) ) echo "bp_forum_form_validate.init();\n"?>
</script>

<style>
.mceEditor { margin-bottom: 20px; display:block; } 
.mceTop { background: #666 !important; }
.mceEditor a { color: #999; }
.wp_themeSkin .mceLayout { border: 1px solid #bbb !important;}
.wp_themeSkin .mceToolbar,
.wp_themeSkin .mceStatusbar {background: #dfdfdf url(<?php echo admin_url('images/gray-grad.png')?>) repeat-x; }
.wp_themeSkin .mceIframeContainer,
.wp_themeSkin .mceStatusbar { border-top: 1px solid #bbb !important; }
.wp_themeSkin .mceIframeContainer,
.wp_themeSkin .mceMenuItem,
.wp_themeSkin .mceColorSplitMenu { background: white; }
.wp_themeSkin .mceMenuItem a,
.wp_themeSkin .mceColorSplitMenu a { color: #666;}
.wp_themeSkin .mceMenuItem a:hover,
.wp_themeSkin .mceColorSplitMenu a:hover { color: #000;}
#ubpfeditor-error {
	<?php if($opts['width']) echo 'width:'.(intval($opts['width'])-30).'px;'?> 
	background: #FFEBE8; border: 1px solid #C66; color: #AA0000; padding: 6px 15px; margin: 20px 0; border-radius: 3px; display: none; 
}
</style>
<?php
}


function allowed_tags(){
	require_once plugin_dir_path(__FILE__).'inc/allowed-tags.php';
	
	$r = array();
	$opts = get_option($this->id);
	
	if( empty($opts['allowed_tags']) ){
		foreach($default_allowedtags as $tag)
			$r[$tag] = $full_allowedtags[$tag];
	}else{
		foreach($opts['allowed_tags'] as $tag)
			$r[$tag] = $full_allowedtags[$tag];
	}
	return $r;
}





/* Back-end
--------------------------------------------------------------------------------------- */

function install() {
	global $wp_version;
	if (version_compare($wp_version, "3.1", "<")) 
		wp_die("This plugin requires WordPress version 3.1 or higher.");
	
	require_once plugin_dir_path(__FILE__).'inc/allowed-tags.php';
	
	$options = array (
		'enable' => '',
		'enable_topic' => '1',
		'enable_reply' => '1',
		'form_validate' => '1',
		'buttons1' => $this->get_default_buttons1(),
		'buttons2' => $this->get_default_buttons2(),
		'plugins' => '', 
		'plugin_dir' => '',
		'width' => 634,
		'height' => 400,
		'editor_style' => '',
		'allowed_tags' => $default_allowedtags,
	);
	
	$saved = get_option($this->id);
	
	if ( !empty($saved) ) {
		foreach ($saved as $key=>$val) 
			$options[$key] = $val;
	}
		
	if ($saved != $options) 
		update_option($this->id, $options);
}

function uninstall(){
	delete_option($this->id);
}

function admin_init(){
	register_setting($this->id.'_options', $this->id, array( &$this, 'admin_page_vailidate'));
}

function admin_menu(){
	if( !is_super_admin() ) 
		return false;
	
	add_submenu_page( 
		'bp-general-settings', 
		'U '.__('BuddyPress Forum Editor', $this->id), 
		'U '.__('Forum Editor', $this->id),
		'manage_options', 
		$this->id, 
		array(&$this, 'admin_page') 
	);
}

function admin_page(){
	$opts = (object) get_option($this->id);
	require_once plugin_dir_path(__FILE__).'inc/allowed-tags.php';
	if( empty($opts->allowed_tags) ) $opts->allowed_tags = $default_allowedtags;
	$skins = array('default', 'highcontrast', 'o2k7', 'wp_theme');
	?>
	
	<div class="wrap">
		<?php screen_icon("options-general"); ?>
		<h2>U <?php _e('BuddyPress Forum Editor', $this->id);?></h2>
		
		<?php settings_errors( $this->id ) ?>
		
		<p style="padding:10px; color:gray;">* <?php printf(__('This plugin is using %s as a WYSIWYG HTML editor.', $this->id), '<a href="http://tinymce.moxiecode.com/" target="_blank">TinyMCE</a>')?></p>
		
		<form action="<?php echo admin_url('options.php')?>" method="post">
			<?php settings_fields($this->id.'_options'); ?>
			<table class="form-table">
			
			<tr>
				<th><?php _e('Enable', $this->id)?></th>
				<td>
					<label><input type="checkbox" name="<?php echo $this->id?>[enable]" id="enable_cb" value="1" <?php checked($opts->enable, '1')?>> <?php _e('Enable', $this->id)?></label>
					<div id="enable_scope" style="padding-left:16px">
						<label><input type="checkbox" name="<?php echo $this->id?>[enable_topic]" value="1" <?php checked($opts->enable_topic, '1')?>> <?php _e('Enable Topic editor', $this->id)?></label><br>
						<label><input type="checkbox" name="<?php echo $this->id?>[enable_reply]" value="1" <?php checked($opts->enable_reply, '1')?>> <?php _e('Enable Reply editor', $this->id)?></label>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php _e('Editor Size', $this->id)?> *</th>
				<td>
					<?php _e('Width', $this->id)?> :
					<input type="text" name="<?php echo $this->id?>[width]" value="<?php echo $opts->width;?>" size="3"> px
					<br>
					<?php _e('Height', $this->id)?> :
					<input type="text" name="<?php echo $this->id?>[height]" value="<?php echo $opts->height;?>" size="3"> px
				</td>
			</tr>
			<tr>
				<th><?php _e('Editor Skin', $this->id)?> *</th>
				<td>
					<select name="<?php echo $this->id?>[skin]">
						<?php foreach($skins as $skin){ ?>
						<option value="<?php echo $skin?>" <?php selected($opts->skin, $skin)?>><?php echo $skin?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php _e('Form Validate', $this->id)?></th>
				<td>
					<label><input type="checkbox" name="<?php echo $this->id?>[form_validate]" value="1" <?php checked($opts->form_validate, '1')?>> <?php _e('Enable', $this->id)?></label>
					<p class="description"><?php _e('Validating whether form fields are filled out before submit post.', $this->id)?></p>
					
				</td>
			</tr>
			<tr>
				<th><?php _e('Primary Buttons group', $this->id)?> *</th>
				<td>
					<input type="text" name="<?php echo $this->id?>[buttons1]" value="<?php echo $opts->buttons1;?>" class="widefat">
					<p class="description"><?php _e('Separate buttons with commas. Pipe character( | ) is visual separator.', $this->id)?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Secondary Buttons group', $this->id)?></th>
				<td>
					<input type="text" name="<?php echo $this->id?>[buttons2]" value="<?php echo $opts->buttons2;?>" class="widefat">
					<p class="description"><?php _e('Separate buttons with commas. Pipe character( | ) is visual separator.', $this->id)?></p>
					<br>
					<p><strong><?php _e('Available button reference', $this->id)?> :</strong></p>
					<p><code><?php echo $this->get_default_buttons1();?></code></p>
					<p><code><?php echo $this->get_default_buttons2();?></code></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Allowed Tags', $this->id)?></th>
				<td>
					<p><strong><?php _e('Default allowed tags', $this->id)?> :</strong></p>
					<p><?php $i=0; foreach($full_allowedtags as $k=>$v){ 
						if(in_array($k, $default_allowedtags)){ ?>
						<label><input type="checkbox" name="<?php echo $this->id?>[allowed_tags][]" value="<?php echo $k?>" <?php checked(in_array($k, $opts->allowed_tags))?>> <?php echo $k?></label> &nbsp;
						<?php if($i++%10==9) echo '<br>';?>
					<?php }} ?>
					</p>
					
					<p><strong><?php _e('Additional tags', $this->id)?> :</strong></p>
					<p>
					<?php $i=0; foreach($full_allowedtags as $k=>$v){ 
						if(!in_array($k, $default_allowedtags)){ ?>
						<label style="white-space:nowrap;"><input type="checkbox" name="<?php echo $this->id?>[allowed_tags][]" value="<?php echo $k?>" <?php checked(in_array($k, $opts->allowed_tags))?>> <?php echo $k?></label> &nbsp;
						<?php if($i++%10==9) echo '<br>';?>
					<?php }} ?>
					</p>
					<p class="description"><?php _e('For instance, if you would embed Youtube, select <code>iframe</code>. and if you would use old embed code(Flash), select <code>object, embed and param</code>.', $this->id)?></p>
					<p class="description"><?php _e('Some tags are never allowed. script, style, link.', $this->id)?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Editor Content CSS URL', $this->id)?></th>
				<td>
					<input type="text" name="<?php echo $this->id?>[editor_style]" value="<?php echo $editor_style;?>" class="widefat">
					<p class="description"><?php _e('If you\'d like to customize the Editor\'s content style, enter your own stylesheet file URL.', $this->id)?></p>
					<p class="description"><?php printf(__('If you leave a blank, the %s CSS will be used.', $this->id), '<a href="'.$this->url.'inc/content.css">'.__('defaults', $this->id).'</a>')?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e('Extend TinyMCE Plugin', $this->id)?></th>
				<td>
					<p style="font-size:14px;"><strong><?php _e('How to extend TinyMCE plugin', $this->id)?></strong></p>
					<p style="color:red"><?php _e('This is not required option.', $this->id)?></p>
					<p><strong><?php _e('Plugin directory', $this->id)?> :</strong>
					<?php echo WP_PLUGIN_URL?>/
					<input type="text" name="<?php echo $this->id?>[plugin_dir]" value="<?php echo $opts->plugin_dir;?>" ></p>
					
					<p><strong><?php _e('Plugin names', $this->id)?> :</strong>
					<input type="text" name="<?php echo $this->id?>[plugins]" value="<?php echo $opts->plugins;?>" class="regular-text">
					<span class="description"><?php _e('Separate plugin name with commas.', $this->id)?></span></p>
					
					<ol style="color:#666">
					<li>Download <a href="http://tinymce.moxiecode.com/download/download.php" target="_blank">tinyMCE</a>.</li>
					<li>Upload your downloaded folder(<code>your-download/jscript/<strong>tiny_mce</strong></code>) to WordPress' plugin folder(<code><?php echo WP_PLUGIN_URL?></code>). you can rename this folder.</li>
					<li>Fill the '<?php _e('Plugin directory', $this->id)?>' with the uploaded folder name</li>
					<li>Fill the '<?php _e('Plugin names', $this->id)?>' with tinyMCE plugin names you want.</li>
					<li>Add buttons to the '<?php _e('Primary Buttons group', $this->id)?>' or '<?php _e('Secondary Buttons group', $this->id)?>'. Button name is usually same with plugin name.</li>
					<li>* 'media' is currently disabled, which has some errors I couldn't fix. if you would embed something like Flash, use the HTML dialog.</li>
					</ol>
					<p><a href="http://urlless.com/extending-tinymce-plugin-for-u-buddypress-forum-editor/" target="_blank"><?php _e('View more explaination.', $this->id)?></a></p>
				</td>
			</tr>
			
			</table>
			
			<p class="submit">
				<input name="submit" type="submit" class="button-primary" value="<?php esc_attr_e(__('Save Changes'))?>" />
			</p>
		</form>
		
		<p><strong><?php _e('Related Plugin', $this->id)?></strong> : <a href="http://wordpress.org/extend/plugins/u-buddypress-forum-attachment/" target="_blank">U BuddyPress Forum Attachment</a></p>
	</div>
	
	<script>
	jQuery('#enable_cb').filter(function(){
		jQuery(this).click(function(){
			var area = '#' + this.id.replace(/_enable/, '_area');
			if( this.checked ) jQuery('#enable_scope').slideDown('fast'); else jQuery('#enable_scope').hide();
		});
		if( ! this.checked ) jQuery('#enable_scope').hide();
	});
	</script> 
	<?php
}

function admin_page_vailidate($input){
	$r = array();
	$r['enable'] = $input['enable'];
	$r['enable_topic'] = $input['enable_topic'];
	$r['enable_reply'] = $input['enable_reply'];
	$r['form_validate'] = $input['form_validate'];
	$r['width'] = absint($input['width']);
	$r['height'] = absint($input['height']);
	$r['skin'] = $input['skin'];
	$r['buttons1'] = $input['buttons1'];
	$r['buttons2'] = $input['buttons2'];
	$r['plugins'] = $input['plugins'];
	$r['plugin_dir'] = untrailingslashit($input['plugin_dir']);
	$r['allowed_tags'] = $input['allowed_tags'];
	$r['editor_style'] = absint($input['editor_style']);
	
	if( !$r['buttons1'] || !$r['width'] || !$r['height'] ){
		add_settings_error($this->id, 'settings_error', __('Error: please fill the required fields.', $this->id), 'error');
		$r = get_option($this->id);
	} else {
		add_settings_error($this->id, 'settings_updated', __('Settings saved.'), 'updated');
	}
	return $r;
}

function get_default_buttons1(){
	return 'fontselect, fontsizeselect, forecolor, backcolor, |, bold, italic, underline, strikethrough, |, justifyleft, justifycenter, justifyright, justifyfull, | ,sub, sup, |, removeformat';
}

function get_default_buttons2(){
	return 'undo, redo,|, pastetext, pasteword, |, bullist, numlist, |, outdent, indent, blockquote,|, link, unlink, hr, image, charmap, |, code, fullscreen';
}

}

$ubpfeditor = new UBPForumEditor;

