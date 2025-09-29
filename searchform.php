<!-- Start SearchForm -->
<form method="get" class="search-form" role="search" action="<?php echo esc_url(home_url('/')); ?>">
    <input type="text" value="<?php echo get_search_query(); ?>" name="s" placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder','ignites' ); ?>" class="form-control">
    <span class="lnr lnr-magnifier"></span>
    <button class="btn hidden" type="submit" aria-label="<?php esc_attr_e( 'Search', 'ignites' ); ?>"><?php esc_html_e( 'Search', 'ignites' ); ?></button>
</form>
<!-- End SearchForm -->