<?php
require_once dirname(__DIR__) . '/etc/class/t7.php';

/**
 * handler for applications api requests.
 * @author misterhaan
 */
class applicationsApi extends t7api {
	/**
	 * write out the documentation for the applications api controller.  the page
	 * is already opened with an h1 header, and will be closed after the call
	 * completes.
	 */
	protected static function ShowDocumentation() {
?>
			<h2 id=postaddrelease>post addrelease</h2>
			<p>
				add a release to an application.  only available to admin.  accepts
				binary uploaded files in binfile, bin32file, and srcfile.
			</p>
			<dl class=parameters>
				<dt>application</dt>
				<dd>application id this release belongs to.  required.</dd>
				<dt>version</dt>
				<dd>version number of the release, formatted #.#.# and required.</dd>
				<dt>released</dt>
				<dd>
					release date.  will be set to current date and time if missing or
					empty.
				</dd>
				<dt>language</dt>
				<dd>primary code language.  required.</dd>
				<dt>dotnet</dt>
				<dd>.net framework version.  required.</dd>
				<dt>studio</dt>
				<dd>visual studio version.  required.</dd>
				<dt>binurl</dt>
				<dd>
					url to the binary (usually an installer) for this release.  required
					if binfile isn’t uploaded.  this is the 64-bit version if one exists.
				</dd>
				<dt>bin32url</dt>
				<dd>
					url to the 32-bit binary (usually an installer) for this release.  if
					blank or missing and bin32file isn’t uploaded, this release will only
					have one binary.
				</dd>
				<dt>srcurl</dt>
				<dd>
					url to the source code for this release.  for new releases this is
					normally a github url but older code might be svn.  required if
					srcfile isn’t uploaded.
				</dd>
				<dt>changelog</dt>
				<dd>
					list of changes in this release in markdown format.  usually this is
					a bulleted list.  required.
				</dd>
			</dl>

			<h2 id=getedit>get edit</h2>
			<p>get application information for editing.  only available to admin.</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>application id to load for editing.</dd>
			</dl>

			<h2 id=postsave>post save</h2>
			<p>
				save edits to an application or add a new application.  only available
				to admin. accepts image file upload named "icon" which is required for
				new applications and optional for existing (will overwrite old icon
				image if specified).
			</p>
			<dl class=parameters>
				<dt>id</dt>
				<dd>
					application id to save.  will add new application if empty or missing.
				</dd>
				<dt>name</dt>
				<dd>application name as plain text.  required.</dd>
				<dt>url</dt>
				<dd>
					url portion specific to this application.  will be set from name if
					empty or missing.
				</dd>
				<dt>desc</dt>
				<dd>description of the application in markdown format.  required.</dd>
				<dt>github</dt>
				<dd>
					url portion specific to this application on github.  optional; will
					not link to github unless present.
				</dd>
				<dt>wiki</dt>
				<dd>
					url portion specific to this application on auwiki.  optional; will
					not link to auwiki unless present.
				</dd>
				<dt>originalurl</dt>
				<dd>
					when editing an existing application, this value is compared against
					url.  if they’re different, the files get renamed.
				</dd>
			</dl>

			<h2 id=getvalidateversion>post validateversion</h2>
			<p>
				validate that a version hasn’t already been used for this application.
			</p>
			<dl class=parameters>
				<dt>appid</dt>
				<dd>id of the application this version is for.  required.</dd>
				<dt>version</dt>
				<dd>version to check.  required.</dd>
			</dl>

<?php
	}

	/**
	 * add a new release to an application.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function addreleaseAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['app']) && +$_POST['app'] && isset($_POST['language']) && +$_POST['language'] && isset($_POST['studio']) && +$_POST['studio'])
				if($app = $db->query('select id, url from code_vs_applications where id=\'' . +$_POST['app'] . '\' limit 1'))
					if($app = $app->fetch_object()) {
						if(isset($_POST['version']) && $ver = self::CheckVersion(trim($_POST['version']), +$app->id, $ajax)) {
							$version = implode('.', $ver);
							$filenamebase = $app->url . '-v' . $version;
							if($binurl = self::GetFileUrl('bin', $filenamebase)) {
								$bin32url = self::GetFileUrl('bin32', $filenamebase);
								if($srcurl = self::GetFileUrl('src', $filenamebase)) {
									$ajax->Data->ver = $ver;  // TODO:  remove debug
									$released = $_POST['released'] ? t7format::LocalStrtotime($_POST['released']) : time();
									$language = +$_POST['language'];
									$dotnet = isset($_POST['dotnet']) && +$_POST['dotnet'] ? +$_POST['dotnet'] : '';
									$studio = +$_POST['studio'];
									$ins = 'insert into code_vs_releases set application=\'' . +$app->id
										. '\', released=\'' . +$released . '\', major=\'' . +$ver[0]
										. '\', minor=\'' . +$ver[1] . '\', revision=\'' . +$ver[2]
										. '\', lang=\'' . $language
										. '\', dotnet=nullif(\'' . $dotnet . '\',\'\'), studio=\'' . $studio
										. '\', binurl=\'' . $db->escape_string($binurl)
										. '\', bin32url=nullif(\'' . $db->escape_string($bin32url)
										. '\',\'\'), srcurl=\'' . $db->escape_string($srcurl)
										. '\', changelog=\'' . $db->escape_string(t7format::Markdown($_POST['changelog'])) . '\'';
									$ajax->Data->sql = $ins;  // TODO:  remove debug
									if($db->real_query($ins)) {
										$ajax->Data->url = $app->url;
										if(time() - $released < 604800)  // within the last week
											t7send::Tweet($app->url . ' v' . $version . ' released', t7format::FullUrl('/code/vs/' . $app->url));
									} else
										$ajax->Fail('error saving release to database', $db->errno . ' ' . $db->error);
								} else
									$ajax->Fail('source code url or file is required.');
							} else
								$ajax->Fail('binary url or file is required.');
						}  // CheckVersion adds its own failure message
					} else
						$ajax->Fail('application not found');
				else
					$ajax->Fail('database error looking up application', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('app, language, and studio are required.');
		else
			$ajax->Fail('only the administrator can add application releases.  you might need to log in again.');
	}

	/**
	 * get application information for editing.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function editAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_GET['id']) && $_GET['id'] == +$_GET['id'])
				if($app = $db->query('select url, name, descmd, github, wiki from code_vs_applications where id=\'' . +$_GET['id'] . '\' limit 1'))
					if($app = $app->fetch_object())
						$ajax->MergeData($app);
					else
						$ajax->Fail('cannot find application.');
				else
					$ajax->Fail('database error looking up application for editing', $db->errno . ' ' . $db->error);
			else
				$ajax->Fail('numeric id required to edit application.');
		else
			$ajax->Fail('only the administrator can edit applications.  you might need to log in again.');
	}

	/**
	 * save changes to an application or add a new application.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function saveAction($ajax) {
		global $db, $user;
		if($user->IsAdmin())
			if(isset($_POST['name']) && trim($_POST['name']) && isset($_POST['desc']) && trim($_POST['desc'])) {
				$id = isset($_POST['id']) ? +$_POST['id'] : false;
				if($id || isset($_FILES['icon']) && $_FILES['icon']['size']) {
					$name = trim($_POST['name']);
					$url = isset($_POST['url']) && trim($_POST['url']) ? trim($_POST['url']) : t7format::NameToUrl($name);
					if(self::CheckUrl('code_vs_applications', 'name', $url, $id, $ajax)) {
						$path = $_SERVER['DOCUMENT_ROOT'] . '/code/vs/files/';
						if(isset($_FILES['icon']) && $_FILES['icon']['size'])
							t7file::SaveUploadedImage($_FILES['icon'], 'png', [$path . $url . '.png']);
						$desc = trim($_POST['desc']);
						$github = isset($_POST['github']) && trim($_POST['github']) ? trim($_POST['github']) : '';
						$wiki = isset($_POST['wiki']) && trim($_POST['wiki']) ? trim($_POST['wiki']) : '';
						$ins = 'code_vs_applications set name=\'' . $db->escape_string($name) . '\', url=\'' . $db->escape_string($url) . '\', descmd=\'' . $db->escape_string($desc) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown($desc)) . '\', github=\'' . $db->escape_string($github) . '\', wiki=\'' . $db->escape_string($wiki) . '\'';
						$ins = $id
							? 'update ' . $ins . ' where id=\'' . +$id . '\' limit 1'
							: 'insert into ' . $ins;
						if($db->real_query($ins)) {
							$ajax->Data->url = $url;
							if($id && $url != $_POST['originalurl'] && $_POST['originalurl'] == t7format::NameToUrl($_POST['originalurl']))
								if(isset($_FILES['icon']) && $_FILES['icon']['size'])
									unlink($path . $_POST['originalurl'] . '.png');
								else
									rename($path . $_POST['originalurl'] . '.png', $path . $url . '.png');
						} else
							$ajax->Fail('database error saving application', $db->errno . ' ' . $db_>error);
					}  // CheckUrl adds its own failure message
				} else
					$ajax->Fail('icon image file must be included with new applications.');
			} else
				$ajax->Fail('name and desc are required.');
		else
			$ajax->Fail('only the administrator can edit applications.  you might need to log in again.');
	}

	/**
	 * check if a version is valid for an application.
	 * @param t7ajax $ajax ajax object for returning data or reporting an error.
	 */
	protected static function validateversionAction($ajax) {
		global $db;
		if(isset($_GET['value']) && isset($_GET['id']) && +$_GET['id'])
			self::CheckVersion(trim($_GET['value']), +$_GET['id'], $ajax);
		else
			$ajax->Fail('value and id are required.');
	}

	private static function CheckVersion($version, $appid, $ajax) {
		global $db;
		if(preg_match('/^[0-9]+(\.[0-9]+){0,2}$/', $version)) {
			$ver = explode('.', $version);
			while(count($ver) < 3)
				$ver[] = 0;
			if($dupe = $db->query('select released from code_vs_releases where application=\'' . $appid . '\' and major=\'' . +$ver[0] . '\' and minor=\'' . +$ver[1] . '\' and revision=\'' . +$ver[2] . '\''))
				if($dupe = $dupe->fetch_object())
					$ajax->Fail('version ' . $version . ' was already released on ' . t7format::SmartDate($dupe->released));
				else
					return $ver;
			else
				$ajax->Fail('database error checking for duplicate version', $db->errno . ' ' . $db->error);
		} else
			$ajax->Fail('version must be 1 to 3 numbers separated by dots.');
		return false;
	}

	private static function GetFileUrl($type, $base) {
		return isset($_POST[$type . 'url']) && trim($_POST[$type . 'url']) ? trim($_POST[$type . 'url']) : self::SaveUpload($type, $base);
	}

	private static function SaveUpload($type, $base) {
		if(isset($_FILES[$type . 'file']) && $_FILES[$type . 'file']['size']) {
			$filename = $base;
			switch($type) {
				case 'bin':
					if(isset($_POST['bin32url']) && trim($_POST['bin32url']) || isset($_FILES['bin32file']) && $_FILES['bin32file']['size'])
						$filename .= '_x64';
					break;
				case 'bin32':
					$filename .= '_x86';
					break;
				case 'src':
					$filename .= '_source';
			}
			$filename .= '.' . strtolower(self::GetExtension($_FILES[$type . 'file']['name']));
			if(move_uploaded_file($_FILES[$type . 'file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . '/code/vs/files/' . $filename))
				return 'files/' . $filename;
		}
		return '';
	}

	function GetExtension($filename) {
		$parts = explode('.', $filename);
		return $parts[count($parts) - 1];
	}
}
applicationsApi::Respond();
