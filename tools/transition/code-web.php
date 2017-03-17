<?php
define('TR_WEB_SCRIPTS', 9);
define('STEP_CREATE_FILES_DIR', 1);
define('STEP_COPY_COMMENTS', 2);

require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('web script migration');
?>
			<h1>web script migration</h1>
<?php
if(isset($_GET['dostep']))
	switch($_GET['dostep']) {
		case 'createdir':
			$path = $_SERVER['DOCUMENT_ROOT'] . '/code/web/files';
			if(is_dir($path) || mkdir($path, 0775))
				$db->real_query('update transition_status set stepnum=' . STEP_CREATE_FILES_DIR . ', status=\'upload directory created\' where id=\'' . TR_WEB_SCRIPTS . '\' and stepnum<' . STEP_CREATE_FILES_DIR);
			break;
		case 'copycomments':
			if($db->real_query('insert into code_web_comments (script, posted, user, name, contacturl, html) select s.id, c.instant, u.id, c.name, c.url, replace(c.comments, \'&nbsp;\', \' \') from track7_t7data.comments as c inner join code_web_scripts as s on s.url=substring_index(c.page,\'/\',-1) left join transition_users as u on u.olduid=c.uid where page like \'/analogu/scripts/%\' and not page=\'/analogu/scripts/\' order by c.instant'))
				$db->real_query('update transition_status set stepnum=' . STEP_COPY_COMMENTS . ', status=\'comments copied\' where id=\'' . TR_WEB_SCRIPTS . '\' and stepnum<' . STEP_COPY_COMMENTS);
			else
				echo '<pre><code>' . $db->error . '</code></pre>';
			break;
	}

if($status = $db->query('select stepnum, status from transition_status where id=' . TR_WEB_SCRIPTS))
	$status = $status->fetch_object();
?>
			<h2>upload directory</h2>
<?php
if($status->stepnum < STEP_CREATE_FILES_DIR) {
?>
			<p>
				git doesn’t support empty directories, so before uploads can happen the
				directory needs to be created.
			</p>
			<nav class=calltoaction><a class="new action" href="?dostep=createdir">create that directory!</a></nav>
<?php
} else {
?>
			<p>upload directory has been created.</p>

			<h2>comments</h2>
<?php
	if($status->stepnum < STEP_COPY_COMMENTS) {
?>
			<p>
				once the web scripts have been added to the new site, the comments can
				be copied.  everything else needs to be done first.
			</p>
			<nav class=calltoaction><a class="copy action" href="?dostep=copycomments">copy comments</a></nav>
<?php
	} else {
?>
			<p>comments migrated successfully.</p>
<?php
	}
}
$html->Close();
