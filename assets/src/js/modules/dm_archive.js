import { Common } from '../../../../../../lib/assets/js/src/common.es6';
import { Events } from '../../../../../../lib/assets/js/src/modules/class.events.es6';

!(function ( Common, Events ) {
  // Set reset flag
  let reset = false;
  let $filter_form;
  let $reset_btn;
  let $submit_btn;

  function init_form() {
    $filter_form = document.querySelector( '.twig-daily-messages #acf-form.dm_filter_form' );

    if ( $filter_form ) {
      $reset_btn = $filter_form.querySelector( 'button[value="reset"]' );
      $submit_btn = $filter_form.querySelector( 'input[type="submit"]' );
      // Move reset button next to submit for styling.
      $reset_btn.after( $submit_btn );
      $submit_btn.after( $reset_btn );

      $reset_btn.addEventListener( 'click', e => {
        reset = true;

        // Start date
        $filter_form.querySelector( '#start_date input[type="text"].hasDatepicker' ).value = '';
        $filter_form.querySelector( '#start_date input[type="hidden"].input-alt' ).value = '';

        // End date
        $filter_form.querySelector( '#end_date input[type="text"].hasDatepicker' ).value = '';
        $filter_form.querySelector( '#end_date input[type="hidden"].input-alt' ).value = '';

        const $keyword = $filter_form.querySelector( '#search_term input[type="text"]' );

        const $category_selects = $filter_form.querySelectorAll( '#acf_event_category select.select2-hidden-accessible option' );
        const $dept_selects = $filter_form.querySelectorAll( '#acf_event_department select.select2-hidden-accessible option' );
        const $audience_selects = $filter_form.querySelectorAll( '#audience label.selected' );

        // Category
        for ( let i = 0; i < $category_selects.length; i++ ) {
          $category_selects[i].parentNode.removeChild( $category_selects[i] );
        }

        // Department
        for ( let i = 0; i < $dept_selects.length; i++ ) {
          $dept_selects[i].parentNode.removeChild( $dept_selects[i] );
        }

        // Audience
        for ( let i = 0; i < $audience_selects.length; i++ ) {
          $audience_selects[i].classList.remove( 'selected' );
          $audience_selects[i].querySelector( 'input[type="checkbox"]' ).checked = false;
        }

        // Keyword
        $keyword.value = '';
        $submit_btn.click();
      } );

      acf.add_filter( 'validation_complete', ( json, $form ) => {
        if ( reset ) {
          const $is_filter_form = $form[0].querySelector( 'input[name="dm_filter_form"]' );
          $is_filter_form.value = '0';
        }

        return json;
      } );
    }
  }

  function initForm() {
    init_form();
  }

  initForm();
})( Common, Events );