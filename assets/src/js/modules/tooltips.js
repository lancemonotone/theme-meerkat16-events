(function( window, document, $) {
  if(typeof tribe_ev === 'undefined') return;

  tribe_ev.fn.tooltips = function () {
    var $container = $( document.getElementById( 'tribe-events' ) );
    var $body = $( 'body' );
    var is_shortcode = $container.hasClass( 'tribe-events-shortcode' );
    var is_month_view = $container.hasClass( 'view-month' ) || $body.hasClass( 'events-gridview' );
    var is_week_view = $container.hasClass( 'view-week' ) || $body.hasClass( 'tribe-events-week' );
    var is_photo_view = $container.hasClass( 'view-photo' ) || $body.hasClass( 'tribe-events-photo' );
    var is_day_view = $container.hasClass( 'view-day' ) || $body.hasClass( 'tribe-events-day' );
    var is_list_view = $container.hasClass( 'view-list' ) || $body.hasClass( 'events-list' );
    var is_map_view = $container.hasClass( 'view-map' ) || $body.hasClass( 'tribe-events-map' );
    var is_single = $body.hasClass( 'single-tribe_events' );

    $container.on( 'mouseenter', 'div[id*="tribe-events-event-"], div.event-is-recurring', function () {
      var bottomPad = 0;
      var $this = $( this );
      var $tip;

      if ( is_month_view ) { // Cal View Tooltips
        bottomPad = $this.find( 'a' ).outerHeight() + 16;
      } else if ( is_single || is_day_view || is_list_view ) { // Single/List View Recurring Tooltips
        bottomPad = $this.outerHeight() + 12;
      } else if ( is_photo_view ) { // Photo View
        bottomPad = $this.outerHeight() + 10;
      }

      // Widget Tooltips
      if ( $this.parents( '.tribe-events-calendar-widget' ).length ) {
        bottomPad = $this.outerHeight() - 6;
      }

      if ( !is_week_view || is_shortcode ) {
        if ( is_month_view || is_shortcode ) {
          $tip = $this.find( '.tribe-events-tooltip' );

          if ( !$tip.length ) {
            var data = $this.data( 'tribejson' );

            if ( typeof data == 'string' ) {
              data = $.parseJSON( data );
            }

            var tooltip_template = $this.hasClass( 'tribe-event-featured' )
              ? 'tribe_tmpl_tooltip_featured'
              : 'tribe_tmpl_tooltip';

            $this.append( tribe_tmpl( tooltip_template, data ) );

            $tip = $this.find( '.tribe-events-tooltip' );
          }

          // Look for the distance between top of tooltip and top of visible viewport.
          var dist_to_top = $this.offset().top - ($( window ).scrollTop() + 50); // The +50 is some padding for a
                                                                                 // more aesthetically-pleasing
                                                                                 // view.
          var tip_height = $tip.outerHeight();

          // If true, tooltip is near top of viewport, so tweak some values to keep the tooltip fully in-view.
          if ( dist_to_top < tip_height ) {
            bottomPad = -tip_height;
            $tip.addClass( 'tribe-events-tooltip-flipdown' );
          }

          $tip.css( 'bottom', bottomPad ).stop( true, false ).show();
        } else {
          $this.find( '.tribe-events-tooltip' ).css( 'bottom', bottomPad ).stop( true, false ).show();
        }
      }

    } ).on( 'mouseleave', 'div[id*="tribe-events-event-"], div[id*="tribe-events-daynum-"]:has(a), div.event-is-recurring', function () {

      var $tip = $( this ).find( '.tribe-events-tooltip' );

      $tip.stop( true, false ).hide( 0, function () {
        $tip.removeClass( 'tribe-events-tooltip-flipdown' );
      } );

    } );
  };
  tribe_ev.fn.tooltips();
})( window, document, jQuery);