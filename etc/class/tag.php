<?php
class Tag {
	public string $Name;
	protected string $subsite;

	public function __construct(mysqli $db, string $name, string $subsite) {
		try {
			$select = $db->prepare('select name, subsite from tag where name=? and subsite=? limit 1');
			$select->bind_param('ss', $name, $subsite);
			$select->execute();
			$select->bind_result($name, $subsite);
			if ($select->fetch()) {
				$this->Name = $name;
				$this->subsite = $subsite;
			} else
				throw new DetailedException('tag not found.');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error verifying $subsite/$name exists", $mse);
		}
	}

	public static function FromQueryString(mysqli $db, string $subsite): ?Tag {
		if (isset($_GET['tag']) && $_GET['tag'])
			return new Tag($db, $_GET['tag'], $subsite);
		return null;
	}

	/**
	 * Look up all the tags for a post
	 * @param $db Database connection
	 * @param $post Post ID to look up
	 * @return string[] Tag names for the specified post
	 */
	public static function ForPost(mysqli $db, int $post): array {
		try {
			$select = $db->prepare('select tag from post_tag where post=?');
			$select->bind_param('i', $post);
			$select->execute();
			$select->bind_result($tag);
			$tags = [];
			while ($select->fetch())
				$tags[] = $tag;
			return $tags;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error looking up tags for post #$post", $mse);
		}
	}

	/**
	 * Remove tags from a post
	 * @param $db Database connection
	 * @param $post Post ID tags should be removed from
	 * @param $tagList Comma-separated list of tags to remove
	 */
	public static function RemoveFromPost(mysqli $db, int $post, string $tagList): void {
		try {
			$delete = $db->prepare('delete from post_tag where post=? and tag=?');
			$delete->bind_param('is', $post, $tag);
			foreach (explode(',', $tagList) as $tag)
				$delete->execute();
			$delete->close();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error removing tags from post #$post", $mse);
		}
	}

	/**
	 * Add tags to a post
	 * @param $db Database connection
	 * @param $post Post ID tags should be added to
	 * @param $subsite Subsite these tags are used in (only matters if tags haven't already been used)
	 * @param $tagList Comma-separated list of tags to add
	 */
	public static function AddToPost(mysqli $db, int $post, string $subsite, string $tagList): void {
		$tags = explode(',', $tagList);
		try {
			$insert = $db->prepare('insert ignore into tag (name, subsite) values (?, ?)');
			$insert->bind_param('ss', $tag, $subsite);
			foreach ($tags as $tag)
				if ($tag)
					$insert->execute();
			$insert->close();

			$insert = $db->prepare('insert into post_tag (post, tag) values (?, ?)');
			$insert->bind_param('is', $post, $tag);
			foreach ($tags as $tag)
				if ($tag)
					$insert->execute();
			$insert->close();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error adding tags to post #$post", $mse);
		}
	}
}

class ActiveTag extends Tag {
	public string $Description;

	public function __construct(mysqli $db, string $name, string $subsite) {
		try {
			$select = $db->prepare('select name, subsite, description from tag where name=? and subsite=? limit 1');
			$select->bind_param('ss', $name, $subsite);
			$select->execute();
			$select->bind_result($name, $subsite, $description);
			if ($select->fetch()) {
				$this->Name = $name;
				$this->subsite = $subsite;
				$this->Description = $description;
			} else
				throw new DetailedException('tag not found.');
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error looking up $subsite tag information", $mse);
		}
	}

	public static function FromQueryString(mysqli $db, string $subsite): ?ActiveTag {
		if (isset($_GET['tag']) && $_GET['tag'])
			return new ActiveTag($db, $_GET['tag'], $subsite);
		return null;
	}

	public function ShowInfo(bool $isAdmin = false) {
?>
		<div id=taginfo data-subsite=<?= $this->subsite; ?> data-name=<?= $this->Name; ?>>
			<?php
			if ($isAdmin) {
			?>
				<label class=multiline id=editdesc v-if=editing>
					<span class=field><textarea v-model=description ref=editField></textarea></span>
					<span>
						<a href="#save" title="save tag description" class="action okay" @click.prevent=SaveEdit :disabled=saving :class="{working: saving}"></a>
						<a href="#cancel" title="cancel editing" class="action cancel" @click.prevent=CancelEdit></a>
					</span>
				</label>
			<?php
			}
			?>
			<div class=editable v-html=description v-if=!editing><?= $this->Description; ?></div>
		</div>
		<p>go back to <a href="/<?= $this->subsite; ?>/">everything</a>.</p>

<?php
	}

	public static function UpdateDescription(mysqli $db, string $subsite, string $name, string $description): void {
		try {
			$update = $db->prepare('update tag set description=? where subsite=? and name=? limit 1');
			$update->bind_param('sss', $description, $subsite, $name);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error updating description for tag $subsite/$name", $mse);
		}
	}
}

class TagFrequency extends Tag {
	public int $Count;

	public function __construct(string $name, int $count) {
		$this->Name = $name;
		$this->Count = $count;
	}

	public static function List(mysqli $db, string $subsite): array {
		try {
			$select = $db->prepare('select tag as name, count from tagusage where count>1 and subsite=? order by lastused desc');
			$select->bind_param('s', $subsite);
			$select->execute();
			$select->bind_result($name, $count);
			$tags = [];
			while ($select->fetch())
				$tags[] = new TagFrequency($name, $count);
			return $tags;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error looking up $subsite tags", $mse);
		}
	}
}

class TagStatistics extends Tag {
	public string $Description;
	public int $Count;
	public TimeTagData $LastUsed;

	public function __construct(CurrentUser $user, string $name, string $description, int $count, int $lastUsed) {
		$this->Name = $name;
		$this->Description = $description;
		$this->Count = $count;
		require_once 'formatDate.php';
		$this->LastUsed = new TimeTagData($user, 'ago', $lastUsed, FormatDate::Long);
	}

	public static function List(mysqli $db, CurrentUser $user, string $subsite): array {
		try {
			$select = $db->prepare('select t.name, t.description, u.count, unix_timestamp(u.lastused) from tag as t left join tagusage as u on u.tag=t.name and u.subsite=t.subsite where t.subsite=? order by lastused desc');
			$select->bind_param('s', $subsite);
			$select->execute();
			$select->bind_result($name, $description, $count, $lastused);
			$tags = [];
			while ($select->fetch())
				$tags[] = new TagStatistics($user, $name, $description, $count, $lastused);
			return $tags;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error looking up $subsite tags", $mse);
		}
	}
}
