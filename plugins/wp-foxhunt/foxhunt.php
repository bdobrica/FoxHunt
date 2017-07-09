<?php
/*
Plugin Name: FoxHunt
Plugin URI: https://www.foxhunt.ro
Description:
Author: Bogdan Dobrica
Version: 0.1
Author URI: http://ublo.ro/
*/
define ('WP_FOXHUNT_PLUGIN', plugin_dir_path (__FILE__));

spl_autoload_register (function ($class) {
        if (strpos ($class, 'FH_') !== 0) return;
        $file = __DIR__ . '/class/' . strtolower ($class) . '.php';
        if (!file_exists ($file)) return;
        include ($file);
        });

$fh_plugin = new FH_Plugin ();
?>
