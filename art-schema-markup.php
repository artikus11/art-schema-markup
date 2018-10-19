<?php
/**
 * Plugin Name:       Art Schema Markup
 * Plugin URI:        wpruse.ru/?p=804
 * Text Domain: art-schema-markup
 * Domain Path: /languages
 * Description: Плагин быстрого внедрения микроразметки по schema.org через json-ld для блогов и инфосайтов. Автоматически размечаются посты и страницы
 * Version:           2.1.5
 * Author:            Artem Abramovich
 * Author URI:        https://wpruse.ru/
 * License:           GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt Text Domain: Domain Path:
 *
 * GitHub Plugin URI: https://github.com/artikus11/art-schema-markup
 *
 * Copyright Artem Abramovich
 *
 *     This file is part of Art Schema Markup,
 *     a plugin for WordPress.
 *
 *     Art Schema Markup is free software:
 *     You can redistribute it and/or modify it under the terms of the
 *     GNU General Public License as published by the Free Software
 *     Foundation, either version 3 of the License, or (at your option)
 *     any later version.
 *
 *     Art Schema Markup is distributed in the hope that
 *     it will be useful, but WITHOUT ANY WARRANTY; without even the
 *     implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *     PURPOSE. See the GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with WordPress. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'ASM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASM_PLUGIN_URI', plugins_url( '', __FILE__ ) );

$asm_data = get_file_data( __FILE__, array(
	'ver'         => 'Version',
	'name'        => 'Plugin Name',
	'text_domain' => 'Text Domain',
) );

define( 'ASM_PLUGIN_VER', $asm_data['ver'] );
define( 'ASM_PLUGIN_NAME', $asm_data['name'] );

register_uninstall_hook( __FILE__, array( 'ASM_Schema_Markup', 'uninstall' ) );

/**
 * Class ASM_Schema_Markup
 *
 * Main ASM class, initialized the plugin
 *
 * @class       ASM_Schema_Markup
 * @version     2.1.0
 * @author      Artem Abramovich
 */
class ASM_Schema_Markup {
	
	/**
	 * Instance of ASM_Schema_Markup.
	 *
	 * @since  2.1.0
	 * @access private
	 * @var object $instance The instance of ASM_Schema_Markup.
	 */
	private static $instance;
	
	/**
	 * Construct.
	 *
	 * @since 2.1.0
	 */
	public function __construct() {
		
		$this->init();
		
		// Load textdomain
		$this->load_textdomain();
		
	}
	
	/**
	 * Init.
	 *
	 * Initialize plugin parts.
	 *
	 *
	 * @since 2.1.0
	 */
	public function init() {
		
		if ( version_compare( PHP_VERSION, '5.6', 'lt' ) ) {
			return add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
		}
		
		if ( is_admin() ) :
			
			/**
			 * Settings
			 */
			require_once ASM_PLUGIN_DIR . 'includes/class-asm-settings.php';
			$this->admin_settings = new ASM_Admin_Settings();
		
		endif;
		
		/**
		 * Front end
		 */
		require_once ASM_PLUGIN_DIR . 'includes/class-asm-markup-data.php';
		$this->front_end = new ASM_Markup();
		
		global $pagenow;
		if ( 'plugins.php' == $pagenow ) {
			// Plugins page
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ), 10, 2 );
		}
		
		
	}
	
	
	/**
	 * Textdomain.
	 *
	 * Load the textdomain based on WP language.
	 *
	 * @since 2.1.0
	 */
	public function load_textdomain() {
		
		$locale = apply_filters( 'plugin_locale', get_locale(), 'art-schema-markup' );
		
		// Load textdomain
		load_textdomain( 'art-schema-markup', WP_LANG_DIR . '/art-schema-markup/art-schema-markup-' . $locale . '.mo' );
		load_plugin_textdomain( 'art-schema-markup', false, basename( dirname( __FILE__ ) ) . '/languages' );
		
	}
	
	/**
	 * Instance.
	 *
	 * An global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 2.1.0
	 * @return object Instance of the class.
	 */
	public static function instance() {
		
		if ( is_null( self::$instance ) ) :
			self::$instance = new self();
		endif;
		
		return self::$instance;
		
	}
	
	/**
	 * Plugin action links.
	 *
	 * Add links to the plugins.php page below the plugin name
	 * and besides the 'activate', 'edit', 'delete' action links.
	 *
	 * @since 2.1.0
	 *
	 * @param    array  $links List of existing links.
	 * @param    string $file  Name of the current plugin being looped.
	 *
	 * @return    array            List of modified links.
	 */
	public function add_plugin_action_links( $links, $file ) {
		
		if ( $file == plugin_basename( __FILE__ ) ) :
			$links = array_merge( array(
				'<a href="' . esc_url( admin_url( 'options-general.php?page=markup_slug' ) ) . '">' . __( 'Settings', 'art-schema-markup' ) . '</a>',
			), $links );
		endif;
		
		return $links;
		
	}
	
	/**
	 * Display PHP 5.6 required notice.
	 *
	 * Display a notice when the required PHP version is not met.
	 *
	 * @since 1.0.0
	 */
	public function php_version_notice() {
		
		?>
		<div class="notice notice-error">
			
			<p><?php echo sprintf( __( '%s requires PHP 5.6 or higher and your current PHP version is %s. Please (contact your host to) update your PHP version.', 'art-schema-markup' ), ASM_PLUGIN_NAME, PHP_VERSION ); ?></p>
		</div>
		<?php
		
	}
	
	/**
	 * Deleting settings when uninstalling the plugin
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		
		delete_option( 'asm_option_name' );
	}
	
}


/**
 * The main function responsible for returning the ASM_Schema_Markup object.
 *
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * Example: <?php ASM_Schema_Markup()->method_name(); ?>
 *
 * @since 2.1.0
 *
 * @return object ASM_Schema_Markup class object.
 */
if ( ! function_exists( 'asm_schema_markup' ) ) :
	
	function asm_schema_markup() {
		
		return ASM_Schema_Markup::instance();
	}

endif;

asm_schema_markup();

// Backwards compatibility
$GLOBALS['asm'] = asm_schema_markup();