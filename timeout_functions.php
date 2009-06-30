<?php

/**
 * A collection of functions used throughout phpLDAPadmin for the timeout and automatic logout feature
 * @author Samuel Tran
 * @package phpLDAPadmin
 *
 */

/**
 * Responsible for setting/updating two session-vars that are used for the timeout and auto logout feature:
 *	- "activity" var records the server last activity.
 *	- "pla_rightframe_server_id" var records the ID of the server active on the right frame.
 * where X is the * ID of the server which the user is working on
 *
 * @param object $ldapserver The LDAPServer object of the server which the user has logged in.
 * @return bool
 */
function set_lastactivity( $ldapserver )
{
	$_SESSION[ 'activity' ][$ldapserver->server_id] = time();
	$_SESSION[ 'pla_rightframe_server_id' ] = $ldapserver->server_id;
	return true;
}

/**
 * Remove the session-var "pla_lastactivity_X" set by update_lastactivity()
 * where X is the * ID of the server
 *
 * @param object $ldapserver The LDAPServer object of the server which the user has logged in.
 * @todo Check that session_write_close doesnt break anything else being here.
 */
function unset_lastactivity( $ldapserver )
{
	if( isset($_SESSION['activity'][$ldapserver->server_id] ) ) {
       		unset( $_SESSION[ 'activity' ][$ldapserver->server_id ] );
		session_write_close();
	}
}


/**
 * Check if custom session timeout has been reached for server $ldapserver.
 * If it has:
 * 	- automatically log out user by calling unset_login_dn( $server_id )
 *	- if $server_id is equal to right frame $server_id, load timeout.php page in the right frame
 *	- return true
 *
 * @param object $ldapserver The LDAPServer object of the server which the user has logged in.
 * @return bool true on success, false on failure.
 */
function session_timed_out( $ldapserver )
{
	global $servers, $lang;

	# If session hasn't expired yet
	if( isset( $_SESSION[ 'activity' ][$ldapserver->server_id] ) ) {

		// If $session_timeout not defined, use ( session_cache_expire() - 1 )
		if ( ! isset( $servers[ $ldapserver->server_id ][ 'session_timeout' ] ) )
			$session_timeout = session_cache_expire()-1;
		else
			$session_timeout = $servers[ $ldapserver->server_id ][ 'session_timeout' ];

		// Get the $last_activity and $rightframe_server_id value
		$last_activity = $_SESSION[ 'activity' ][$ldapserver->server_id];
		$rightframe_server_id = $_SESSION[ 'pla_rightframe_server_id' ];

		// If diff between current time and last activity greater than $session_timeout, log out user
		if ( ( time()-$last_activity ) > ( $session_timeout*60 ) ) {

			if( in_array($ldapserver->auth_type, array('cookie','session')) ) {
				syslog_msg ( LOG_NOTICE,"Logout for " . get_logged_in_dn( $ldapserver ) );
				unset_login_dn( $ldapserver ) or pla_error( $lang['could_not_logout'] );
			}

			// If $ldapserver->server_id equal $rightframe_server_id load timeout page on right frame
			if ( $ldapserver->server_id == $rightframe_server_id ) { ?>
				<SCRIPT LANGUAGE="JavaScript">
				<!--
				parent.right_frame.location.href = 'timeout.php?server_id=<?php echo $ldapserver->server_id; ?>';
				//--></SCRIPT>

			<?php }
			return true;	

		} else
			return false;
	}
}