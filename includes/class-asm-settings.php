<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class ASM_Admin_Settings
 *
 * @author Artem Abramovich
 * @since  2.1.0
 */
class ASM_Admin_Settings {
	
	/**
	 * ASM_Admin_Settings constructor.
	 *
	 * @since 2.1.0
	 *
	 */
	public function __construct() {
		
		add_action( 'admin_menu', array( $this, 'add_page_settings' ) );
		add_action( 'admin_init', array( $this, 'activate_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts_admin' ) );
	}
	
	/**
	 * Connection of settings scripts
	 *
	 * @since 2.1.0
	 */
	public function load_scripts_admin() {
		
		wp_enqueue_script( 'asm-setting-script', ASM_PLUGIN_URI . '/assets/js/script-settings.js', array( 'jquery' ), ASM_PLUGIN_VER, false );
		wp_enqueue_media();
	}
	
	/**
	 * Connection settings page
	 *
	 * @since 2.1.0
	 */
	public function add_page_settings() {
		
		add_options_page( __( 'Settings', 'art-schema-markup' ), 'Markup Schemaorg', 'manage_options', 'markup_slug', array( $this, 'setting_callback' ) );
	}
	
	/**
	 * Return function for settings page
	 *
	 * @since 2.1.0
	 */
	public function setting_callback() {
		
		?>
		<div class="wrap">
			<h2><?php echo get_admin_page_title() . ' ' . ASM_PLUGIN_NAME ?></h2>
			
			<form action="options.php" method="POST">
				<?php
				
				settings_fields( 'option_group' );
				do_settings_sections( 'base_setting_page' );
				submit_button();
				?>
			</form>
		</div>
		<?php
		
	}
	
	/**
	 * Creating custom fields
	 *
	 * @since 2.1.0
	 */
	public function activate_settings() {
		
		register_setting( 'option_group', 'asm_option_name', 'sanitize_callback' );
		
		add_settings_section( 'section_id', '', '', 'base_setting_page' );
		
		add_settings_field( 'image_default', __( 'Default image', 'art-schema-markup' ), array( $this, 'image_default_callback' ), 'base_setting_page', 'section_id' );
		
		add_settings_field( 'image_logo', __( 'Default logo', 'art-schema-markup' ), array( $this, 'image_logo_callback' ), 'base_setting_page', 'section_id' );
		
		add_settings_field( 'select_point', __( 'Markup output', 'art-schema-markup' ), array( $this, 'select_point_callback' ), 'base_setting_page', 'section_id' );
	}
	
	/**
	 * Clear data
	 *
	 * @since 2.1.0
	 *
	 * @param $options
	 *
	 * @return mixed
	 */
	public function sanitize_callback( $options ) {
		
		foreach ( $options as $name => & $val ) {
			if ( $name == 'image_default' ) {
				$val = esc_url( $val );
			}
			
			if ( $name == 'image_logo' ) {
				$val = esc_url( $val );
			}
			if ( $name == 'select_point' ) {
				$val = intval( $val );
			}
		}
		
		return $options;
	}
	
	/**
	 * Return function of selection field
	 *
	 * @since 2.1.0
	 */
	public function select_point_callback() {
		
		$vals = array(
			array(
				'id'       => 'header',
				'label'    => __( 'In header', 'art-schema-markup' ),
				'img_link' => '/assets/image/header.jpg',
			),
			array(
				'id'       => 'before_post',
				'label'    => __( 'Before content', 'art-schema-markup' ),
				'img_link' => '/assets/image/before_content.jpg',
			),
			array(
				'id'       => 'after_post',
				'label'    => __( 'After content', 'art-schema-markup' ),
				'img_link' => '/assets/image/after_content.jpg',
			),
			array(
				'id'       => 'footer',
				'label'    => __( 'In footer', 'art-schema-markup' ),
				'img_link' => '/assets/image/footer.jpg',
			),
		);
		
		$option_value = $this->get_option( 'asm_option_name[select_point]', 'header' );
		
		echo '<fieldset>';
		echo '<style>
			label{
			    position: relative;
       
			}
			label > .label{
				display: block;
				text-align: center;
				padding-bottom: 5px;
			    margin-top: -5px;
			}
			label > input{
				visibility: hidden;
				position: absolute;
			}
			label > input + img{
				cursor:pointer;
				border:2px solid transparent;
				opacity: 0.75;
			}
			label > input:checked + img{
			    border: 2px solid #fff;
			    opacity: 1;
			}
			label > input:checked + img + .label{
			    background: #fff;
			}
		</style>';
		
		foreach ( $vals as $key ) {
			echo '<label>';
			echo '<input type="radio" name="asm_option_name[select_point]" value="' . $key['id'] . '"' . checked( $key['id'], $option_value, false ) . ' />';
			echo '<img src="' . ASM_PLUGIN_URI . $key['img_link'] . '" width="150px" height="auto">';
			echo '<span class="label">' . $key['label'] . '</span></label>';
		}
		echo '</fieldset>';
	}
	
	/**
	 * Helpers function of processing settings fields
	 *
	 * @since 2.1.0
	 *
	 * @param        $option_name
	 * @param string $default
	 *
	 * @return array|mixed|null|string
	 */
	public function get_option( $option_name, $default = '' ) {
		
		// Array value.
		if ( strstr( $option_name, '[' ) ) {
			
			parse_str( $option_name, $option_array );
			
			// Option name is first key.
			$option_name = current( array_keys( $option_array ) );
			
			// Get value.
			$option_values = get_option( $option_name, '' );
			
			$key = key( $option_array[ $option_name ] );
			
			if ( isset( $option_values[ $key ] ) ) {
				$option_value = $option_values[ $key ];
			} else {
				$option_value = null;
			}
		} else {
			// Single value.
			$option_value = get_option( $option_name, null );
		}
		
		if ( is_array( $option_value ) ) {
			$option_value = array_map( 'stripslashes', $option_value );
		} elseif ( ! is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}
		
		return ( null === $option_value ) ? $default : $option_value;
	}
	
	/**
	 * Return function for image selection field
	 *
	 * @since 2.1.0
	 */
	public function image_default_callback() {
		
		$this->image_uploader( 'image_default' );
	}
	
	/**
	 * Helpers image processing function
	 *
	 * @since 2.1.0
	 *
	 * @param $name
	 */
	public function image_uploader( $name ) {
		
		$options = get_option( 'asm_option_name' );
		
		if ( 'image_default' == $name ) {
			$default_image = ASM_PLUGIN_URI . '/assets/image/default.jpg';
		} else {
			$default_image = ASM_PLUGIN_URI . '/assets/image/default-logo.jpg';
		}
		
		if ( ! empty( $options[ $name ] ) ) {
			$image_link = esc_url( $options[ $name ] );
		} else {
			$image_link = $default_image;
		}
		
		echo '
        <div class="asm-upload">
	        <img src="' . $image_link . '" width="250px" height="auto" style="display:block;margin-bottom:5px"/>
            <input type="text" name="asm_option_name[' . $name . ']" id="asm_option_name[' . $name . ']" value="' . $image_link . '" style="width: 172px;"/>
            <button class="upload_image_button button" id="asm_option_name[' . $name . ']" style="padding: 2px 6px;
"><span class="dashicons dashicons-upload"></span></button>
            <span type="text" class="remove_image_button button" style="padding: 3px 6px"><span class="dashicons dashicons-no-alt"></span></span>
        </div>
    ';
		
	}
	
	/**
	 * Return function for logo selection field
	 *
	 * @since 2.1.0
	 */
	public function image_logo_callback() {
		
		$this->image_uploader( 'image_logo' );
	}
}
