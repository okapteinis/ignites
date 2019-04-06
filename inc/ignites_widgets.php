<?php

	function ignites_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'ignites' ),
		'id'            => 'sidebar-left',
		'description'   => esc_html__( 'Add widgets here.', 'ignites' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'ignites_widgets_init' );