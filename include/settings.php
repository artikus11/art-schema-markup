<?php
add_action('admin_menu', 'asm_add_plugin_page');
function asm_add_plugin_page(){
	add_options_page( 'Настройки плагина', 'Markup Schemaorg', 'manage_options', 'markup_slug', 'asm_setting_callback' );
}

function asm_setting_callback(){
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title() ?></h2>

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

add_action('admin_init', 'asm_plugin_settings');
function asm_plugin_settings(){

	register_setting( 'option_group', 'asm_option_name', 'sanitize_callback' );

	add_settings_section( 'section_id', 'Основные настройки', '', 'base_setting_page' );

	// параметры: $id, $title, $callback, $page, $section, $args
	add_settings_field('asm_image_default', 'Изображение по умолчанию', 'asm_image_default_callbak', 'base_setting_page', 'section_id' );
	add_settings_field('asm_image_logo', 'Логотип', 'asm_image_logo_callback', 'base_setting_page', 'section_id' );
}

function asm_image_default_callbak(){
	$val = get_option('asm_option_name');
	$val = $val ? $val['image_default'] : null;
	?>
	<input type="text" name="asm_option_name[image_default]" value="<?php echo esc_attr( $val ) ?>" />
	<?php
}

function asm_image_logo_callback(){
	$val = get_option('asm_option_name');
	$val = $val ? $val['image_logo'] : null;
	?>
	<input type="text" name="asm_option_name[image_logo]" value="<?php echo esc_attr( $val ) ?>" />
	<?php
}

## Очистка данных
function sanitize_callback( $options ){
	// очищаем
	foreach( $options as $name => & $val ){
		if( $name == 'image_default' )
			$val = strip_tags( $val );

		if( $name == 'image_logo' )
			$val = strip_tags( $val );
	}

	//die(print_r( $options )); // Array ( [input] => aaaa [checkbox] => 1 )

	return $options;
}