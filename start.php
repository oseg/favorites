<?php
/**
 * Favorites
 *
 * @package Favorites
 *
 */

elgg_register_event_handler('init', 'system', 'favorites_init');

function favorites_init() {
    	elgg_extend_view('js/elgg', 'js/favorites/site.js');

	// favorites
	elgg_register_widget_type('favorites', elgg_echo('widgets:favorites:title'), elgg_echo('widgets:favorites:description'), ['dashboard']);
	elgg_register_plugin_hook_handler('register', 'menu:extras', 'favorites_extras_register_hook');
	elgg_register_plugin_hook_handler('output:before', 'layout', 'favorites_layout_before_hook');
	elgg_register_action('favorite/toggle', elgg_get_plugins_path() . 'favorites/actions/favorites/toggle.php');
}

/**
 * Function to register menu items for favorites widget
 *
 * @param string $hook_name    name of the hook
 * @param string $entity_type  type of the hook
 * @param string $return_value current return value
 * @param array  $params       hook parameters
 *
 * @return array
 */
function favorites_extras_register_hook($hook_name, $entity_type, $return_value, $params) {
	if (!favorites_has_widget()) {
		return;
	}
	global $FAVORITES_TITLE;
	
	if (empty($FAVORITES_TITLE)) {
		return;
	}
	
	$favorite = favorites_is_linked();
	$toggle_href = 'action/favorite/toggle?link=' . elgg_normalize_url(current_page_url()) . '&title=' . $FAVORITES_TITLE;
	
	$return_value[] = ElggMenuItem::factory([
		'name' => 'widget_favorites_add',
		'text' => elgg_view_icon('star-empty'),
		'href' => $toggle_href,
		'is_action' => true,
		'title' => elgg_echo('widgets:favorites:menu:add'),
		'item_class' => $favorite ? 'hidden' : '',
	]);
	
	$return_value[] = ElggMenuItem::factory([
		'name' => 'widget_favorites_remove',
		'text' => elgg_view_icon('star-alt'),
		'href' => $toggle_href,
		'is_action' => true,
		'title' => elgg_echo('widgets:favorites:menu:remove'),
		'item_class' => $favorite ? '' : 'hidden',
	]);
	
	return $return_value;
}
/**
 * Track the page title for use in sidebar menu
 *
 * @param string $hook_name    name of the hook
 * @param string $entity_type  type of the hook
 * @param string $return_value current return value
 * @param array  $params       hook parameters
 *
 * @return array
 */
function favorites_layout_before_hook($hook_name, $entity_type, $return_value, $params) {
	$title = elgg_extract('title', $return_value);
	if (empty($title)) {
		return;
	}
	
	global $FAVORITES_TITLE;
	$FAVORITES_TITLE = $title;
}
/**
 * Checks if a user has the favorites widget
 *
 * @param int $owner_guid GUID of the user that should own the widget, defaults to logged in user guid
 *
 * @return boolean
 */
function favorites_has_widget($owner_guid = 0) {
	if (empty($owner_guid) && elgg_is_logged_in()) {
		$owner_guid = elgg_get_logged_in_user_guid();
	}
	
	if (empty($owner_guid)) {
		return false;
	}
	
	$options = [
		'type' => 'object',
		'subtype' => 'widget',
		'private_setting_name_value_pairs' => ['handler' => 'favorites'],
		'count' => true,
		'owner_guid' => $owner_guid,
	];
	return (bool) elgg_get_entities_from_private_settings($options);
}
/**
 * Returns the favorite object related to a given url
 *
 * @param string $url url to check, defaults to current page if empty
 *
 * @return false|ElggObject
 */
function favorites_is_linked($url = '') {
	if (empty($url)) {
		$url = current_page_url();
	}
	if (empty($url)) {
		return false;
	}
	
	$options = [
		"type" => "object",
		"subtype" => "widget_favorite",
		"joins" => ["JOIN " . elgg_get_config("dbprefix") . "objects_entity oe ON e.guid = oe.guid"],
		"wheres" => ["oe.description = '" . sanitise_string($url) . "'"],
		"limit" => 1
	];
	
	$entities = elgg_get_entities($options);
	if (empty($entities)) {
		return false;
	}
	
	return $entities[0];
}