(function($) {

    // Scroll to top

    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 600) {
            $('.scroll-top').fadeIn(600);
        } else {
            $('.scroll-top').fadeOut(600);
        }
    });
    $('.scroll-top').on("click", function () {
        $("html,body").animate({
            scrollTop: 0
        }, 500);
        return false;
    });


    // Add Arrow
    $(".main-navigation").find('ul').siblings('a').append('<span class="arrow"></span>');
    $(".arrow").on('click',function(){
        $(this).closest('.menu-item-has-children').find('.sub-menu').toggleClass("show");
    });

    // Stop Scrolling
    $.fn.stopScrolling = function() {
        $('body').on('wheel.modal mousewheel.modal', function () {return false;});
        return this;
    };

    // Restore Scrolling
    $.fn.restoreScrolling = function() {
        $('body').off('wheel.modal mousewheel.modal');
        return this;
    };

    // Toggle Menu
    $( '.hamburger-menu' ).on( 'click', function() {
        $('.body-overlay').addClass('is-active');
        $('.main-navigation').toggleClass('is-active');
        $(this).toggleClass('cross');
        $.fn.stopScrolling();
    });

    $( '.hamburger-menu.cross, .close-navigation' ).on( 'click', function() {
        $('.hamburger-menu').removeClass('cross');
        $('.body-overlay').removeClass('is-active');
        $('.main-navigation').removeClass('is-active');
        $.fn.restoreScrolling();
    });

    // Keyboard Esc
    $(document).keyup(function(e) {
        if (e.keyCode === 27) {
            $('.hamburger-menu').removeClass('cross');
            $('.body-overlay').removeClass('is-active');
            $('.main-navigation').removeClass('is-active');
            $.fn.restoreScrolling();
        }
    });

    $(document).on( 'click', function (e) {
        if ( $( e.target).closest( '.hamburger-menu,.main-navigation' ).length === 0 ) {
            $('.body-overlay').removeClass('is-active');
            $('.main-navigation').removeClass('is-active');
            $('.hamburger-menu').removeClass('cross');
            $.fn.restoreScrolling();
        }
    });

    // Widget add children class
    $(".widget .children").parent().addClass('haschildren');


})(jQuery);
