<?php
/*
Plugin Name: GT Press
Plugin URI: http://www.CustomDesignSite.net/plugins/GT-Press/
Description: GT Press allows you to easily customize the look and feel of the back-end Admin menus and create a 'White Label' CMS Experience.
Version: 1.0
Author: Billy Bryant
Author URI: http://www.CustomDesignSite.net/
*/
/*	Copyright 2011 Billy Bryant (email: billy.bryant@glowtouch.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
	
    http://www.gnu.org/licenses/gpl.txt
*/
/* Release History :
 * 1.0:		Initial Release
*/
//////////////////////////////////////////////////////////////////

///////////////////// ADD ADMIN OPTIONS PAGE ////////////////////

function gtpress_admin_menu() {
	$gtpress_ico = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/';
	add_options_page('GT Press', 'GT Press Setup', 8, basename(__FILE__), 'gtpressMenu_options_page', '', $gtpress_ico.'gtpress.png');
}
add_action('admin_menu', 'gtpress_admin_menu');

/////////////////////////////////////////////////////////////////

///////////////////// INITIALIZE PLUGIN //////////////////////////

function GTPress_preInit(){
	global $wp_version;
	if ( version_compare($wp_version, '3.1', '<') ) {
		deactivate_plugins( basename(__FILE__) );
		wp_die("Sorry, this plugin requires WordPress 3.1 or greater!<br>Please update your WordPress install before attempting to re-activate!");
	}
	if ( is_plugin_active('./ozh-admin-drop-down-menu/wp_ozh_adminmenu.php') ) {
		deactivate_plugins( basename(__FILE__) );
		wp_die("Sorry, the plugin <a href=\"http://wordpress.org/extend/plugins/ozh-admin-drop-down-menu/\" alt=\"Ozh' Admin Drop Down Menu\">Ozh' Admin Drop Down Menu</a> is required!<br>Please install this plugin before attempting to re-activate!");
	}

}
register_activation_hook(__FILE__, 'GTPress_preInit');

if (isset($_POST['update_gtpressMenu_options']))
	update_gtpressMenu_options();

add_action('admin_menu', 'gtpressMenu_init');
add_action('admin_menu', 'gtpressMenu_disable_menus');
add_action('admin_head', 'gtpressMenu_disable_metas');

register_activation_hook(basename(__FILE__), 'set_gtpressMenu_options');
register_deactivation_hook(basename(__FILE__), 'unset_gtpressMenu_options');

wp_enqueue_script('jquery');


function GTPress_init(){
	if(!get_option("GTPress")){
		# Load default options
		global $menu, $submenu;
		$options["head_btmcolor"] = "#000000";
		$options["head_topcolor"] = "#888888";
		$options["hide_themes"] = 'false';
		$options["hide_postmeta"] = 'false';
		$options["hide_pagemeta"] = 'false';
		update_option("GTPress", $options);
	}
}
register_activation_hook( __FILE__, 'GTPress_init' );

$GTPress_options = get_option("GTPress");
function gtpressMenu_init() {


	// save default menu and submenu, otherwise it will be permanently hidden
	global $menu, $submenu;
	update_option('gtpressMenu_default_menu', $menu);
	update_option('gtpressMenu_default_submenu', $submenu);

	$disabled_menu_items = get_option('gtpressMenu_disabled_menu_items');
	if ($disabled_menu_items === false) {
		add_option('gtpressMenu_disabled_menu_items');
		update_option('gtpressMenu_disabled_menu_items', array());
	} else if (is_string($disabled_menu_items) && is_array(unserialize($disabled_menu_items)))
		update_option('gtpressMenu_disabled_menu_items', unserialize($disabled_menu_items));

	$disabled_submenu_items = get_option('gtpressMenu_disabled_submenu_items');
	if ($disabled_submenu_items === false) {
		add_option('gtpressMenu_disabled_submenu_items');
		update_option('gtpressMenu_disabled_submenu_items', array());
	} else if (is_string($disabled_submenu_items) && is_array(unserialize($disabled_submenu_items)))
		update_option('gtpressMenu_disabled_submenu_items', unserialize($disabled_submenu_items));

	$disabled_metas = get_option('gtpressMenu_disabled_metas');
	if ($disabled_metas === false) {
		add_option('gtpressMenu_disabled_metas');
		update_option('gtpressMenu_disabled_metas', array());
	} else if (is_string($disabled_metas) && is_array(unserialize($disabled_metas)))
		update_option('gtpressMenu_disabled_metas', unserialize($disabled_metas));
}

/////////////////////////////////////////////////////////////////

///////////////////// OVERRIDE ADMIN CSS ////////////////////////

function gtpress_overrideCSS(){
	$GTTabs_options=get_option("GTTabs");
	?>
	<style type="text/css">
	<?php require_once("GTPress_css.php"); ?>
	</style>
	<?php

}
add_action('admin_head','gtpress_overrideCSS', 1000);

/////////////////////////////////////////////////////////////////

///////////////////// HIDE THEME SELECTION //////////////////////

function GTPress_hide_themes()
{
	$disabled_menu_items = get_option('gtpressMenu_disabled_menu_items');
	$disabled_submenu_items = get_option('gtpressMenu_disabled_submenu_items');
	if (in_array('themes.php', $disabled_menu_items)) {
		add_menu_page ( 'Look & Feel', 'Look & Feel', 'edit_theme_options', 'nav-menus.php' );
		add_submenu_page( 'nav-menus.php', 'Menus', 'Menus', 'edit_theme_options', 'nav-menus.php' );
		add_submenu_page( 'nav-menus.php', 'Custom Header', 'Custom Header', 'edit_theme_options', 'themes.php?page=custom-header' );
		add_submenu_page( 'nav-menus.php', 'Custom Background', 'Custom Background', 'edit_theme_options', 'themes.php?page=custom-background' );
	}
}
add_action('admin_menu', 'GTPress_hide_themes', 999);

/////////////////////////////////////////////////////////////////

///////////////////// CHANGE MENU ORDER /////////////////////////

function GTPress_menu_order($menu_ord) 
{
	if (isset($options["menu_order"])) 
	{
		if (!$menu_ord) return true;
		return array($options["menu_order"]);
	}
}
add_filter('custom_menu_order', 'GTPress_menu_order');
add_filter('menu_order', 'GTPress_menu_order');

/////////////////////////////////////////////////////////////////

///////////////////// REMOVE PAGE METAS /////////////////////////

function GTPress_hide_pagemeta() {
	if ($options['hide_pagemeta'] == "true") {
		remove_meta_box( 'commentstatusdiv' , 'page' , 'normal' ); // allow comments for pages
		remove_meta_box( 'commentsdiv' , 'page' , 'normal' ); // recent comments for pages
		remove_meta_box( 'postcustom' , 'page' , 'normal' ); // custom fields for pages
		remove_meta_box( 'trackbacksdiv' , 'page' , 'normal' ); // page trackbacks
		remove_meta_box( 'postexcerpt' , 'page' , 'normal' ); // page excerpts
		remove_meta_box( 'tagsdiv-post_tag' , 'page' , 'side' ); // page tags
		remove_meta_box( 'pageparentdiv','page','side'); // Page Parent
		remove_meta_box( 'slugdiv','page','normal'); // page slug
	}
}
add_action( 'admin_menu' , 'GTPress_hide_pagemeta' );

/////////////////////////////////////////////////////////////////

///////////////////// REMOVE POST METAS /////////////////////////

function GTPress_hide_postmeta() {
	if ($options['hide_postmeta'] == "true") {
		remove_meta_box( 'commentstatusdiv' , 'post' , 'normal' ); // allow comments for posts
		remove_meta_box( 'commentsdiv' , 'post' , 'normal' ); // recent comments for posts
		remove_meta_box( 'postcustom' , 'post' , 'normal' ); // custom fields for posts
		remove_meta_box( 'trackbacksdiv' , 'post' , 'normal' ); // post trackbacks
		remove_meta_box( 'postexcerpt' , 'post' , 'normal' ); // post excerpts
		remove_meta_box( 'tagsdiv-post_tag' , 'post' , 'side' ); // post tags
		remove_meta_box( 'slugdiv','post','normal'); // post slug
	}
}
add_action( 'admin_menu' , 'GTPress_hide_postmeta' );

/////////////////////////////////////////////////////////////////

///////////////////// HIDE DASHBOARD WIDGETS ////////////////////

function GTPress_unset_dashboard() {
	$disabled_menu_items = get_option('gtpressMenu_disabled_menu_items');
	$disabled_submenu_items = get_option('gtpressMenu_disabled_submenu_items');
	if (in_array('index.php', $disabled_menu_items)) {
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_wordpress_blog']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_other_wordpress_news']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);	
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
	}
}
add_action('wp_dashboard_setup', 'GTPress_unset_dashboard');

/////////////////////////////////////////////////////////////////

///////////////////// REMOVE MENU ITEMS /////////////////////////

function gtpressMenu_disable_menus() {

	if (current_user_can('manage_options') && get_option('gtpressMenu_admins_see_everything'))
		return;
	
	global $menu, $submenu;
	$disabled_menu_items = get_option('gtpressMenu_disabled_menu_items');
	$disabled_submenu_items = get_option('gtpressMenu_disabled_submenu_items');

	if (in_array('index.php', $disabled_menu_items))
		GTPress_unset_dashboard(); 

	foreach ($menu as $index => $item) {
		if ($item == 'index.php')
			continue;

		if (in_array($item[2], $disabled_menu_items))
			unset($menu[$index]);
	
		if (!empty($submenu[$item[2]]))
			foreach ($submenu[$item[2]] as $subindex => $subitem) 
				if (in_array($subitem[2], $disabled_submenu_items))
					unset($submenu[$item[2]][$subindex]);
	}
}

/////////////////////////////////////////////////////////////////

///////////////////// REMOVE META ITEMS /////////////////////////

function gtpressMenu_disable_metas() {

	if (current_user_can('manage_options') && get_option('gtpressMenu_admins_see_everything'))
		return;

	remove_action('admin_head', 'index_js');

	$disabled_metas = get_option('gtpressMenu_disabled_metas');
	if (!empty($disabled_metas)) {
		$metas = implode(',', $disabled_metas);
		echo '<style type="text/css">'.$metas.' {display: none !important}</style>';
	}

}

/////////////////////////////////////////////////////////////////

///////////////////// UPDATE MENU OPTIONS ///////////////////////

function update_gtpressMenu_options() {
	update_option('gtpressMenu_disabled_menu_items', isset($_POST['disabled_menu_items']) ? $_POST['disabled_menu_items'] : array()
	);
	update_option('gtpressMenu_disabled_submenu_items', 
		isset($_POST['disabled_submenu_items']) ? $_POST['disabled_submenu_items'] : array()
	);
	update_option('gtpressMenu_disabled_metas', 
		isset($_POST['disabled_metas']) ? $_POST['disabled_metas'] : array()
	);
	update_option('gtpressMenu_admins_see_everything',
		isset($_POST['gtpressMenu_admins_see_everything']) ? true : false
	);
}

/////////////////////////////////////////////////////////////////

///////////////////// ADDING FARBTASTIC /////////////////////////

add_action('init', 'load_Farbtastic');
function load_Farbtastic() {
	wp_enqueue_script('jquery');
	wp_enqueue_style( 'farbtastic' );
	wp_enqueue_script('farbtastic', get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/js/farbtastic.js', array('jquery'));
}

/////////////////////////////////////////////////////////////////

///////////////////// START OPTIONS PAGE ////////////////////////

function gtpressMenu_options_page() {

	if (isset($_POST['submit_GTPress'])) {
			$options["head_btmcolor"] = $_POST['head_btmcolor'];
			$options["head_topcolor"] = $_POST['head_topcolor'];
			$options["hide_themes"] = $_POST['hide_themes'];
			$options["hide_postmeta"] = $_POST['hide_postmeta'];
			$options["hide_pagemeta"] = $_POST['hide_pagemeta'];
			update_option("GTPress", $options);
			update_gtpressMenu_options();
			echo "<div class=\"updated\"><p><strong> GT Press Options Updated!</strong></p></div>";
	}

	?>

 <link rel="stylesheet" href="<?php echo get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/js/farbtastic.css'; ?>" type="text/css" />
 <style type="text/css" media="screen">
   .colorwell {
     border: 2px solid #fff;
     width: 6em;
     text-align: center;
     cursor: pointer;
   }
   body .colorwell-selected {
     border: 2px solid #000;
     font-weight: bold;
   }
 </style>
 
 <script type="text/javascript" charset="utf-8">
  $(document).ready(function() {
    jQuery('#demo').hide();
    var f = jQuery.farbtastic('#picker');
    var p = jQuery('#picker').css('opacity', 0.25);
    var selected;
    jQuery('.colorwell')
      .each(function () { f.linkTo(this); jQuery(this).css('opacity', 0.75); })
      .focus(function() {
        if (selected) {
          jQuery(selected).css('opacity', 0.75).removeClass('colorwell-selected');
        }
        f.linkTo(this);
        p.css('opacity', 1);
        jQuery(selected = this).css('opacity', 1).addClass('colorwell-selected');
      });
  });
 </script>
	<div class="wrap">
	
	<h2><img src="<?php $gtpress_ico = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/'; echo $gtpress_ico.'gtpress.jpg'; ?>"> - GT Press Options</h2>
	<p><strong>WARNING:</strong> Do not change anything on this page unless you know what you are doing.<hr />
	<table border="0">
	<tr>
		<td valign="top" width="60%">
				<form method="post">

<!--DISABLED<h3>Edit Header Style</h3>
            <table width="50%" border="0">
              <tr>
                <td width="30%">Top Gradient Color</td>
                <td width="70%"><input type="text" name="head_topcolor[]" id="color1" class="colorwell" size="6" value="<?php echo $options["head_topcolor"]; ?>" /></td>
              </tr>
              <tr>
                <td width="30%">Bottom Gradient Color</td>
                <td width="70%"><input type="text" name="head_btmcolor[]" id="color2" class="colorwell" size="6" value="<?php echo $options["head_btmcolor"]; ?>" /></td>
              </tr>
            </table>
END_DISABLED-->           
			<h3>Disable Menu Items</h3>

			<table border="0" style="width: 100%">
			<tr>
				<td valign="top" nowrap="nowrap" width="50%">

	<?php

	// MENU ITEMS 

	$menu              = get_option('gtpressMenu_default_menu');
	$submenu           = get_option('gtpressMenu_default_submenu');
	$disabled_items    = get_option('gtpressMenu_disabled_menu_items');
	$disabled_subitems = get_option('gtpressMenu_disabled_submenu_items');
	  
	$i = 0;
	foreach ($menu as $item) {

		if ( $i == (int) (count($menu) / 2) )
			echo '</td><td valign="top" nowrap="nowrap" width="50%">';

		// menu items
		
		$checked = (in_array($item[2], $disabled_items)) ? ' checked="checked"' : '';

		echo '<input type="checkbox"' .	$disabled .	$checked . 
			' name="disabled_menu_items[]"  value="'.$item[2].'" />&nbsp;' .	$item[0] . "<br />\n";

		if (!isset($submenu[$item[2]])) // if no submenu items, exit
			continue;

		// submenu items

		foreach ($submenu[$item[2]] as $subitem) {

			$asterisk = ($subitem[2] == basename(__FILE__)) ? 
				' <span style="color: red; font-weight: bold; font-size: 150%">*</span>' : '';
			
			$checked = (in_array($subitem[2], $disabled_subitems)) ? ' checked="checked"' : '';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox"' . $checked .
				' name="disabled_submenu_items[]" value="' . $subitem[2] . '" />&nbsp;' . $subitem[0] . 
				$asterisk . "<br />\n";

		}

		$i++;

	}

	?>
	
				</td>
			</tr>
			</table>
			
		</td>
		<td valign="top">
	
	<?php

	// PAGE/POST META ITEMS
	
	$metas = array(
		'#authordiv,#pageauthordiv',
		'#pagecommentstatusdiv,#commentstatusdiv',
		'#pagepassworddiv,#passworddiv',
		'#pageslugdiv,slugdiv',
		'#uploading',
		'#pagepostcustom,#postcustom',
		'#categorydiv',
		'#posttimestampdiv',
		'#postexcerpt',
		'#trackbacksdiv',
		'#pageparentdiv',
		'#pagetemplate,#pagetemplatediv',
		'#pageorder,#pageorderdiv',
		'#tagsdiv',
		'div.side-info'
	);
 
	$meta_names = array(
		'Author',
		'Discussion',
		'Password',
		'Slug (< 2.5)',
		'Uploading (< 2.5)',
		'Custom Fields',
		'Post Category',
		'Post Timestamp (< 2.5)',
		'Post Excerpt',
		'Post Trackbacks',
		'Page Parent',
		'Page Template',
		'Page Order', 
		'Post Tags (2.5+)', 
		'\'Related\' Links (2.5+)'
	);
	  
	$disabled_metas = get_option('gtpressMenu_disabled_metas');

	echo '<h3>Disable Page/Post Meta</h3>';
	foreach ($metas as $index => $meta) {
		$checked = (in_array($meta, $disabled_metas)) ? ' checked="checked"' : '';
		echo '<input type="checkbox"' . $checked . ' name="disabled_metas[]" value="' . $meta . '" />&nbsp;' .
			$meta_names[$index] . "<br />\n";
	}

	?>

			<h3>GT Press bookmarklet</h3>

			<p><span style="color: red; font-weight: bold; font-size: 150%">*</span>
			If you are disabling the GT Press options page, bookmark the following link 
			(a 'bookmarklet') to help you open the GT Press options page directly from anywhere within the 
			Wordpress administration area.</p>
			
			<a href="javascript:l=location.href;n=l.indexOf('wp-admin');
			if(n>0)location=l.substring(0,n+9)+'options-general.php?page=GTPress.php';
			void(0);">GT Press options page</a>
	
		</td>
	</tr>
	</table> 
	
	<p class="submit">
		<input type="submit" name="submit_GTPress" value="<?php _e('Update Settings', '') ?> &raquo;">
		<input type="checkbox" name="gtpressMenu_admins_see_everything" value="1" <?php
			if (get_option('gtpressMenu_admins_see_everything')) echo 'checked="checked"'; ?> />
		<label for="gtpressMenu_admins_see_everything">Administrators see everything</label></p> 
	
	<input type="hidden" name="update_gtpressMenu_options" value="1" /></form></div>

	<script type="text/javascript">
	$j=jQuery.noConflict();
	var gtpressMenuGroups = { 
		'pages' : [
			'page-new.php',
			'edit-pages.php',
			'#pageparentdiv',
			'#pagetemplate,#pagetemplatediv',
			'#pageorder,#pageorderdiv'
		],
		'comments' : [
			'edit-comments.php',
			'moderation.php',
			'options-discussion.php',
			'#trackbacksdiv',
			'#pagecommentstatusdiv,#commentstatusdiv'
		],
		'links' : [
			'link-manager.php',
			'link-add.php',
			'link-import.php',
			'edit-link-categories.php'
		],
		'categories' : [
			'categories.php',
			'edit-link-categories.php',
			'#categorydiv',
			'div.side-info'
		],
		'themes' : [
			'themes.php',
			'widgets.php',
			'theme-editor.php'
		],
		'tags' : [
			'edit-tags.php',
			'#tagsdiv',
			'div.side-info'
		]
	};
	
	function gtpressMenuInitGroups() {
		var groups = ['pages','comments','links','categories','themes'];
		for (var i = 0; i < groups.length; i++) {
			curGroup = eval('gtpressMenuGroups.'+groups[i]);
			var allChecked = true;
			for (var j = 0; j < curGroup.length; j++) {
				$j("input[@value='"+curGroup[j]+"']").each(function(){
					if (!this.disabled && this.checked == false)
						allChecked = false;
				});
			}
			if (allChecked)
				$j('#gtpressMenu'+groups[i]).each(function(){
					this.checked = true;
				});
		}
	}
	gtpressMenuInitGroups();

	function gtpressMenuDoGroup(group) {
		if ($j('#gtpressMenu'+group).is(':checked'))
			gtpressMenuEnableGroup(group);
		else
			gtpressMenuDisableGroup(group);
	}
	
	function gtpressMenuEnableGroup(group) {
		var items = eval('gtpressMenuGroups.'+group);
		for (var i = 0; i < items.length; i++) 
			gtpressMenuDisable(items[i]);
	}

	function gtpressMenuDisableGroup(group) {
		var items = eval('gtpressMenuGroups.'+group);
		for (var i = 0; i < items.length; i++) 
			gtpressMenuEnable(items[i]);
	}
		
	function gtpressMenuEnable(pageUrl) {
		$j("input[@value='"+pageUrl+"']").each(function(){ 
			if (!this.disabled) 
				this.checked = false; 
		});
	}

	function gtpressMenuDisable(pageUrl) {
		$j("input[@value='"+pageUrl+"']").each(function(){ 
			if (!this.disabled) 
				this.checked = true;
		});
	}
	</script>
	
	<?php
}

/////////////////////////////////////////////////////////////////

///////////////////// UPDATE MENU OPTIONS ///////////////////////

function set_gtpressMenu_options() {
	add_option('gtpressMenu_default_menu');
	add_option('gtpressMenu_default_submenu');
	add_option('gtpressMenu_disabled_menu_items');
	add_option('gtpressMenu_disabled_submenu_items');
	add_option('gtpressMenu_disabled_metas');
	add_option('gtpressMenu_admins_see_everything');

	update_option('gtpressMenu_disabled_menu_items', array());
	update_option('gtpressMenu_disabled_submenu_items', array());
	update_option('gtpressMenu_disabled_metas', array());
	update_option('gtpressMenu_admins_see_everything', false);
}

function unset_gtpressMenu_options() {
	delete_option('gtpressMenu_default_menu');
	delete_option('gtpressMenu_default_submenu');
	delete_option('gtpressMenu_disabled_menu_items');
	delete_option('gtpressMenu_disabled_submenu_items');
	delete_option('gtpressMenu_disabled_metas');
	delete_option('gtpressMenu_admins_see_everything');
}

/////////////////////////////////////////////////////////////////

///////////////////// HIDE DASHBOARD WIDGETS ////////////////////

function gtpress_disable_dashboard() {
 	global $wp_meta_boxes;
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_wordpress_blog']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_other_wordpress_news']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);	
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
	unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
}

/////////////////////////////////////////////////////////////////

?>
