(function($) {

    $(document).on( 'click', '.easy-sidebar-menu-widget-toggler', function(e){
        easy_sidebar_menu_widget_toggle( $(this) );

        e.preventDefault();
        e.stopPropagation();
    } );

    function easy_sidebar_menu_widget_toggle( $dis ){
        if( $dis.hasClass('toggle__open') ){
            $dis.removeClass('toggle__open');
        }else{
            $dis.addClass('toggle__open');
        }
        $dis.parent('.link__wrap').parent('.menu-item').children('.sub-menu').slideToggle('fast');
    }

})(jQuery);
