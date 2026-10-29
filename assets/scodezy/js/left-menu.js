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

    // An expanded group scrolls into view in the left menu (gen3 1.0.59)
    menu.on( "kt.menu.accordion.shown", function( item ){
        var e_item = ( item && item.nodeType === 1 ) ? item : $( menuElement ).find( '.menu-item.menu-accordion.show' ).last()[ 0 ];   // The group just opened
        LeftMenu.reveal( e_item );
    });

    // The open page's link is visible when the menu is long
    if( link != null ){
        LeftMenu.reveal( link, true );
    }
});

/**
 * Keeps menu items inside the visible part of the scrolling left menu (gen3 1.0.59)
 */
var LeftMenu = {

    margin : 12,                                               // Space kept above / below the item (px)

    /**
     * @description Scrolls the left menu so the whole item (an expanded group with its pages) is visible. Only scrolls
     *              when needed; a group taller than the menu is aligned to its top so its title stays in view.
     * @param {Element} element - Menu item or link
     * @param {boolean} instant - true = no animation (page load)
     * @returns {void}
     */
    reveal : function( element, instant ){
        var $wrapper = $( '#kt_aside_menu_wrapper' );           // The scrolling part of the menu
        if( !element || $wrapper.length === 0 ){ return; }
        var wrapper = $wrapper[ 0 ];
        if( wrapper.scrollHeight <= wrapper.clientHeight ){ return; }   // Menu does not scroll (short menu, or small screens)

        var top    = element.getBoundingClientRect().top - wrapper.getBoundingClientRect().top + wrapper.scrollTop;   // Item top inside the menu
        var bottom = top + element.offsetHeight;                // Item bottom inside the menu
        var view   = wrapper.clientHeight;                      // Visible height of the menu
        var target = wrapper.scrollTop;                         // New scroll position

        if( bottom + LeftMenu.margin > target + view ){         // Bottom hidden: scroll down, but never past the item's top
            target = Math.min( top - LeftMenu.margin, bottom + LeftMenu.margin - view );
        }
        if( top - LeftMenu.margin < target ){                   // Top hidden
            target = top - LeftMenu.margin;
        }
        target = Math.max( 0, target );
        if( target === wrapper.scrollTop ){ return; }

        if( instant ){
            $wrapper.scrollTop( target );
        }
        else{
            $wrapper.stop().animate( { scrollTop: target }, 250 );
        }
    }
};
