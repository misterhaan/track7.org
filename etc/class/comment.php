<?php
require_once 'environment.php';
require_once 'user.php';
require_once 'formatDate.php';

class Comment {
	private const ListLimit = 24;

	public int $ID;
	public TimeTagData $Instant;
	public string $HTML;
	public ?string $Markdown;
	public Author $Author;
	public bool $CanChange;
	public array $Edits;

	private function __construct(CurrentUser $user, int $id, int $instant, string $html, ?string $markdown, ?int $authorUserID, Author $author, array $edits = []) {
		$this->ID = $id;
		$this->Instant = new TimeTagData($user, FormatDate::LongHTML, $instant);
		$this->HTML = $html;
		$this->CanChange = $user->IsAdmin() || $user->ID == $authorUserID;
		if ($this->CanChange) {
			$this->Markdown = $markdown;
			if (!$markdown && $user->IsAdmin())
				$this->Markdown = $html;
		}
		$this->Author = $author;
		$this->Edits = $edits;
	}

	/**
	 * List comments for the entire site, most recent first.
	 */
	public static function List(mysqli $db, CurrentUser $currentUser, int $skip = 0): CommentList {
		$limit = self::ListLimit + 1;
		// TODO:  migrate users_friends table
		try {
			$select = $db->prepare('select c.id, unix_timestamp(c.instant), c.user, u.username, u.displayname, u.avatar, u.level, f.fan as friend, c.name, c.contact, c.markdown, c.html, group_concat(concat(unix_timestamp(e.instant), \'\t\', eu.username, \'\t\', eu.displayname) order by e.instant separator \'\n\'), p.title, p.url from comment as c left join post as p on p.id=c.post left join user as u on u.id=c.user left join users_friends as f on f.friend=c.user and f.fan=? left join edit as e on e.comment=c.id left join user as eu on eu.id=e.user group by c.id, f.fan, f.friend order by c.instant desc limit ? offset ?');
			$select->bind_param('iii', $currentUser->ID, $limit, $skip);
			return self::FetchList($select, $currentUser, true);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up all comments', $mse);
		}
	}

	/**
	 * List comments for a post, most recent last.
	 */
	public static function ListByPost(mysqli $db, CurrentUser $currentUser, int $postID, int $skip = 0): CommentList {
		$limit = self::ListLimit + 1;
		// TODO:  migrate users_friends table
		try {
			$select = $db->prepare('select c.id, unix_timestamp(c.instant), c.user, u.username, u.displayname, u.avatar, u.level, f.fan as friend, c.name, c.contact, c.markdown, c.html, group_concat(concat(unix_timestamp(e.instant), \'\t\', eu.username, \'\t\', eu.displayname) order by e.instant separator \'\n\') from comment as c left join user as u on u.id=c.user left join users_friends as f on f.friend=c.user and f.fan=? left join edit as e on e.comment=c.id left join user as eu on eu.id=e.user where c.post=? group by c.id, f.fan, f.friend order by c.instant limit ? offset ?');
			$select->bind_param('iiii', $currentUser->ID, $postID, $limit, $skip);
			return self::FetchList($select, $currentUser);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up comments for post', $mse);
		}
	}

	/**
	 * List comments by a user, most recent first.
	 */
	public static function ListByUser(mysqli $db, CurrentUser $currentUser, int $userID, int $skip = 0): CommentList {
		$limit = self::ListLimit + 1;
		// TODO:  migrate users_friends table
		try {
			$select = $db->prepare('select c.id, unix_timestamp(c.instant), c.user, u.username, u.displayname, u.avatar, u.level, f.fan as friend, c.name, c.contact, c.markdown, c.html, group_concat(concat(unix_timestamp(e.instant), \'\t\', eu.username, \'\t\', eu.displayname) order by e.instant separator \'\n\'), p.title, p.url from comment as c left join post as p on p.id=c.post left join user as u on u.id=c.user left join users_friends as f on f.friend=c.user and f.fan=? left join edit as e on e.comment=c.id left join user as eu on eu.id=e.user where c.user=? group by c.id, f.fan, f.friend order by c.instant desc limit ? offset ?');
			$select->bind_param('iiii', $currentUser->ID, $userID, $limit, $skip);
			return self::FetchList($select, $currentUser, true);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up comments for user', $mse);
		}
	}

	private static function FetchList(mysqli_stmt $select, CurrentUser $currentUser, bool $includePost = false): CommentList {
		$select->execute();
		if ($includePost)
			$select->bind_result($id, $instant, $authorUserID, $username, $displayname, $avatar, $level, $friend, $name, $contact, $markdown, $html, $edits, $title, $url);
		else
			$select->bind_result($id, $instant, $authorUserID, $username, $displayname, $avatar, $level, $friend, $name, $contact, $markdown, $html, $edits);
		$list = new CommentList();
		while ($select->fetch())
			if (count($list->Comments) < self::ListLimit) {
				$list->Comments[] = $comment = new self($currentUser, $id, $instant, $html, $markdown, $authorUserID, new Author($username, $displayname, $name, $contact, $avatar, +$level, boolval($friend)), Edit::Parse($currentUser, $edits));
				if ($includePost) {
					$comment->Title = $title;
					$comment->URL = "$url#comments";
				}
			} else
				$list->HasMore = true;
		return $list;
	}

	public static function Create(mysqli $db, CurrentUser $currentUser, int $post, string $markdown, string $name, string $contact): Comment {
		require_once 'formatText.php';
		$html = FormatText::Markdown($markdown);
		$userID = $currentUser->IsLoggedIn() ? $currentUser->ID : null;
		$now = time();
		try {
			$insert = $db->prepare('insert into comment (instant, post, user, name, contact, html, markdown) values (from_unixtime(?), ?, ?, ?, ?, ?, ?)');
			$insert->bind_param('iiissss', $now, $post, $userID, $name, $contact, $html, $markdown);
			$insert->execute();
			return new self($currentUser, $insert->insert_id, $now, $html, $markdown, $userID, new Author($currentUser->Username, $currentUser->DisplayName, $name, $contact, $currentUser->Avatar, $currentUser->Level, false));
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving comment', $mse);
		}
	}

	public static function Update(mysqli $db, CurrentUser $currentUser, int $id, string $markdown, bool $stealth): string {
		require_once 'formatText.php';
		$html = FormatText::Markdown($markdown);
		$userID = $currentUser->ID;
		$isAdmin = +$currentUser->IsAdmin();
		try {
			$update = $db->prepare('update comment set markdown=?, html=? where id=? and (user=? or ?) limit 1');
			$update->bind_param('ssiii', $markdown, $html, $id, $userID, $isAdmin);
			$update->execute();
			if ($update->affected_rows) {
				if (!$stealth) {
					$insert = $db->prepare('insert into edit (comment, instant, user) values (?, now(), ?)');
					$insert->bind_param('ii', $id, $userID);
					$insert->execute();
				}
				return $html;
			} else
				throw new DetailedException('comment not updated.  this could mean you saved without changing, this isn’t your comment, or it no longer exists.');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error updating comment', $mse);
		}
	}

	public static function Delete(mysqli $db, CurrentUser $currentUser, int $id): void {
		$userID = $currentUser->ID;
		$isAdmin = +$currentUser->IsAdmin();
		try {
			$delete = $db->prepare('delete from comment where id=? and (user=? or ?) limit 1');
			$delete->bind_param('iii', $id, $userID, $isAdmin);
			$delete->execute();
			if (!$delete->affected_rows)
				throw new DetailedException('comment not deleted.  this could mean this isn’t your comment or it no longer exists.');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error deleting comment', $mse);
		}
	}
}

class Author {
	public string $Name;
	public string $URL;
	public ?string $Avatar;
	public string $Level;
	public bool $IsFriend;

	public function __construct(?string $username, ?string $displayname, ?string $name, ?string $contact, ?string $avatar, int $level, bool $isFriend) {
		if ($username && $displayname)
			$this->Name = $displayname;
		elseif ($username)
			$this->Name = $username;
		else
			$this->Name = $name;
		if ($username)
			$this->URL = "/user/$username/";
		else
			$this->URL = $contact;
		$this->Avatar = $avatar;
		$this->Level = $level ? UserLevel::Name($level) : '';
		$this->IsFriend = $isFriend;
	}
}

class Edit {
	public TimeTagData $Instant;
	public string $DisplayName;
	public string $Username;

	public function __construct(CurrentUser $user, int $instant, string $username, ?string $displayname) {
		require_once 'formatDate.php';
		$this->Instant = new TimeTagData($user, FormatDate::LongHTML, $instant);
		$this->Username = $username;
		$this->DisplayName = $displayname ? $displayname : $username;
	}

	public static function Parse(CurrentUser $user, ?string $edits): array {
		$result = [];
		if ($edits)
			foreach (explode("\n", $edits) as $edit) {
				list($instant, $username, $displayname) = explode("\t", $edit);
				$result[] = new self($user, $instant, $username, $displayname);
			}
		return $result;
	}
}

class CommentList {
	public array $Comments = [];
	public bool $HasMore = false;
}
