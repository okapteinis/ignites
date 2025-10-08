<?php
/**
 * Ignites Theme Theme Customizer
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 * @package Ignites
 */

function ignites_customize_register( $wp_customize ) {

	$wp_customize->remove_section( 'header_image' );

	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	// Header Background Color
	$wp_customize->add_setting(
		'header_bg_color',
		array(
			'default'           => '#fff',
			'transport'         => 'postMessage',
			'sanitize_callback' => 'sanitize_hex_color',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Color_Control(
			$wp_customize,
			'header_bg_color', // Match setting ID
			array(
				'label'    => __( 'Header Background Color', 'ignites' ),
				'section'  => 'colors',
				'priority' => 2,
			)
		)
	);

	// Sidebar Settings
	$wp_customize->add_section('ignites_sidebar_section', array(
		'title'    => __('Sidebar', 'ignites'),
		'priority' => 110,
	));

	$wp_customize->add_setting( 'ignites_sidebar_settings', array(
		'capability'        => 'edit_theme_options',
		'sanitize_callback' => 'ignites_sanitize_select',
		'default'           => 'right-sidebar',
	) );

	$wp_customize->add_control( 'ignites_sidebar_settings', array(
		'type'    => 'select',
		'section' => 'ignites_sidebar_section',
		'label'   => __( 'Sidebar Layout', 'ignites' ),
		'choices' => array(
			'no-sidebar'    => __( 'No Sidebar', 'ignites' ),
			'left-sidebar'  => __( 'Left Sidebar', 'ignites' ),
			'right-sidebar' => __( 'Right Sidebar', 'ignites' ),
		),
	) );

	// Selective refresh for core components
	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial( 'blogname', array(
			'selector'        => '.site-title a',
			'render_callback' => 'ignites_customize_partial_blogname',
		) );
		$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
			'selector'        => '.site-description',
			'render_callback' => 'ignites_customize_partial_blogdescription',
		) );
		$wp_customize->selective_refresh->add_partial( 'copyright_txt', array(
			'selector'        => '.copyright-text',
			'render_callback' => 'ignites_customize_partial_copyright_txt',
		) );
	}

	// Footer Settings
	$wp_customize->add_section('ignites_footer_section', array(
		'title'    => __('Footer', 'ignites'),
		'priority' => 120,
	));

	$wp_customize->add_setting('copyright_txt', array(
		'default'           => '',
		'transport'         => 'postMessage',
		'sanitize_callback' => 'wp_filter_nohtml_kses',
	));

	$wp_customize->add_control('copyright_txt', array(
		'label'    => __('Footer Copyright Text', 'ignites'),
		'section'  => 'ignites_footer_section',
		'type'     => 'textarea',
	));
}
add_action( 'customize_register', 'ignites_customize_register' );

/**
 * Sanitize select control.
 */
function ignites_sanitize_select( $input, $setting ) {
	// Ensure input is a slug.
	$input = sanitize_key( $input );
	// Get list of choices from the control associated with the setting.
	$choices = $setting->manager->get_control( $setting->id )->choices;
	// If the input is a valid key, return it; otherwise, return the default.
	return ( array_key_exists( $input, $choices ) ? $input : $setting->default );
}

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
 * Render the copyright text for the selective refresh partial.
 *
 * @return void
 */
function ignites_customize_partial_copyright_txt() {
	echo esc_html( get_theme_mod( 'copyright_txt' ) );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function ignites_customize_preview_js() {
	wp_enqueue_script( 'ignites-customizer', get_template_directory_uri() . '/assets/js/customizer.js', array( 'customize-preview' ), IGNITES_THEME_VERSION, true );
}
add_action( 'customize_preview_init', 'ignites_customize_preview_js' );

/**
 * In-line CSS for Customizer options.
 */
function ignites_customizer_css() {
	$header_bg_color = get_theme_mod( 'header_bg_color', '#fff' );
	?>
	<style type="text/css">
		.header-section { background-color: <?php echo esc_hex_color( $header_bg_color ); ?>; }
	</style>
	<?php
}
add_action( 'wp_head', 'ignites_customizer_css' );