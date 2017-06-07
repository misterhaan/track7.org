<?php
define('MAX_ART_SIZE', 800);
define('MAX_THUMB_SIZE', 150);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	if(isset($_GET['ajax'])) {
		$ajax = new t7ajax();
		$ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
		$ajax->Send();
		die;
	}
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('art not found');
?>
			<h1>404 art not found</h1>

			<p>
				sorry, we don’t seem to have any art by that name.  try picking one from
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
			</p>
<?php
	$html->Close();
	die;
}

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'get':
			if(isset($_GET['id']) && $_GET['id'])
				if($art = $db->query('select a.id, a.title, a.url, i.ext, coalesce(nullif(descmd,\'\'),deschtml) as descmd from art as a left join image_formats as i on i.id=a.format where a.id=\'' . +$_GET['id'] . '\''))
					if($art = $art->fetch_object()) {
						$art->tags = [];
						if($tags = $db->query('select t.name from art_taglinks as tl left join art_tags as t on t.id=tl.tag where tl.art=\'' . +$art->id . '\''))
							while($tag = $tags->fetch_object())
								$art->tags[] = $tag->name;
						$ajax->Data = $art;
					} else
						$ajax->Fail('cannot find art.');
				else
					$ajax->Fail('error looking up art details for editing.');
			else
				$ajax->Fail('get requires a url.');
			break;
		case 'save':
			$id = isset($_POST['id']) ? +$_POST['id'] : false;
			if($id || $_FILES['art']['size'])
				if($_POST['title']) {
					if(!$_POST['url'])
						$_POST['url'] = str_replace(' ', '-', $_POST['title']);
					if($unique = $db->query('select url from art where url=\'' . $db->escape_string($_POST['url']) . '\' and id!=\'' . +$id . '\' limit 1'))
						if($unique->num_rows < 1) {
							if($_FILES['art']['size']) {
								$size = getimagesize($_FILES['art']['tmp_name']);
								$aspect = $size[0] / $size[1];
								$ext = false;
								switch($size[2]) {
									case IMAGETYPE_JPEG:
										$image = imagecreatefromjpeg($_FILES['art']['tmp_name']);
										$ext = 'jpg';
										break;
									case IMAGETYPE_GIF:
										$image = imagecreatefromgif($_FILES['art']['tmp_name']);
										$ext = 'png';  // save gif as png
									case IMAGETYPE_PNG:
										$image = imagecreatefrompng($_FILES['art']['tmp_name']);
										$ext = 'png';
										break;
									default:
										$ajax->Fail('unknown image type.');
										$ajax->Send();
										die;
										break;
								}
								if($size[0] > MAX_ART_SIZE || $size[1] > MAX_ART_SIZE) {
									if($aspect > 1) {
										$width = MAX_ART_SIZE;
										$height = round(MAX_ART_SIZE / $aspect);
									} else {
										$height = MAX_ART_SIZE;
										$width = round(MAX_ART_SIZE * $aspect);
									}
									$fullsize = imagecreatetruecolor($width, $height);
									if($ext == 'png') {
										imagealphablending($fullsize, false);
										imagesavealpha($fullsize, true);
									}
									imagecopyresampled($fullsize, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
									switch($ext) {
										case 'jpg':
											imagejpeg($fullsize, dirname($_SERVER['SCRIPT_FILENAME']) . '/img/' . $_POST['url'] . '.jpg');
											break;
										case 'png':
											imagepng($fullsize, dirname($_SERVER['SCRIPT_FILENAME']) . '/img/' . $_POST['url'] . '.png');
											break;
									}
									imagedestroy($fullsize);
								} else
									switch($ext) {
										case 'jpg':
											imagejpeg($image, dirname($_SERVER['SCRIPT_FILENAME']) . '/img/' . $_POST['url'] . '.jpg');
											break;
										case 'png':
											imagepng($image, dirname($_SERVER['SCRIPT_FILENAME']) . '/img/' . $_POST['url'] . '.png');
											break;
									}
								if($aspect > 1) {
									$w = MAX_THUMB_SIZE;
									$h = round(MAX_THUMB_SIZE / $aspect);
								} else {
									$h = MAX_THUMB_SIZE;
									$w = round(MAX_THUMB_SIZE * $aspect);
								}
								$thumb = imagecreatetruecolor($w, $h);
								if($ext == 'png') {
									imagealphablending($thumb, false);
									imagesavealpha($thumb, true);
								}
								imagecopyresampled($thumb, $image, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);
								switch($ext) {
									case 'jpg':
										imagejpeg($thumb, dirname($_SERVER['SCRIPT_FILENAME']) . '/img/' . $_POST['url'] . '-prev.jpg');
										break;
									case 'png':
										imagepng($thumb, dirname($_SERVER['SCRIPT_FILENAME']) . '/img/' . $_POST['url'] . '-prev.png');
										break;
								}
								imagedestroy($thumb);
								imagedestroy($image);
								unlink($_FILES['art']['tmp_name']);
							}
							$q = 'art set title=\'' . $db->escape_string($_POST['title']) . '\', url=\'' . $db->escape_string(trim($_POST['url'])) . '\', ' . ($_FILES['art']['size'] ? 'format=(select id from image_formats where ext=\'' . $ext . '\'), ' : '') . 'descmd=\'' . $db->escape_string(trim($_POST['descmd'])) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown(trim($_POST['descmd']))) . '\'';
							$q = $id ? 'update ' . $q . ' where id=\'' . +$id . '\' limit 1' : 'insert into ' . $q . ', posted=\'' . +time() . '\'';
							if($db->real_query($q)) {
								if(!$id) {
									$id = $db->insert_id;
									t7send::Tweet('new art: ' . $_POST['title'], t7format::FullUrl('/art/' . $_POST['url']));
								}
								$_POST['taglist'] = explode(',', $_POST['taglist']);
								$_POST['originalTaglist'] = explode(',', $_POST['originalTaglist']);
								$addtags = array_diff($_POST['taglist'], $_POST['originalTaglist']);
								if(count($addtags)) {
									$qat = $db->prepare('insert into art_tags (name) values (?) on duplicate key update id=id');
									$qat->bind_param('s', $name);
									$qlt = $db->prepare('insert into art_taglinks set art=\'' . +$id . '\', tag=(select id from art_tags where name=? limit 1)');
									$qlt->bind_param('s', $name);
									foreach($addtags as $name) {
										if(!$qat->execute())
											$ajax->Fail('error adding tag:  ' . $qat->error);
										if(!$qlt->execute())
											$ajax->Fail('error linking tag:  ' . $qlt->error);
									}
									$qat->close();
									$qlt->close();
								}
								$deltags = array_diff($_POST['originalTaglist'], $_POST['taglist']);
								if(count($deltags))
									$db->real_query('delete from art_taglinks where art=\'' . +$id . '\' and tag in (select id from art_tags where name in (\'' . implode('\', \'', $deltags) . '\'))');
								$tags = array_merge($addtags, $deltags);
								if(count($tags))
									if(!$db->real_query('update art_tags set count=(select count(1) as count from art_taglinks as tl where tl.tag=art_tags.id group by tl.tag), lastused=(select max(a.posted) as lastused from art_taglinks as tl left join art as a on a.id=tl.art where tl.tag=art_tags.id group by tl.tag) where name in (\'' . implode('\', \'', $tags) . '\')'))
										$ajax->Fail('error updating tag stats:  ' . $db->error);
								$ajax->Data->url = dirname($_SERVER['SCRIPT_NAME']) . '/' . $_POST['url'];
							} else
								$ajax->Fail('database error saving art data.' . "\n\n" . $q);
						} else
							$ajax->Fail('url “' . $_POST['url'] . '” already in use.');
					else
						$ajax->Fail('error checking uniqueness of art url.');
				} else
					$ajax->Fail('title is required.');
			else
				$ajax->Fail('image file must be included with new art.');
			break;
	}
	$ajax->Send();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['ko' => true]);
$html->Open(($id ? 'edit' : 'add') . ' art');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> art</h1>
			<form id=editart data-bind="submit: Save">
<?php
if($id) {
?>
				<input type=hidden id=artid name=id value="<?php echo $id ?>">
<?php
}
?>
				<label>
					<span class=label>title:</span>
					<span class=field><input name=title maxlength=32 required data-bind="value: title"></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input id=url name=url maxlength=32 pattern="[a-z0-9\-_]+" data-bind="value: url"></span>
				</label>
				<label title="upload the art" data-bind="visible: !art()">
					<span class=label>art:</span>
					<span class=field>
						<input type=file name=art accept=".jpg, .jpeg, .png, image/jpeg, image/jpg, image/png" data-bind="event: {change: CacheArt}">
					</span>
				</label>
				<label class=multiline title="the art" data-bind="visible: art()">
					<span class=label>art:</span>
					<span class=field>
						<img class="art preview" data-bind="attr: {src: art}">
					</span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=descmd data-bind="value: descmd"></textarea></span>
				</label>
				<label>
					<span class=label>tags:</span>
					<span class=field><input name=taglist pattern="[a-z0-9\.]+(,[a-z0-9\.]+)*?" data-bind="value: tags"></span>
				</label>
				<button id=save>save</button>
				<p data-bind="visible: id()"><img class="art preview" data-bind="attr: {src: url() ? 'img/' + url() + '.' + ext() : ''}"></p>
			</form>
<?php
$html->Close();
