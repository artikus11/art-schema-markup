<?php
/**
 * Plugin Name:       Art Schema Markup
 * Plugin URI:        wpruse.ru/?p=804
 * Description: Плагин быстрого внедрения микроразметки по schema.org через json-ld для блогов и инфосайтов. Автоматически размечаются посты и страницы
 * Version:           2.0.0
 * Author:            Artem Abramovich
 * Author URI:        https://wpruse.ru/
 * License:           GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt Text Domain: Domain Path:
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'ASM_PLUGIN_DIR', trailingslashit( dirname( __FILE__ ) ) );
define( 'ASM_PLUGIN_URI', plugins_url( '', __FILE__ ) );

require_once( ASM_PLUGIN_DIR . 'include/settings.php' );
require_once( ASM_PLUGIN_DIR . 'include/markup-data.php' );

register_uninstall_hook( __FILE__, 'asm_uninstall' );
function asm_uninstall() {
	delete_option( 'asm_option_name' );
}
