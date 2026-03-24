$( document ).ready(function(){
    
    /**
     * KTMenu Documentation: https://preview.keenthemes.com/html/metronic/docs/general/menu/api
     * 
     * Note: Must use querySelector only for this KTMenu, jQuery element won't work
     */
    
    var page_name = $( '#current_page_name' ).val();
    if( page_name == "" )
        page_name = "dashboard";
    
    //KTMenu.createInstances();
    var menuElement = document.querySelector( "#kt_aside_menu1" );
    var menu = KTMenu.getInstance( menuElement );
    
    var e_active_element  = document.querySelector( '.menu-item[data-page-name="'+ page_name +'"]' );
    var link = menu.getItemLinkElement( e_active_element );
    if( link != null )
        menu.setActiveLink( link );

    
});
