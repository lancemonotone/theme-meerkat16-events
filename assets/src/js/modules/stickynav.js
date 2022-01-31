export default function () {
  var networkHeader       = jQuery( '.network-header' ),
      secondaryNavigation = jQuery( '.events-secondary-nav' ), //cal search/filter bar
      belowNavHeroContent = jQuery( '#masthead' ), //beginning of main content
      hero                = jQuery( '.events-hero' ),
      networkHeight       = networkHeader.height(),
      heroHeight          = hero.height(),
      navHeight           = secondaryNavigation.height();

  //set scrolling variables
  var scrolling    = false,
      previousTop  = 0,
      currentTop   = 0,
      scrollOffset = 50;

  jQuery( window ).on( 'scroll', function () {
    if ( !scrolling ) {
      jQuery( '.datepicker' ).remove();
      scrolling = true;
      (!window.requestAnimationFrame)
        ? barslide
        : requestAnimationFrame( barslide );
    }
  } );

  jQuery( window ).on( 'resize', function () {
    networkHeight = networkHeader.height(),
      heroHeight = hero.height(),
      navHeight = secondaryNavigation.height();
  } );

  function barslide() {
    var currentTop = jQuery( window ).scrollTop();
    if ( belowNavHeroContent.length > 0 && (previousTop != currentTop) ) {
      checkStickyNavigation( currentTop );
    } // events bar
    previousTop = currentTop;
    scrolling = false;
  }

  function checkStickyNavigation( currentTop ) {
    //no longer needed with megamenu in overlay
    //let megamenuHeight = jQuery('.network-utility').height(); //needs to be checked on the fly

    //events bar
    var secondaryNavOffsetTop = networkHeight + (heroHeight / 2) - (navHeight / 2);
    if ( previousTop >= currentTop ) {
      //if scrolling up...
      if ( currentTop < secondaryNavOffsetTop + scrollOffset ) {

        secondaryNavigation.removeClass( 'fixed slide-up' );
        belowNavHeroContent.removeClass( 'secondary-nav-fixed' );
      }

    } else {
      //if scrolling down...
      if ( currentTop > secondaryNavOffsetTop + scrollOffset ) {
        secondaryNavigation.addClass( 'fixed slide-up' );
        belowNavHeroContent.addClass( 'secondary-nav-fixed' );
      }

    }
  }
}