<?php
/*
Plugin Name: Picasa Image Express
Plugin URI: http://psytoy.net/picasa-image-express
Description: Browse, search and select photos from any publicly available Picasa Web Album and add them to your post/pages.
Version: 1.1
Author: Scrawl
Author URI: http://psytoy.net

Copyright 2008 Scrawl  (scrawl@psytoy.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Plugin directory URI
$pluginURI = get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__));

// -----------------------------------------------
// Define hooks and actions
// -----------------------------------------------

// Hook for adding admin menus
add_action('admin_menu', 'pie_add_settings_page');

// Hook for plugin activation
register_activation_hook( __FILE__, 'pie_init_options' );

// Hook for media button - late enough so that add_meta_box() is defined
if (is_admin()){
	add_action('media_buttons', 'pie_addMediaButton', 20);
}

// Attach stylesheet
add_action('wp_head','pie_addStyleSheet');

// -----------------------------------------------
// Define plugin functions
// -----------------------------------------------

// Add media button to editor
function pie_addMediaButton() {

	global $pluginURI;
	
	$media_picasa_iframe_src = "$pluginURI/album_viewer.php?";
	
	// Retrieve the settings from the database and append to popup window URL before thick box values
	$opt = pie_init_options();
	foreach ($opt as $key => $value){
		$media_picasa_iframe_src .= "&amp;".$key."=".$value;
	}
	
	$media_picasa_iframe_src .= "&TB_iframe=true&amp;height=500&amp;width=750";
	$media_picasa_title = "Add Picasa image";
	
	echo "<a href=\"$media_picasa_iframe_src\" class=\"thickbox\" title=\"$media_picasa_title\"><img src=\"$pluginURI/icon_picasa{$opt["pie_icon"]}.gif\" alt=\"$media_picasa_title\" /></a>";
}

// Attach stylesheet
function pie_addStyleSheet() {

	global $pluginURI;
	
	// Add stylesheet
	echo "<link href=\"$pluginURI/picasa-image-express.css\" rel=\"stylesheet\" type=\"text/css\" />\n";
	
	// Add IE inline-block hack 
	echo "<!--[if lt ie 8]>\n<link href=\"$pluginURI/picasa-image-express-IE.css\" rel=\"stylesheet\" type=\"text/css\" />\n<![endif]-->\n";
}

// Add a page which will hold the  Options form
function pie_add_settings_page() {
    //add_options_page('Test Options', 'Test Options', 8, 'testoptions', 'mt_options_page');
	add_options_page('Picasa Image Express', 'Picasa Image Express', 8, 'picasaImageExpress', 'pie_settings_form');
}

// Retrieve plugin options from database or set defaults if they don't exist
function pie_init_options(){

	//Define default values
	$pieOptions = array(
		"pie_user_name" => "psytoy78",
		"pie_link_thumbnails" => "true",
		"pie_thumb_size" => 160,
		"pie_square_thumbnails" => "true",
		"pie_image_size" => 640,
		"pie_link_option" => "picasa",
		"pie_captions" => "false",
		"pie_thumbnail_css" => "none",
		"pie_gallery_css" => "alignGalleryLeft",
		"pie_margin_top" => 10,
		"pie_margin_right" => 10,
		"pie_margin_bottom" => 10,
		"pie_margin_left" => 10,
		"pie_configured" => true,
		"pie_icon" => 1);
		
	// Apply defaults if not found
	if(!get_option("pie_configured")){
		foreach ($pieOptions as $key => $option){
			update_option($key,$option);
		}
	// Otherwise over-ride defaults with options stored in database
	} else {
		foreach ($pieOptions as $key => $option){
			$pieOptions[$key] = get_option($key);
		}
	}
	return $pieOptions;
}

// Settings page form
function pie_settings_form(){
	global $pluginURI;
?>
	<div class="wrap">
		<?php 
			// Retrieve the settings from the database
			$opt = pie_init_options();
			
			// debug assistant
			/*foreach ($opt as $key => $option){
				echo $key . "=" . $option . "<br/>";
			}*/
		?>

		<form method="post" action="options.php">
			<!-- Begin PIE form contents -->
			<h3>Hints and tips</h3>
			<ul>
				<li>All of the settings below are also available when selecting photos to insert.</li>
				<li>The only item you need to setup on this page is the Picasa User Name. Everything else is optional.</li>
				<li>To insert an image gallery, select multiple images using CTRL-Click and SHIFT-Click </li>
				<li>The order you select images will determine the order they appear on your page or post</li>
			</ul>
			<h3>Account setup</h3>
			<table class="form-table">
				<tr>
					<th scope="row">Picasa User (<span style="color:red;">required</span>)</th>
					<td><input type="text" name="pie_user_name" size="40" value="<?php echo $opt['pie_user_name']; ?>" />
						<br />
						Your Picasa web albums user name. Eg: <a href="http://picasaweb.google.com/psytoy78" target="_blank">http://picasaweb.google.com/<strong>psytoy78</strong></a></td>
				</tr>
			</table>
			<h3>Image setup</h3>
			<table class="form-table">
				<tr>
					<th scope="row">Link to larger photo?</th>
					<td><label>
						<input type="radio" name="pie_link_thumbnails" value="true" <?php if ($opt["pie_link_thumbnails"] == "true") { _e('checked="checked"'); } ?>  />
						Yes</label>
						<label>
						<input type="radio" name="pie_link_thumbnails" value="false" <?php if ($opt["pie_link_thumbnails"] == "false") { _e('checked="checked"'); } ?> />
						No</label>
						<br />
						Select 'yes' to enable clicking on a photo to view a larger version</td>
				</tr>
				<tr>
					<th scope="row">Thumbnail size</th>
					<td><select name="pie_thumb_size">
							<option <?php if ($opt["pie_thumb_size"] == 32) { _e('selected="selected"'); } ?> >32</option>
							<option <?php if ($opt["pie_thumb_size"] == 48) { _e('selected="selected"'); } ?> >48</option>
							<option <?php if ($opt["pie_thumb_size"] == 64) { _e('selected="selected"'); } ?> >64</option>
							<option <?php if ($opt["pie_thumb_size"] == 72) { _e('selected="selected"'); } ?> >72</option>
							<option <?php if ($opt["pie_thumb_size"] == 144) { _e('selected="selected"'); } ?> >144</option>
							<option <?php if ($opt["pie_thumb_size"] == 160) { _e('selected="selected"'); } ?> >160</option>
							<option <?php if ($opt["pie_thumb_size"] == 200) { _e('selected="selected"'); } ?> >200</option>
							<option <?php if ($opt["pie_thumb_size"] == 288) { _e('selected="selected"'); } ?> >288</option>
							<option <?php if ($opt["pie_thumb_size"] == 320) { _e('selected="selected"'); } ?> >320</option>
							<option <?php if ($opt["pie_thumb_size"] == 400) { _e('selected="selected"'); } ?> >400</option>
							<option <?php if ($opt["pie_thumb_size"] == 512) { _e('selected="selected"'); } ?> >512</option>
							<option <?php if ($opt["pie_thumb_size"] == 576) { _e('selected="selected"'); } ?> >576</option>
							<option <?php if ($opt["pie_thumb_size"] == 640) { _e('selected="selected"'); } ?> >640</option>
							<option <?php if ($opt["pie_thumb_size"] == 720) { _e('selected="selected"'); } ?> >720</option>
							<option <?php if ($opt["pie_thumb_size"] == 800) { _e('selected="selected"'); } ?> >800</option>
						</select>
						<br />
						The size of the photo to appear in your post/page. The longest side (ie width or height) will be this size</td>
				</tr>
				<tr>
					<th scope="row">Crop 1:1?</th>
					<td><label>
							<input type="radio" name="pie_square_thumbnails" value="true" <?php if ($opt["pie_square_thumbnails"] == "true") { _e('checked="checked"'); } ?> />
							Yes
						</label>
						<label>
							<input type="radio" name="pie_square_thumbnails" value="false" <?php if ($opt["pie_square_thumbnails"] == "false") { _e('checked="checked"'); } ?> />
							No
						</label><br />
						This will crop photos so that they appear square. Useful when displaying a gallery of images so they are all the same size.<br />
						If set to 'yes', <strong>please ensure Thumbnail size is no larger than 160</strong></label></td>
				</tr>
				<tr>
					<th scope="row">Display captions?</th>
					<td><label><input type="radio" name="pie_captions" value="true" <?php if ($opt["pie_captions"] == "true") { _e('checked="checked"'); } ?> />
						Yes
						</label>
						<label>
						<input type="radio" name="pie_captions" value="false" <?php if ($opt["pie_captions"] == "false") { _e('checked="checked"'); } ?> />
						No</label>
						<br />
						Display a caption (if available) underneath the photo.
						<br/>Add or edit your captions by signing in to your <a href="http://picasaweb.google.com" target="_blank">Picasa Web Album account</a></td>
				</tr>
				<tr>
					<th scope="row">Large image size</th>
					<td><select name="pie_image_size" >
							<option <?php if ($opt["pie_image_size"] == 32) { _e('selected="selected"'); } ?> >32</option>
							<option <?php if ($opt["pie_image_size"] == 48) { _e('selected="selected"'); } ?> >48</option>
							<option <?php if ($opt["pie_image_size"] == 64) { _e('selected="selected"'); } ?> >64</option>
							<option <?php if ($opt["pie_image_size"] == 72) { _e('selected="selected"'); } ?> >72</option>
							<option <?php if ($opt["pie_image_size"] == 144) { _e('selected="selected"'); } ?> >144</option>
							<option <?php if ($opt["pie_image_size"] == 160) { _e('selected="selected"'); } ?> >160</option>
							<option <?php if ($opt["pie_image_size"] == 200) { _e('selected="selected"'); } ?> >200</option>
							<option <?php if ($opt["pie_thumb_size"] == 288) { _e('selected="selected"'); } ?> >288</option>
							<option <?php if ($opt["pie_thumb_size"] == 320) { _e('selected="selected"'); } ?> >320</option>
							<option <?php if ($opt["pie_thumb_size"] == 400) { _e('selected="selected"'); } ?> >400</option>
							<option <?php if ($opt["pie_image_size"] == 512) { _e('selected="selected"'); } ?> >512</option>
							<option <?php if ($opt["pie_image_size"] == 576) { _e('selected="selected"'); } ?> >576</option>
							<option <?php if ($opt["pie_image_size"] == 640) { _e('selected="selected"'); } ?> >640</option>
							<option <?php if ($opt["pie_image_size"] == 720) { _e('selected="selected"'); } ?> >720</option>
							<option <?php if ($opt["pie_image_size"] == 800) { _e('selected="selected"'); } ?> >800</option>
						</select>
						<br />
						The  maximum size of a photo that is linked from a thumbnail. The longest side (ie width or height) will be this size</td>
				</tr>
				<tr>
					<th scope="row">Open as</th>
					<td><label><input type="radio" name="pie_link_option" id="direct" value="direct" <?php if ($opt["pie_link_option"] == "direct") { _e('checked="checked"'); } ?> />Direct link</label>
						<label><input type="radio" name="pie_link_option" id="picasa" value="picasa" <?php if ($opt["pie_link_option"] == "picasa") { _e('checked="checked"'); } ?> />Picasa</label>
						<label><input type="radio" name="pie_link_option" id="lightbox" value="lightbox" <?php if ($opt["pie_link_option"] == "lightbox") { _e('checked="checked"'); } ?> />Lightbox</label>
						<label><input type="radio" name="pie_link_option" id="thickbox" value="thickbox" <?php if ($opt["pie_link_option"] == "thickbox") { _e('checked="checked"'); } ?> />Thickbox</label>
						<br />
						Direct link - link directly to photo<br />
						Picasa - link to  Picasa Web Album<br />
						Lightbox - link to photo and add support for a third-party <a href="http://wordpress.org/extend/plugins/slimbox-plugin/" title="My personal favourite" target="_blank">Lightbox</a> plugin<br />
						Thickbox - link to photo and add support for a third-party Thickbox plugin</label></td>
				</tr>
			</table>
			<h3>Styles and formatting</h3>
			<table class="form-table">
				<tr>
					<th scope="row">Alignment of a single image</th>
					<td><select name="pie_thumbnail_css">
							<option <?php if ($opt["pie_thumbnail_css"] == "none") { _e('selected="selected"'); } ?> value="none" >none</option>
							<option <?php if ($opt["pie_thumbnail_css"] == "alignleft") { _e('selected="selected"'); } ?> value="alignleft" >left</option>
							<option <?php if ($opt["pie_thumbnail_css"] == "alignright") { _e('selected="selected"'); } ?> value="alignright" >right</option>
						</select>
						<br />
						Alignment of a single image relative to text.</td>
				</tr>
				<tr>
					<th scope="row">Alignment of a gallery of images</th>
					<td><select name="pie_gallery_css">
							<option <?php if ($opt["pie_gallery_css"] == "none") { _e('selected="selected"'); } ?> >none</option>
							<option <?php if ($opt["pie_gallery_css"] == "alignGalleryLeft") { _e('selected="selected"'); } ?> value="alignGalleryLeft"  >left</option>
							<option <?php if ($opt["pie_gallery_css"] == "alignGalleryRight") { _e('selected="selected"'); } ?> value="alignGalleryRight" >right</option>
							<option <?php if ($opt["pie_gallery_css"] == "alignGalleryCenter") { _e('selected="selected"'); } ?> value="alignGalleryCenter" >center</option>
						</select>
						<br />
						Alignment of images in a gallery relative to each other.</td>
				</tr>
				<tr>
					<th scope="row">Margins</th>
					<td><label>Top:
							<input name="pie_margin_top" type="text" size="5" value="<?php echo $opt['pie_margin_top']; ?>" />
						</label>
						<label>Right:
							<input name="pie_margin_right" type="text" size="5" value="<?php echo $opt['pie_margin_right']; ?>" />
						</label>
						<label>Bottom:
							<input name="pie_margin_bottom" type="text" size="5" value="<?php echo $opt['pie_margin_bottom']; ?>" />
						</label>
						<label>Left:
							<input name="pie_margin_left" type="text" size="5" value="<?php echo $opt['pie_margin_left']; ?>" />
						</label>
						<br/>
						Amount of space surrounding an image
					</td>
				</tr>
			</table>		
			<h3>Misc settings</h3>
			<table class="form-table">
				<tr>
					<th scope="row">Insert Picasa icon</th>
					<td>
						<label>
						<input type="radio" name="pie_icon" value="1" <?php if ($opt["pie_icon"] == "1") { _e('checked="checked"'); } ?>  />
						<img src="<?php echo $pluginURI; ?>/icon_picasa1.gif" alt="Option 1" title="Option 1"/></label>
						<label>
						<input type="radio" name="pie_icon" value="2" <?php if ($opt["pie_icon"] == "2") { _e('checked="checked"'); } ?> />
						<img src="<?php echo $pluginURI; ?>/icon_picasa2.gif" alt="Option 2" title="Option 2"/></label>
						<label>
						<input type="radio" name="pie_icon" value="3" <?php if ($opt["pie_icon"] == "3") { _e('checked="checked"'); } ?> />
						<img src="<?php echo $pluginURI; ?>/icon_picasa3.gif" alt="Option 3" title="Option 3"/></label>
					</td>
				</tr>
				<tr>
					<th scope="row">Change all settings back to defaults</th>
					<td>
						<label>
						<input type="radio" name="pie_configured" value="0" <?php if (!$opt["pie_configured"]) { _e('checked="checked"'); } ?>  />
						Yes</label>
						<label>
						<input type="radio" name="pie_configured" value="1" <?php if ($opt["pie_configured"]) { _e('checked="checked"'); } ?> />
						No</label>
					</td>
				</tr>
			</table>
			<!-- End PIE form contents -->
			
			<!-- Begin Wordpress required fields -->
			<?php wp_nonce_field('update-options'); ?>
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="pie_user_name,pie_link_thumbnails,pie_thumb_size,pie_square_thumbnails,pie_image_size,pie_link_option,pie_captions,pie_thumbnail_css,pie_gallery_css,pie_margin_top,pie_margin_right,pie_margin_bottom,pie_margin_left,pie_configured,pie_icon" />
			<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
			</p>
			<!-- End Wordpress required form elements -->
		</form>
	</div>
<?php
}
?>