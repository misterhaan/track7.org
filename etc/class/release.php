<?php
require_once 'environment.php';
require_once 'user.php';
require_once 'formatDate.php';

class Release {
	private const ListLimit = 24;

	public string $Version;
	public TimeTagData $Instant;
	public string $Title;
	public string $Language;
	public float $DotNet;
	public int $VisualStudio;
	public string $Changelog;
	public string $BinURL;
	public string $Bin32URL;
	public string $SourceURL;

	public function __construct(CurrentUser $user, string $version, int $instant, string $name, string $language, string $dotNet, string $visualStudio, string $changelog, string $binURL, string $bin32URL, string $sourceURL) {
		$this->Version = $version;
		$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = "$name v$version";
		$this->Language = $language;
		$this->DotNet = $dotNet;
		$this->VisualStudio = $visualStudio;
		$this->Changelog = $changelog;
		$this->BinURL = $binURL;
		$this->Bin32URL = $bin32URL;
		$this->SourceURL = $sourceURL;
	}

	public static function List(mysqli $db, CurrentUser $user, string $application, int $skip = 0): ReleaseList {
		$limit = self::ListLimit + 1;
		try {
			$select = $db->prepare('select concat(r.major, \'.\', r.minor, \'.\', r.revision), unix_timestamp(r.instant), a.name, r.language, r.dotnet, r.visualstudio, r.changelog, r.binurl, r.bin32url, r.srcurl from `release` as r left join application as a on a.id=r.application where r.application=? order by r.instant desc limit ? offset ?');
			$select->bind_param('sii', $application, $limit, $skip);
			$select->execute();
			$select->bind_result($version, $instant, $name, $language, $dotnet, $visualstudio, $changelog, $binurl, $bin32url, $srcurl);
			$result = new ReleaseList();
			while ($select->fetch())
				if (count($result->Releases) < self::ListLimit)
					$result->Releases[] = new self($user, $version, $instant, $name, $language, +$dotnet, $visualstudio, $changelog, $binurl, $bin32url, $srcurl);
				else
					$result->HasMore = true;
			return $result;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up releases', $mse);
		}
	}
}

/**
 * Partial list of releases
 */
class ReleaseList {
	/**
	 * @var Release[] Group of releases loaded
	 */
	public array $Releases = [];
	/**
	 * Whether there are more releases to load
	 */
	public bool $HasMore = false;
}

class NewRelease {
	public string $Application;
	public string $Version;
	public int $Instant;
	public string $Language;
	public ?float $DotNet;
	public int $VisualStudio;
	public string $Changelog;
	public string $BinURL;
	public string $Bin32URL;
	public string $SourceURL;

	public function __construct(string $application, string $version, int $instant, string $language, ?float $dotNet, int $visualStudio, string $changelog, string $binURL, string $bin32URL, string $sourceURL) {
		$this->Application = $application;
		$this->Version = $version;
		$this->Instant = $instant;
		$this->Language = $language;
		$this->DotNet = $dotNet;
		$this->VisualStudio = $visualStudio;
		$this->Changelog = $changelog;
		$this->BinURL = $binURL;
		$this->Bin32URL = $bin32URL;
		$this->SourceURL = $sourceURL;
	}

	public static function FromPOST(mysqli $db, CurrentUser $user, $application): self {
		if (!isset($_POST['version'], $_POST['language'], $_POST['visualstudio'], $_POST['binurl']))
			throw new DetailedException('version, language, visualstudio, and binurl are required');

		if (!($version = trim($_POST['version'])))
			throw new DetailedException('version cannot be blank');
		$validVersion = self::VersionAvailable($db, $user, $application, $version);
		if ($validVersion->State == 'invalid')
			throw new DetailedException($validVersion->Message);

		if (isset($_POST['instant']) && $instant = trim($_POST['instant'])) {
			require_once 'formatDate.php';
			$instant = FormatDate::LocalToTimestamp($instant, $user);
		}
		if (!$instant)
			$instant = time();

		$language = trim($_POST['language']);
		if ($language != 'c#' && $language != 'vb')
			throw new DetailedException('language must be either c# or vb');

		if (!(isset($_POST['dotnet']) && $dotnet = +$_POST['dotnet']))
			$dotnet = null;

		$visualstudio = +$_POST['visualstudio'];

		if (isset($_POST['changelog'])) {
			require_once 'formatText.php';
			$changelog = FormatText::Markdown(trim($_POST['changelog']));
		} else
			$changelog = '';

		$binurl = trim($_POST['binurl']);
		if (!$binurl)
			throw new DetailedException('binurl cannot be blank');

		if (isset($_POST['bin32url']))
			$bin32url = trim($_POST['bin32url']);
		else
			$bin32url = '';

		if (isset($_POST['srcurl']))
			$srcurl = trim($_POST['srcurl']);
		else
			$srcurl = '';

		return new self($application, $version, $instant, $language, $dotnet, $visualstudio, $changelog, $binurl, $bin32url, $srcurl);
	}

	public static function VersionAvailable(mysqli $db, CurrentUser $user, string $application, string $version): ValidationResult {
		$parts = explode('.', $version);
		$major = +array_shift($parts);
		$minor = +array_shift($parts);
		$revision = +array_shift($parts);
		$version = "$major.$minor.$revision";
		try {
			$select = $db->prepare('select unix_timestamp(r.instant) from `release` as r left join application as a on a.id=r.application where a.id=? and r.major=? and r.minor=? and r.revision=? limit 1');
			$select->bind_param('siii', $application, $major, $minor, $revision);
			$select->execute();
			$select->bind_result($timestamp);
			if ($select->fetch())
				return new ValidationResult('invalid', "“{$version}” already released " . FormatDate::HowLongAgo($timestamp) . ' ago.', $version);
			return new ValidationResult('valid', '', $version);
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error checking release version', $mse);
		}
	}

	public function Save(mysqli $db): void {
		$parts = explode('.', $this->Version);
		$major = +array_shift($parts);
		$minor = +array_shift($parts);
		$revision = +array_shift($parts);
		$version = "$major.$minor.$revision";
		try {
			$db->begin_transaction();

			$insert = $db->prepare('insert into `release` (application, major, minor, revision, instant, language, dotnet, visualstudio, changelog, binurl, bin32url, srcurl) values (?, ?, ?, ?, from_unixtime(?), ?, ?, ?, ?, ?, ?, ?)');
			$insert->bind_param('siiiisdissss', $this->Application, $major, $minor, $revision, $this->Instant, $this->Language, $this->DotNet, $this->VisualStudio, $this->Changelog, $this->BinURL, $this->Bin32URL, $this->SourceURL);
			$insert->execute();

			$update = $db->prepare('update post as p inner join application as a on a.post=p.id set title=concat(a.name, \' v\', ?), instant=from_unixtime(?), preview=? where a.id=?');
			$update->bind_param('siss', $version, $this->Instant, $this->Changelog, $this->Application);
			$update->execute();

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error saving new release', $mse);
		}
	}
}
