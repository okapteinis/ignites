<?php
/**
 * Ignites Theme Theme Customizer
 *
 * @package Ignites
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */


function ignites_customizer_option( $wp_customize ) {

	$wp_customize->add_setting(
		'header_bg_color',
		array(
			'default'     => '#fff',
			'sanitize_callback' => 'sanitize_hex_color'
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'bg_color',
			array(
				'label'      => __( 'Header Background Color', 'ignites' ),
				'section'    => 'colors',
				'settings'   => 'header_bg_color',
				'priority'   =>'2'
			)
		)
	);

	$wp_customize->add_section('ignites_footer_section', array(
		'title'    => __('Footer', 'ignites'),
		'priority' => 120,
	));


	$wp_customize->add_setting('copyright_txt', array(
		'default' => '',
		'transport' => 'postMessage',
		'sanitize_callback' => 'wp_filter_nohtml_kses'
	));

	$wp_customize->add_control('footer_copyright', array(
		'label'      => __('Footer Copyright Text', 'ignites'),
		'section'    => 'ignites_footer_section',
		'settings'   => 'copyright_txt',
        'type'       =>'textarea'
	));

}
add_action( 'customize_register', 'ignites_customizer_option' );



function ignites_customize_register( $wp_customize ) {

	$wp_customize->remove_section( 'header_image' );

	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial( 'blogname', array(
			'selector'        => '.site-title a',
			'render_callback' => 'ignites_customize_partial_blogname',
		) );
		$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
			'selector'        => '.site-description',
			'render_callback' => 'ignites_customize_partial_blogdescription',
		) );
	}
}
add_action( 'customize_register', 'ignites_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function ignites_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function ignites_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function ignites_customize_preview_js() {
	wp_enqueue_script( 'ignites-customizer', get_template_directory_uri() . '/assets/js/customizer.js', array( 'customize-preview' ), '20151215', true );
}
add_action( 'customize_preview_init', 'ignites_customize_preview_js' );

function ignites_customizer_css() {
	?>
	<style type="text/css">
		.header-section { background-color: <?php echo esc_html(get_theme_mod( 'header_bg_color' )); ?>; }
	</style>
	<?php
}
add_action( 'wp_head', 'ignites_customizer_css' );