<?php
/*
Plugin Name: BuddyPress Announce Group
Plugin URI: http://wordpress.org/extend/plugins/buddypress-announce-group/
Author: Deryk Wenaus
Author URI: http://www.bluemandala.com
Description: This plugin makes an announcement-only group where only admins or moderators can add content.
Version: 1.3
Revision Date: March 25, 2013
*/



// filter member value. Return false if announcement group and regular member, otherwise return true
// this mainly controls post fields for group activity and group forum
function ag_filter_group_member( $is_member ) {
	global $bp;
	
	if ( ag_get_announce_group() == 'announce' ) {
		if ( $bp->is_item_admin || $bp->is_item_mod ) {
			echo "<p id='announce-only' style='color:#888;font-size:11px;'>". __('Note: This is an announcement-only group where you have access to add content. Regular members can view but not add content.', 'announce_group' ) . "</i></p>";
			return true;
		} else {
			echo "<p id='announce-only' style='color:#888;font-size:11px;'>" . __('This is an announcement-only group, regular members cannot add content.', 'announce_group' ) . "</i></p>";
			return false;
		}
	} else {
		return $is_member;
	}
}
add_filter( 'bp_group_is_member', 'ag_filter_group_member' );


// filter out group status and don't return 'public' for announce groups
// this mainly controls post fields for group activity and group forum
function ag_filter_group_status( $status ) {
	global $bp;

	//echo '<p>filter_group_status - item_admin:'.$bp->is_item_admin . ', item_mod:'.$bp->is_item_mod.'</pre>';
	
	if ( ag_get_announce_group() == 'announce' )
		if ( $bp->is_item_admin || $bp->is_item_mod )
			return $status;
		else
			return 'announce';
	else
		return $status;
}
add_filter( 'bp_get_group_status', 'ag_filter_group_status' );


// change the name of the group if it's an announce group
function ag_filter_group_type( $type ) {
	if ( ag_get_announce_group() == 'announce' ) {
		if ($type == 'Public Group')
			$type = __('Announce Group', 'announce_group' );
		elseif ($type == 'Private Group')
			$type = __('Private Announce Group', 'announce_group' );
		elseif ($type == 'Hidden Group')
			$type = __('Hidden Announce Group', 'announce_group' );
	}
	return $type;
}
add_filter( 'bp_get_group_type', 'ag_filter_group_type', 1 );


// create the annouce group option during group creation and editing
function ag_add_announce_group_form() {
	?>
	<hr />
	<div class="radio">
		<label><input type="radio" name="ag-announce-group" value="normal" <?php ag_announce_group_setting('normal') ?> /> <?php _e( 'This is a normal group (all group members can add content).', 'announce_group' ) ?></label>
		<label><input type="radio" name="ag-announce-group" value="announce" <?php ag_announce_group_setting('announce') ?> /> <?php _e( 'This is an announcement-only group (only moderators and admins can add content).', 'announce_group' ) ?>
		<?php if ( function_exists( 'ass_get_group_subscription_status' ) || function_exists( 'ges_get_group_subscription_status' ) ) echo '<br> &nbsp; &nbsp; &nbsp; Usually, you will set the Email Subscription Defaults to "Subscribed" below.</li></ul>'; ?>
		</label>
	</div>
	<hr />
	<?php
}
add_action ( 'bp_after_group_settings_admin' ,'ag_add_announce_group_form' );
add_action ( 'bp_after_group_settings_creation_step' ,'ag_add_announce_group_form' );


// Get the announce group setting
function ag_get_announce_group( $group = false ) {
	global $groups_template;
	
	if ( !$group )
		$group =& $groups_template->group;

	$group_id = isset( $group->id ) ? $group->id : null;

	if ( ! $group_id &&  isset( $group->group_id ) )
		$group_id = $group->group_id;

	$announce_group = groups_get_groupmeta( $group_id, 'ag_announce_group' );

	return apply_filters( 'ag_announce_group', $announce_group );
}


// echo announce group checked setting for the group admin - default to 'normal' in group creation
function ag_announce_group_setting( $setting ) {
	if ( $setting == ag_get_announce_group() )
		echo ' checked="checked"';
	if ( !ag_get_announce_group() && $setting == 'normal' )
		echo ' checked="checked"';
}


// Save the announce group setting in the group meta, if normal, delete it
function ag_save_announce_group( $group ) { 
	global $bp, $_POST;
	if ( $postval = $_POST['ag-announce-group'] ) {
		if ( $postval == 'announce' )
			groups_update_groupmeta( $group->id, 'ag_announce_group', $postval );
		elseif ( $postval=='normal' )
			groups_delete_groupmeta( $group->id, 'ag_announce_group' );
	}
}
add_action( 'groups_group_after_save', 'ag_save_announce_group' );


// change the name of forum to Announcements for announce groups and hide post links and member list from regular members (cosmetic only)
function ag_change_forum_title( $args ) {
	global $bp;
	if ( ag_get_announce_group() == 'announce' ) {
		$bp->bp_options_nav[$bp->groups->current_group->slug][40]['name'] = 'Announcements';

		if ( !$bp->is_item_admin && !$bp->is_item_mod ) {
			echo '<style type="text/css">#subnav a[href="#post-new"], #subnav .new-reply-link, #members-groups-li { display: none; }</style>';
		}
	}
}
add_action( 'bp_before_group_header', 'ag_change_forum_title' );
