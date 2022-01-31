<?php
// Don't load directly
defined( 'WPINC' ) or die;

/**
 * My Events Column for Title Display
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe-events/community/columns/title.php
 *
 * @since  4.5
 * @version 4.5
 */

$canView   = ( get_post_status( $event->ID ) == 'publish' || current_user_can( 'edit_post', $event->ID ) );
$canEdit   = current_user_can( 'edit_post', $event->ID );
$canDelete = current_user_can( 'delete_post', $event->ID );
if ( $canEdit ) {
	?>
	<span class="title">
		<a href="<?php echo esc_url( tribe_community_events_edit_event_link( $event->ID ) ); ?>">
			<?php echo get_the_title( $event ); ?>
            <?php if($event->private === 'yes') {?><i class="event-privacy bts bt-lock bt-sm" title="Campus-Only Event"></i><?php } ?>
		</a>
	</span>
	<?php
} else {
	echo get_the_title( $event );
}
?>
<div class="row-actions">
	<?php
	if ( $canView ) {
		?>
		<span class="view">
			<a href="<?php echo esc_url( tribe_get_event_link( $event ) ); ?>"><?php esc_html_e( 'View', 'tribe-events-community' ); ?></a>
		</span>
		<?php
	}

	if ( $canEdit ) {
		echo tribe( 'community.main' )->getEditButton( $event, __( 'Edit', 'tribe-events-community' ), '<span class="edit wp-admin events-cal"> |', '</span> ' );
	}

	if ( $canDelete ) {
		echo tribe( 'community.main' )->getDeleteButton( $event );
	}
	do_action( 'tribe_ce_event_list_table_row_actions', $event );
	?>
</div>
