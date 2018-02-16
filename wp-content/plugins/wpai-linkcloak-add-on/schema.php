<?php
/**
 * Plugin database schema
 * WARNING: 
 * 	dbDelta() doesn't like empty lines in schema string, so don't put them there;
 *  WPDB doesn't like NULL values so better not to have them in the tables;
 */

/**
 * The database character collate.
 * @var string
 * @global string
 * @name $charset_collate
 */
$charset_collate = '';

// Declare these as global in case schema.php is included from a function.
global $wpdb, $plugin_queries;

if ( ! empty($wpdb->charset))
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
if ( ! empty($wpdb->collate))
	$charset_collate .= " COLLATE $wpdb->collate";
	
$table_prefix = WPAI_Link_Cloak::getInstance()->getTablePrefix();

$plugin_queries = <<<SCHEMA
CREATE TABLE {$table_prefix}links (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,	
	slug VARCHAR(200) NOT NULL DEFAULT '',
	afflink VARCHAR(800) NOT NULL DEFAULT '',
	PRIMARY KEY  (id),
	KEY slug (slug)	
) $charset_collate;
SCHEMA;
