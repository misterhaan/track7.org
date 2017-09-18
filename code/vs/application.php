<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$app = false;
if(isset($_GET['url']) && $app = $db->query('select id, url, name, deschtml, github, wiki from code_vs_applications where url=\'' . $db->escape_string($_GET['url']) . '\' limit 1'))
	$app = $app->fetch_object();
if(!$app) { // TODO:  make sure there’s at least one release   || $entry->status != 'published' && !$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('application not found - software');
?>
			<h1>404 application not found</h1>

			<p>
				sorry, we don’t seem to have an application by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all applications</a>.
			</p>
<?php
	$html->Close();
	die;
}

$html = new t7html(['ko' => true]);
$html->Open(htmlspecialchars($app->name));
?>
			<h1>
				<img class=icon src="files/<?php echo $app->url; ?>.png" alt="">
				<?php echo htmlspecialchars($app->name); ?>
			</h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions>
				<a class=edit href="editapp?id=<?php echo $app->id; ?>">edit this application</a>
				<a class=new href="addrel?app=<?php echo $app->id; ?>">add a release</a>
			</nav>
<?php
}
echo $app->deschtml;
if($app->github) {
?>
			<p class=calltoaction><a class="action github" href="https://github.com/misterhaan/<?php echo $app->github; ?>"><?php echo htmlspecialchars($app->name); ?> on github</a></p>
<?php
}
if($app->wiki) {
?>
			<p class=calltoaction><a class="action documentation" href="https://wiki.track7.org/<?php echo $app->wiki; ?>"><?php echo htmlspecialchars($app->name); ?> documentation</a></p>
<?php
}
if($rels = $db->query('select r.released, concat(r.major, \'.\', r.minor, \'.\', r.revision) as version, r.binurl, r.bin32url, r.srcurl, r.changelog, r.lang, r.dotnet, r.studio from (select r.application, r.released, r.major, r.minor, r.revision, r.binurl, r.bin32url, r.srcurl, r.changelog, l.abbr as lang, if(n.version is not null, concat(\'.net \', n.version), \'\') as dotnet, s.name as studio from code_vs_releases as r left join code_vs_lang as l on l.id=r.lang left join code_vs_dotnet as n on n.id=r.dotnet left join code_vs_studio as s on s.version=r.studio where r.application=\'' . +$app->id . '\' order by major desc, minor desc, revision desc) as r group by major, minor order by r.released desc'))
	while($rel = $rels->fetch_object()) {
		$released = t7format::TimeTag('smart', $rel->released, 'Y-m-d g:i:s a');
		if(strpos($rel->binurl, '/') === false)
			$rel->binurl = 'files/' . $rel->binurl;
		if($rel->bin32url && strpos($rel->bin32url, '/') === false)
			$rel->bin32url = 'files/' . $rel->bin32url;
?>
			<article>
				<header>
					<h2>version <?php echo $rel->version; ?></h2>
					<p class=guidemeta>
						<time class=posted datetime="<?php echo $released->datetime; ?>" title="released <?php echo $released->title; ?>"><?php echo $released->display; ?></time>
<?php
		if($rel->dotnet) {
?>
						<span class=dotnet><?php echo $rel->dotnet; ?></span>
<?php
		}
?>
						<span class=lang><?php echo $rel->lang; ?></span>
						<span class=studio><?php echo $rel->studio; ?></span>
					</p>
				</header>
				<?php if($rel->changelog) echo $rel->changelog; ?>
				<nav class="calltoaction downloads">
					<a class="action <?php echo GetExtension($rel->binurl); ?>" href="<?php echo $rel->binurl; ?>"><?php echo GetName($rel->binurl, $rel->version, $rel->bin32url ? 64 : 0); ?></a>
<?php
		if($rel->bin32url) {
?>
					<a class="action <?php echo GetExtension($rel->bin32url); ?>" href="<?php echo $rel->bin32url; ?>"><?php echo GetName($rel->bin32url, $rel->version, 32); ?></a>
<?php
		}
		if($rel->srcurl) {
?>
					<a class="action <?php echo GetCodeType($rel->srcurl); ?>" href="<?php echo $rel->srcurl; ?>">source v<?php echo $rel->version; ?></a>
<?php
		}
?>
				</nav>
			</article>
<?php
	}
$html->ShowComments('application', 'code_vs', $app->id);
$html->Close();

function GetName($url, $version, $bits = 0) {
	$name = '';
	switch(GetExtension($url)) {
		case 'msi':
			$name = 'installer';
			break;
		case 'zip':
			$name = 'binaries';
			break;
	}
	$name .= ' v' . $version;
	if($bits)
		$name .= ' (' . $bits . '-bit)';
	return $name;
}

function GetExtension($filename) {
	$parts = explode('.', $filename);
	return $parts[count($parts) - 1];
}

function GetCodeType($url) {
	if(substr($url, 0, 19) == 'https://github.com/')
		return 'github';
	if(substr($url, 0, 8) == 'https://' || substr($url, 0, 7) == 'http://')
		return 'link';
	return 'zip';
}
