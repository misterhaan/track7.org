<?php
define('MAX_LEGO', 24);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'legos':
			$legoq = 'select url, title, posted from lego_models';
			if(isset($_GET['before']) && +$_GET['before'])
				$legoq .= ' where posted<\'' . +$_GET['before'] . '\'';
			$legoq .= ' order by posted desc, id desc limit ' . MAX_LEGO;
			$ajax->Data->legos = [];
			if($legos = $db->query($legoq))
				while($lego = $legos->fetch_object()) {
					$posted = t7format::TimeTag('M j, Y', $lego->posted, 'g:i a \o\n l F jS Y');
					$posted->timestamp = $lego->posted;
					$lego->posted = $posted;
					$ajax->Data->legos[] = $lego;
				}
			break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true, 'bodytype' => 'gallery', 'rss' => ['title' => 'legos', 'url' => dirname($_SERVER['PHP_SELF']) . '/feed.rss']]);
$html->Open('original lego models');
?>
			<h1>
				original lego models
				<a class=feed href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/feed.rss" title="rss feed of lego models"></a>
			</h1>

<?php
if($user->IsAdmin()) {
?>
			<nav class=actions><a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php" class=new>add lego model</a></nav>
<?php
}
?>
			<p>
				these lego models are <a href="/user/misterhaan/">my</a> own original
				creations.  each has step-by-step instructions and <a href="http://www.ldraw.org/">ldraw</a>
				model data file available for download.
			</p>

			<ul class=errors data-bind="visible: errors().length, foreach: errors">
				<li data-bind="text: $data"></li>
			</ul>

			<p data-bind="visible: !legos().length && !loadingLegos()">
				this gallery is empty!
			</p>

			<ol id=legogallery class=gallery data-bind="foreach: legos, visible: legos().length">
				<li>
					<a class="lego thumb" data-bind="attr: {href: url}">
						<img data-bind="attr: {src: '/lego/data/' + url + '-thumb.png'}">
						<span class=caption data-bind="text: title"></span>
						<!-- TODO:  show pieces, rating, and post date -->
					</a>
				</li>
			</ol>

			<p class=loading data-bind="visible: loadingLegos">loading more legos . . .</p>
			<p class="more calltoaction" data-bind="visible: hasMoreLegos"><a class="action get" href=#nextpage data-bind="click: LoadLegos">load more legos</a></p>
<?php
$html->Close();
