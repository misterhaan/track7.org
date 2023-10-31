<?php
class Tag {
	public $Name;
	protected $subsite;
	public $Description;

	public function __construct(mysqli $db, string $name, string $subsite) {
		try {
			$select = $db->prepare('select name, subsite, description from tag where name=? and subsite=? limit 1');
			$select->bind_param('ss', $name, $subsite);
			$select->execute();
			$select->bind_result($this->Name, $this->subsite, $this->Description);
			$select->fetch();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error looking up $subsite tag information", $mse);
		}
		return null;
	}

	public static function FromQueryString(mysqli $db, string $subsite): ?Tag {
		if (isset($_GET['tag']) && $_GET['tag'])
			return new Tag($db, $_GET['tag'], $subsite);
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

	public static function UpdateDescription(mysqli $db, string $subsite, string $name, string $description) {
		try {
			$update = $db->prepare('update tag set description=? where subsite=? and name=? limit 1');
			$update->bind_param('sss', $description, $subsite, $name);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException("error updating description for tag $subsite/$name", $mse);
		}
	}
}

class TagFrequency {
	public $Name;
	public $Count;

	public function __construct(string $name, int $count) {
		$this->Name = $name;
		$this->Count = $count;
	}

	public static function List(mysqli $db, string $subsite) {
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
