<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Ignites
 */

?>
</div><!-- #content -->
<div class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <footer id="colophon" class="site-footer">
                    <div class="site-info" id="copyright-txt">
                        <?php
                        if (get_theme_mod('copyright_txt')) {
                            echo esc_html(get_theme_mod('copyright_txt'));
                        } else {
                            echo '&copy; ' . esc_html( date( 'Y' ) ) . ' ';
                            echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>. ';
                            _e(' | <a href="//dopetheme.com">DopeTheme</a>', 'ignites');
                        }
                        ?>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</div>
</div>

<button class="scroll-top" aria-label="<?php esc_attr_e( 'Scroll to top', 'ignites' ); ?>">
    <span class="lnr lnr-chevron-up"></span>
</button>

<?php wp_footer(); ?>

</body>

</html>