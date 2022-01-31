import { Common } from '../../../../../../lib/assets/js/src/common.es6';
import { Events as Dispatcher } from '../../../../../../lib/assets/js/src/modules/class.events.es6';

!(function ( Common, Dispatcher ) {
  // Submit button
  const $eventFormSubmitBtn = document.querySelector( 'input[type="submit"][name="community-event"]' );
  // Store selector for brevity.
  const eventTypeRadios = '.tribe_community_edit .acf-field[data-name="event_type"] input[type="radio"]';
  const $eventTypeRadios = document.querySelectorAll( eventTypeRadios );

  // Event type checkboxes
  const $eventCheck = document.querySelector( `${eventTypeRadios}[value="event"]` );
  const $announcementCheck = document.querySelector( `${eventTypeRadios}[value="announcement"]` );
  const $musicCheck = document.querySelector( `${eventTypeRadios}[value="music"]` );
  const $ctdCheck = document.querySelector( `${eventTypeRadios}[value="62_center"]` );
  const $gradArtCheck = document.querySelector( `${eventTypeRadios}[value="grad-art"]` );
  const $sportsCheck = document.querySelector( `${eventTypeRadios}[value="sports"]` );
  const allChecks = [$eventCheck, $announcementCheck, $musicCheck, $ctdCheck, $gradArtCheck, $sportsCheck];

  // DM checkboxes
  const $dailyMessageCheck = document.querySelector( '.tribe_community_edit .acf-field[data-name="acf_is_dm"] input[type="checkbox"]' );
  const $audienceCheck = document.querySelectorAll( '.tribe_community_edit .acf-field[data-name="acf_dm_audience"] input[type="checkbox"]' );

  const $shouldBeHomeBtns = document.querySelectorAll(
    '.button, ' +
    '.button-primary, ' +
    '.tribe-button, ' +
    '.tribe-pagination > span,' +
    '.tribe-pagination > a' );

  const $requiredFields = document.querySelectorAll(
    '.acf-field.events-required'
  );

  // These checkboxes will automatically control Submit to Daily Messages.
  const autoDailyMessages = [$announcementCheck, $musicCheck, $ctdCheck];

  /**
   * This provides the functionality for the preview page confirmation buttons.
   */
  function confirmSubmission() {
    //const $form = document.querySelector( 'form#confirm-submission' );
    const $formcommit = document.querySelector( 'form#confirm-submission input[name="confirm"]' );
    const $formedit = document.querySelector( 'form#confirm-submission .edit-event' );
    const $formcancel = document.querySelector( 'form#confirm-submission .cancel-event' );

    if ( $formcommit && $formedit ) {
      let confirmSubmit = false;

      $formcommit.addEventListener( 'click', () => {
        confirmSubmit = true;
      } );

      $formcancel.addEventListener( 'click', () => {
        confirmSubmit = true;
      } );

      $formedit.addEventListener( 'click', () => {
        confirmSubmit = true;
      } );

      window.addEventListener( 'beforeunload', e => {
        if ( !confirmSubmit ) {
          e.preventDefault();
          e.returnValue = '';
        }
        //return confirmSubmit && confirm( "Your submission is not complete. Do you wish to leave the page?" );
      } );
    }
  }

  function addBackButton() {
    let $els;

    if ( document.referrer && ($els = document.querySelectorAll( '.back-btn' )) ) {
      for ( let $i = 0; $i < $els.length; $i++ ) {
        $els[$i].classList.remove( 'hidden' );
        // Provide a standard href to facilitate standard browser features such as
        //  - Hover to see link
        //  - Right click and copy link
        //  - Right click and open in new tab
        $els[$i].setAttribute( 'href', document.referrer );

        // We can't let the browser use the above href for navigation. If it does,
        // the browser will think that it is a regular link, and place the current
        // page on the browser history, so that if the user clicks "back" again,
        // it'll actually return to this page. We need to perform a native back to
        // integrate properly into the browser's history behavior
        $els[$i].onclick = function () {
          history.back();
        }
      }
    }
  }

  /**
   * Show counter to indicate max length to user.
   */
  function addCounter() {
    const $els = document.querySelectorAll( '.add-counter' );
    for ( let $i = 0; $i < $els.length; $i++ ) {
      const $input = $els[$i].querySelector( 'input, textarea' );
      const maxLength = $input.maxLength;
      const $counter = $els[$i].querySelector( 'span.counter' );
      $counter.innerHTML = maxLength;
      $input.onkeyup = () => {
        let length = $input.value.length;
        let count = maxLength - length;
        if ( length > maxLength ) {
          return false;
        } else {
          $input.classList.remove( 'input-full' );
          $counter.classList.remove( 'input-full' );
        }
        $counter.innerText = count;
        if ( parseInt( count ) <= 0 ) {
          $input.classList.add( 'input-full' );
          $counter.classList.add( 'input-full' );
        }
      }
    }
  }

  function addRequired() {
    for ( let $i = 0; $i < $requiredFields.length; $i++ ) {
      const $label = $requiredFields[$i].querySelector( 'label' );
      $label.innerHTML += ' <span class="acf-required">*</span>';
    }
  }

  /**
   * Give all buttons the same class for styling and events
   */
  function addBtnClass() {
    for ( let i = 0; i < $shouldBeHomeBtns.length; i++ ) {
      $shouldBeHomeBtns[i].classList.add( "home-btn" );
    }
  }

  /**
   * Show Event Type checkboxes based on Member's plugin assigned roles or Admin/Editor.
   * Ex: 62_center, music, grad_art, sports
   */
  function showEventTypesByRole() {
    let nodes = [];
    const caps = JSON.parse( M16Events.user ); // via wp_localize()

    if ( !caps ) {
      return;
    }
    const capsKeys = Object.keys( caps );

    if ( capsKeys.indexOf( 'administrator' ) > -1 || capsKeys.indexOf( 'editor' ) > -1 ) {
      // If user is administrator or editor, select all checkboxes
      const list = document.querySelectorAll( eventTypeRadios );
      // Have to call Array.slice() because a NodeList isn't an Array
      nodes = Array.prototype.slice.call( list );
    } else {
      // Select only checkboxes that match user role set via Members plugin
      for ( let i = 0; i < capsKeys.length; i++ ) {
        // <input type="checkbox" value="62_center">
        const $item = document.querySelector( `${eventTypeRadios}[value='${capsKeys[i]}']` );
        nodes.push( $item );
      }
    }

    // Show selected checkboxes
    if ( nodes.length ) {
      //console.log('Nodes', nodes);
      nodes.forEach( function ( item ) {
        //console.log( 'Item', item[0] );
        const $el = Common.elements.findAncestor( item, 'li' );
        if ( $el ) {
          $el.style.display = 'block';
        }
      } );
    }
  }

  /**
   * Add listeners and emitters to form elements
   */
  function addEvents() {
    //Dispatcher.on( 'didSubmit', didSubmit );

    /*$eventFormSubmitBtn.addEventListener( 'click', e => {
      Dispatcher.emit( 'syncDailyMessagesCheckboxes' );
    } );*/

    /*function didSubmit( e ) {
      
    }*/

    Dispatcher.on( 'showFields', showFields );

    // Event type checkboxes control display of related fields.
    for ( let i = 0; i < $eventTypeRadios.length; i++ ) {
      $eventTypeRadios[i].addEventListener( 'change', e => {
        Dispatcher.emit( 'showFields', e );
      } );
    }

    /**
     * Show only fields needed by event type.
     * @param e
     */
    function showFields( e ) {
      // [data-showif] is attached to form sections (tribe-events/community/edit-event.php).
      // Each contains array of related event types. Section will display if related event type is checked.
      const $fields = document.querySelectorAll( '[data-showif]' );
      let dataArr;
      let value;

      for ( let i = 0; i < $fields.length; i++ ) {
        dataArr = $fields[i].dataset.showif;
        value = e.target.value;
        Common.elements.removeClass( $fields[i], 'shown' );
        if ( e.target.checked && dataArr.includes( value ) ) {
          Common.elements.addClass( $fields[i], 'shown' );
        }
      }
    }

    Dispatcher.on( 'syncDailyMessagesCheckboxes', syncDailyMessagesCheckboxes );
    Dispatcher.on( 'maybeDisableForAudience', maybeDisableForAudience );

    // On page load, check to see if any event type checkboxes are checked, and trigger
    // display of related fields.
    for ( let i = 0; i < allChecks.length; i++ ) {
      if ( allChecks[i] && allChecks[i].checked ) {
        dispatchEvent( allChecks[i], 'change' );
      }
    }

    // Sync
    $dailyMessageCheck && $dailyMessageCheck.addEventListener( 'click', e => {
      throwRequiredCheckboxError( $dailyMessageCheck, $announcementCheck, 'This field is required for Daily Messages.', 'syncDailyMessagesCheckboxes' );
    } );

    $audienceCheck && $audienceCheck.forEach( item => {
      item.addEventListener( 'change', e => {
        throwRequiredCheckboxError( e.target, $dailyMessageCheck, 'At least one target audience is required.', 'maybeDisableForAudience' );
      } );
    } );

    $announcementCheck && $announcementCheck.addEventListener( 'change', e => {
      Dispatcher.emit( 'syncDailyMessagesCheckboxes' );
    } );

    $musicCheck && $musicCheck.addEventListener( 'change', e => {
      Dispatcher.emit( 'syncDailyMessagesCheckboxes' );
    } );

    $ctdCheck && $ctdCheck.addEventListener( 'change', e => {
      Dispatcher.emit( 'syncDailyMessagesCheckboxes' );
    } );
  }

  // Throw error if one checkbox ($targetCheckbox) requires another checkbox ($requiredCheckbox) to be true
  function throwRequiredCheckboxError( $targetCheckbox, $requiredCheckbox, errorMessage, action = '' ) {
    // First delete all warnings for this field.
    const parent = Common.elements.findAncestor( $targetCheckbox, '.acf-field' );
    parent.querySelectorAll( '.acf-error-message' ).forEach( e => e.parentNode.removeChild( e ) );

    const $checkGroup = parent.querySelectorAll( 'input[type=checkbox]' );

    let checked;
    if ( $checkGroup.length > 1 ) {
      checked = isGroupChecked( $checkGroup );
    } else {
      checked = $targetCheckbox.checked;
    }

    if ( !checked && $requiredCheckbox.checked ) {
      var temp = document.createElement( 'div' );
      Common.elements.addClass( temp, 'acf-notice' );
      Common.elements.addClass( temp, '-error' );
      Common.elements.addClass( temp, 'acf-error-message' );
      temp.innerHTML = '<p>' + errorMessage + '</p>';
      parent.querySelector( '.acf-label' ).insertAdjacentElement( 'afterend', temp );

    }

    // Execute action
    action && Dispatcher.emit( action );
  }

  function isGroupChecked( $group ) {
    let checked = false;
    $group.forEach( $item => {
      if ( !checked ) {
        if ( $item.checked ) {
          checked = true;
        }
      }
    } );
    return checked;
  }

  function maybeDisableForAudience() {
    $eventFormSubmitBtn.disabled = !!document.querySelector( '.acf-field[data-name="acf_dm_audience"]' ).querySelector( '.acf-error-message' );
  }

  /**
   * Automatically check the Submit to Daily Message box if any of the autocheck boxes are checked.
   */
  function syncDailyMessagesCheckboxes() {
    $dailyMessageCheck.checked = $dailyMessageCheck.checked || autoDailyMessages.some( item => {
      return item.checked;
    } );

    dispatchEvent( $dailyMessageCheck, 'change' );
  }

  /**
   * Fire event from specific element.
   // https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
   * @param el
   * @param event
   */
  function dispatchEvent( el, event ) {
    const evt = document.createEvent( 'Event' );
    evt.initEvent( event, true, true );
    el.dispatchEvent( evt );
  }

  function datePickerInit() {
    if ( typeof acf === 'undefined' ) {
      return;
    }

    acf.add_filter( 'date_picker_args', function ( args, $field ) {
      // Some pages (lookin at you DM archive) don't have the $ alias.
      $ = $ || jQuery;

      if ( typeof $ !== 'undefined' ) {
        args.beforeShowDay = $.datepicker.noWeekends;
        // $input (jQuery) text input element
        // args (object) args given to the datepicker function
        // field (object) field instance
      }
      return args;
    } );
  }

  function initForm() {
    addBtnClass();
    addEvents();
    showEventTypesByRole();
    addCounter();
    addRequired();
    addBackButton();
    datePickerInit();
    confirmSubmission();
  }

  initForm();

})( Common, Dispatcher );