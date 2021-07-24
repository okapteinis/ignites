<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Ignites
 */

if ( is_active_sidebar( 'widget-sidebar' ) ) :?>
    <div class="col-lg-4">
        <aside id="secondary" class="widget-area">
            <?php dynamic_sidebar( 'widget-sidebar' ); ?>
        </aside><!-- widget area -->
    </div>
<?php endif; 
