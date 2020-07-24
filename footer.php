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
                            _e('Copyright &copy; ', 'ignites');
                            echo date("Y");
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

<div class="scroll-top">
    <span class="lnr lnr-chevron-up"></span>
</div>

<?php wp_footer(); ?>

</body>

</html>