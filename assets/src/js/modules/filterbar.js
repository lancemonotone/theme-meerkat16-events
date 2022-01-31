"use strict";

(function () {
  // Yes, these are too similar to each other. Tribe f'in rocks, not.
  const keywordOpenClass = 'tribe-bar-filters-open';
  const catFilterBodyOpenClass = 'tribe-filters-open';
  const catFilterBodyClosedClass = 'tribe-filters-closed';
  const catFilterToggleCloseClass = 'tribe_events_filters_close_filters';
  // Keyword/Date Form toggle
  const $keywordToggle = document.getElementById( 'tribe-bar-collapse-toggle' );
  // Category toggle (yes there are two with the same ID bc Tribe)
  const $catFilterToggles = document.querySelectorAll( '#tribe_events_filter_control a' );
  // Category filter wrapper
  const $catFilterWrapper = document.getElementById( 'tribe_events_filters_wrapper' );
  // Keyword filter wrapper
  const $keywordFilterWrapper = document.getElementById( 'tribe-bar-form' );
  // Category child toggle
  const $tribeToggleChild = document.querySelectorAll( '.tribe-toggle-child' );

  $tribeToggleChild.forEach( e => e.remove() );

  // Filterbar class change listener, allows Category Filters
  // to hide when Keyword/Date search is open.
  const keywordObserver = new MutationObserver( function ( mutationsList ) {
    mutationsList.forEach( mutation => {
      //console.log( 'Mutation:', mutation );
      if ( mutation.target.classList.contains( keywordOpenClass ) ) {
        // If $keywordToggle contains open class after mutation (click),
        // add keyword open class to document body and close catFilters.
        document.body.classList.add( keywordOpenClass );
        document.body.classList.remove( catFilterBodyOpenClass );
        document.body.classList.add( catFilterBodyClosedClass );
      } else {
        document.body.classList.remove( keywordOpenClass );
      }
      //console.log( $body.classList );
    } );
  } );

  // Keyword Observer
  if ( typeof ($keywordToggle) != 'undefined' && $keywordToggle != null ) {
    keywordObserver.observe( $keywordToggle, { attributeFilter: ["class"] } );
  }

  // If open, close keyword toggle when catFilter toggle is clicked
  $catFilterToggles.forEach( el => {
    el.addEventListener( 'click', el => {
      if ( document.body.classList.contains( keywordOpenClass ) ) {
        $keywordToggle.click();
      }
    } );
  } );

  // Close elements if click outside.
  document.addEventListener( 'click', event => {
    if ( $catFilterWrapper && $keywordFilterWrapper ) {
      const isCatFilterClickInside = $catFilterWrapper.contains( event.target );
      if ( !isCatFilterClickInside ) {
        if ( document.body.classList.contains( catFilterBodyOpenClass ) ) {
          $catFilterWrapper.getElementsByClassName( catFilterToggleCloseClass )[0].click();
        }
      }

      const isKeywordClickInside = $keywordFilterWrapper.contains( event.target );
      if ( !isKeywordClickInside ) {
        if ( $keywordToggle.classList.contains( keywordOpenClass ) && $keywordToggle.style.display !== 'none' ) {
          $keywordToggle.click();
        }
      }
    }
  } );

})();