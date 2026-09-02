<?php
/**
 * Registers the "wploti/animation" Gutenberg block that inserts an
 * animation picked from the plugin's Lottie animations library.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WPLOTI_PLUGIN_ROOT' ) ) {
	define( 'WPLOTI_PLUGIN_ROOT', dirname( __DIR__, 2 ) );
}

if ( ! function_exists( 'wploti_get_animation_library_files' ) ) {

	/**
	 * List the .json Lottie animation files bundled with the plugin.
	 *
	 * @return string[]
	 */
	function wploti_get_animation_library_files() {
		$files = glob( WPLOTI_PLUGIN_ROOT . '/animations/*.json' );

		if ( ! $files ) {
			return array();
		}

		return array_map( 'basename', $files );
	}
}

if ( ! function_exists( 'wploti_register_animation_block' ) ) {

	function wploti_register_animation_block() {

		wp_register_script(
			'lottiplayer-script',
			plugins_url( 'js/lottie-player-script.js', WPLOTI_PLUGIN_ROOT . '/wploti_maintenance_redirect.php' ),
			array(),
			defined( 'WPLOTI_VERSION' ) ? WPLOTI_VERSION : false,
			false
		);

		register_block_type(
			__DIR__,
			array(
				'render_callback' => 'wploti_render_animation_block',
			)
		);
	}
}
add_action( 'init', 'wploti_register_animation_block' );

if ( ! function_exists( 'wploti_enqueue_animation_block_assets' ) ) {

	function wploti_enqueue_animation_block_assets() {

		wp_enqueue_script( 'lottiplayer-script' );
	}
}
add_action( 'enqueue_block_assets', 'wploti_enqueue_animation_block_assets' );

if ( ! function_exists( 'wploti_enqueue_animation_block_editor_assets' ) ) {

	function wploti_enqueue_animation_block_editor_assets() {

		wp_localize_script(
			'wploti-animation-editor-script',
			'wploti_animation_block',
			array(
				'animations'    => wploti_get_animation_library_files(),
				'animationsUrl' => trailingslashit( plugins_url( 'animations', WPLOTI_PLUGIN_ROOT . '/wploti_maintenance_redirect.php' ) ),
			)
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'wploti_enqueue_animation_block_editor_assets' );

if ( ! function_exists( 'wploti_render_animation_block' ) ) {

	/**
	 * Render callback for the wploti/animation block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	function wploti_render_animation_block( $attributes ) {

		wp_enqueue_script(
			'lottiplayer-script',
			plugins_url( 'js/lottie-player-script.js', WPLOTI_PLUGIN_ROOT . '/wploti_maintenance_redirect.php' ),
			array(),
			defined( 'WPLOTI_VERSION' ) ? WPLOTI_VERSION : false,
			false
		);

		$animation = isset( $attributes['animation'] ) ? sanitize_file_name( $attributes['animation'] ) : 'default-animation.json';
		$library   = wploti_get_animation_library_files();

		if ( ! in_array( $animation, $library, true ) ) {
			$animation = 'default-animation.json';
		}

		$src      = trailingslashit( plugins_url( 'animations', WPLOTI_PLUGIN_ROOT . '/wploti_maintenance_redirect.php' ) ) . $animation;
		$autoplay = ! empty( $attributes['autoplay'] );
		$loop     = ! empty( $attributes['loop'] );
		$width    = ! empty( $attributes['width'] ) ? $attributes['width'] : '100%';
		$height   = ! empty( $attributes['height'] ) ? $attributes['height'] : '300px';

		$style = sprintf( 'width:%s;height:%s;display:block;', esc_attr( $width ), esc_attr( $height ) );

		return sprintf(
			'<lottie-player src="%1$s" %2$s %3$s style="%4$s"></lottie-player>',
			esc_url( $src ),
			$autoplay ? 'autoplay' : '',
			$loop ? 'loop' : '',
			$style
		);
	}
}
