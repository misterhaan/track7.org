<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$scr = false;
if(isset($_GET['url']))
	if($sel = $db->prepare('select s.id, s.name, u.name as typename, s.released, s.deschtml, s.download, s.github, s.insthtml, s.wiki from code_web_scripts as s left join code_web_usetype as u on u.id=s.usetype where s.url=?'))
		if($sel->bind_param('s', $_GET['url']))
			if($sel->execute())
				if($sel->bind_result($id, $name, $typename, $released, $desc, $download, $github, $inst, $wiki))
					if($sel->fetch()) {
						$sel->close();
						$released = t7format::TimeTag('smart', $released, 'Y-m-d g:i:s a');
						if(!$download)
							$download = 'files/' . $_GET['url'] . '.zip';
						$html = new t7html(['ko' => true]);
						$html->Open(htmlspecialchars($name) . ' - scripts - software');
?>
			<h1><?php echo htmlspecialchars($name); ?></h1>
			<p class=guidemeta>
				<span class=scripttype><?php echo $typename; ?></span>
				<time class=posted title="released <?php echo $released->title; ?>" datetime="<?php echo $released->datetime; ?>"><?php echo $released->display; ?></time>
			</p>
<?php
						if($user->IsAdmin()) {
?>
			<nav class=actions>
				<a class=edit href="editscr?id=<?php echo $id; ?>">edit this script</a>
			</nav>
<?php
						}
						echo $desc;
?>
			<h2>files</h2>
			<p class="downloads">
				<a class="action zip" href="<?php echo $download; ?>">download</a>
<?php
						if($github) {
?>
				<a class="action github" href="https://github.com/misterhaan/<?php echo $github; ?>">github</a>
<?php
						}
?>
			</p>
			<h2>requirements</h2>
			<ul class=requirements>
<?php
						if($getreqs = $db->prepare('select ri.name, ri.url from code_web_requirements as r left join code_web_reqinfo as ri on ri.id=r.req where r.script=? order by ri.name')) {
							if($getreqs->bind_param('i', $id))
								if($getreqs->execute())
									if($getreqs->bind_result($reqname, $requrl))
										while($getreqs->fetch()) {
?>
				<li><a href="<?php echo $requrl; ?>"><?php echo $reqname; ?></a></li>
<?php
										}
									else
										echo '<li class=error>error binding result:  ' . $getreqs->error . '</li>';
								else
									echo '<li class=error>error executing requirements query:  ' . $getreqs->error . '</li>';
							else
								echo '<li class=error>error binding script id:  ' . $getreqs->error . '</li>';
							$getreqs->close();
						} else
							echo '<li class=error>error preparing to look up requirements:  ' . $db->error . '</li>';
?>
			</ul>
<?php
						if($inst || $wiki) {
?>
			<h2>instructions</h2>
<?php
							echo $inst;
							if($wiki) {
?>
			<p class=calltoaction><a class="action wiki" href="https://wiki.track7.org/<?php echo $wiki; ?>">read more on the track7 wiki</a></p>
<?php
							}
						}
						$html->ShowComments('script', 'code_web', $id);
						$html->Close();
						die;
					}
header('HTTP/1.0 404 Not Found');
$html = new t7html([]);
$html->Open('script not found - software');
?>
			<h1>404 script not found</h1>

			<p>
				sorry, we don’t seem to have a script by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all scripts</a>.
			</p>
<?php
$html->Close();