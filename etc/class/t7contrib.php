<?php
/**
 * track7 contributions class
 * @author misterhaan
 *
 */
class t7contrib {
	/**
	 * Get latest contributions from everyone.
	 * @param number $before Unix timestamp that contributions should be older than
	 * @param number $limit Only return this many contributions
	 * @return mysqli_result Query results with 0 to $limit contributions, or false on error
	 */
	public static function GetAll($before = false, $limit = 9) {
		global $db;
		$sql = 'select c.conttype, c.posted, c.url, u.username, u.displayname, c.authorname, c.authorurl, c.title, c.preview, c.hasmore from contributions as c left join users as u on u.id=c.author';
		if($before !== false)
			$sql .= ' where c.posted<' . +$before;
		$sql .= ' order by c.posted desc limit ' . $limit;
		return $db->query($sql);
	}

	/**
	 * Get latest contributions from a specific user.
	 * @param number $userid ID of the user whose contributions are being requested
	 * @param number $before Unix timestamp that contributions should be older than
	 * @param number $limit Only return this many contributions
	 * @return mysqli_result Query results with 0 to $limit contributions from the user, or false on error
	 */
	public static function GetUser($userid, $before = false, $limit = 12) {
		global $db;
		$sql = 'select conttype, posted, url, title from contributions where author=\'' . +$userid . '\'';
		if($before !== false)
			$sql .= ' and posted<' . +$before;
		$sql .=	' order by posted desc limit ' . +$limit;
		return $db->query($sql);
	}

	/**
	 * Whether there are any more actions.
	 * @param number $before Unix timestamp more contributions must be older than
	 * @param number $userid ID of the user whose contributions are being requested, or false for all users
	 * @return bool Whether there are any more actions
	 */
	public static function More($before, $userid = false) {
		global $db;
		$sql = 'select 1 from contributions where posted<' . +$before;
		if($userid)
			$sql .= ' and author=\'' . +$userid . '\'';
		$sql .= ' limit 1';
		if($more = $db->query($sql))
			return $more->num_rows > 0;
		return false;
	}

	/**
	 * Get the prefix (if any) for a contribution of the specified type.
	 * @param string $type Contribution type, which is the value from the conttype column
	 * @return string Contribution prefix
	 */
	public static function Prefix($type) {
		switch($type) {
			case 'comment':
				return 'comment on ';
		}
		return '';
	}

	/**
	 * Get the postfix (if any) for a contribution of the specified type.
	 * @param string $type Contribution type, which is the value from the conttype column
	 * @return string Contribution postfix
	 */
	public static function Postfix($type) {
		switch($type) {
			case 'discuss':
				return ' discussion';
		}
		return '';
	}

	/**
	 * Get the action words for a contribution type.
	 * @param string $type Contribution type
	 * @return string Action words (defaults to [type]ed) if unknown type
	 */
	public static function ActionWords($type) {
		switch($type) {
			case 'comment':
				return 'commented on';
			case 'guide':
				return 'posted guide';
		}
		if(substr($type, -1) == 'e')
			return $type . 'd';
		return $type . 'ed';
	}
}
