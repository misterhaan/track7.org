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
				<a href="<?=dirname($_SERVER['SCRIPT_NAME']); ?>/">all applications</a>.
			</p>
<?php
	$html->Close();
	die;
}

$html = new t7html(['vue' => true]);
$html->Open(htmlspecialchars($app->name));
?>
			<h1>
				<img class=icon src="files/<?=$app->url; ?>.png" alt="">
				<?=htmlspecialchars($app->name); ?>
			</h1>
<?php
if($user->IsAdmin()) {
?>
			<nav class=actions>
				<a class=edit href="editapp?id=<?=$app->id; ?>">edit this application</a>
				<a class=new href="addrel?app=<?=$app->id; ?>">add a release</a>
			</nav>
<?php
}
echo $app->deschtml;
if($app->github) {
?>
			<p><a class="action github" href="https://github.com/misterhaan/<?=$app->github; ?>"><?=htmlspecialchars($app->name); ?> on github</a></p>
<?php
}
if($app->wiki) {
?>
			<p><a class="action documentation" href="https://wiki.track7.org/<?=$app->wiki; ?>"><?=htmlspecialchars($app->name); ?> documentation</a></p>
<?php
}

$appid = +$app->id;
if($rels = $db->prepare('call ListApplicationReleases(?)'))
	if($rels->bind_param('i', $appid))
		if($rels->execute())
			if($releases = $rels->get_result())
				while($rel = $releases->fetch_object()) {
					$released = t7format::TimeTag('smart', $rel->released, 'Y-m-d g:i:s a');
					if(strpos($rel->binurl, '/') === false)
						$rel->binurl = 'files/' . $rel->binurl;
					if($rel->bin32url && strpos($rel->bin32url, '/') === false)
						$rel->bin32url = 'files/' . $rel->bin32url;
?>
			<article>
				<header>
					<h2>version <?=$rel->version; ?></h2>
					<p class=meta>
						<time class=posted datetime="<?=$released->datetime; ?>" title="released <?=$released->title; ?>"><?=$released->display; ?></time>
<?php
					if($rel->dotnet) {
?>
						<span class=dotnet><?=$rel->dotnet; ?></span>
<?php
					}
?>
						<span class=lang><?=$rel->lang; ?></span>
						<span class=studio><?=$rel->studio; ?></span>
					</p>
				</header>
				<?php if($rel->changelog) echo $rel->changelog; ?>
				<nav class="downloads">
					<a class="action <?=GetExtension($rel->binurl); ?>" href="<?=$rel->binurl; ?>"><?=GetName($rel->binurl, $rel->version, $rel->bin32url ? 64 : 0); ?></a>
<?php
					if($rel->bin32url) {
?>
					<a class="action <?=GetExtension($rel->bin32url); ?>" href="<?=$rel->bin32url; ?>"><?=GetName($rel->bin32url, $rel->version, 32); ?></a>
<?php
					}
					if($rel->srcurl) {
?>
					<a class="action <?=GetCodeType($rel->srcurl); ?>" href="<?=$rel->srcurl; ?>">source v<?=$rel->version; ?></a>
<?php
					}
?>
				</nav>
			</article>
<?php
				}
			else
				$html->ShowError('error getting results from looking uo releases', $rels->errno, $rels->error);
		else
			$html->ShowError('error executing query to look up releases', $rels->errno, $rels->error);
	else
		$html->ShowError('error binding application id to look up releases', $rels->errno, $rels->error);
else
	$html->ShowError('error preparing to look up releases', $db->errno, $db->error);

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
