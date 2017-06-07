<?php
define('MAX_LEGO_SIZE', 800);
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
	$html->Open('lego model not found');
?>
			<h1>404 lego model not found</h1>

			<p>
				sorry, we don’t seem to have a lego model by that name.  try picking one
				from <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">the gallery</a>.
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
				if($lego = $db->query('select id, title, url, pieces, coalesce(nullif(descmd,\'\'),deschtml) as descmd from lego_models where id=\'' . +$_GET['id'] . '\''))
					if($lego = $lego->fetch_object())
						$ajax->Data = $lego;
					else
						$ajax->Fail('cannot find lego model.');
				else
					$ajax->Fail('error looking up lego model details for editing.');
			else
				$ajax->Fail('get requires an id.');
			break;
		case 'save':
			if(isset($_POST['id']) && $_POST['id'] || $_FILES['image']['size'] && $_FILES['ldraw']['size'] && $_FILES['instructions']['size'])
				if($_POST['title']) {
					if(!$_POST['url'])
						$_POST['url'] = str_replace(' ', '-', $_POST['title']);
					if($unique = $db->query('select url from lego_models where url=\'' . $db->escape_string($_POST['url']) . '\' and id!=\'' . +(isset($_POST['id']) ? $_POST['id'] : 0) . '\' limit 1'))
						if($unique->num_rows < 1) {
							if($_FILES['image']['size']) {
								$size = getimagesize($_FILES['image']['tmp_name']);
								$aspect = $size[0] / $size[1];
								if($size[2] == IMAGETYPE_PNG) {
									$image = imagecreatefrompng($_FILES['image']['tmp_name']);
									if($size[0] > MAX_LEGO_SIZE || $size[1] > MAX_LEGO_SIZE) {
										if($aspect > 1) {
											$width = MAX_LEGO_SIZE;
											$height = round(MAX_LEGO_SIZE / $aspect);
										} else {
											$height = MAX_LEGO_SIZE;
											$width = round(MAX_LEGO_SIZE * $aspect);
										}
										$fullsize = imagecreatetruecolor($width, $height);
										imagealphablending($fullsize, false);
										imagesavealpha($fullsize, true);
										imagecopyresampled($fullsize, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
										imagepng($fullsize, dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $_POST['url'] . '.png');
										imagedestroy($fullsize);
										unlink($_POST['photo']['tmp_name']);
									} else
										move_uploaded_file($_FILES['image']['tmp_name'], dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $_POST['url'] . '.png');
									if($aspect > 1) {
										$w = MAX_THUMB_SIZE;
										$h = round(MAX_THUMB_SIZE / $aspect);
									} else {
										$h = MAX_THUMB_SIZE;
										$w = round(MAX_THUMB_SIZE * $aspect);
									}
									$thumb = imagecreatetruecolor($w, $h);
									imagealphablending($thumb, false);
									imagesavealpha($thumb, true);
									imagecopyresampled($thumb, $image, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);
									imagepng($thumb, dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $_POST['url'] . '-thumb.png');
									imagedestroy($thumb);
									imagedestroy($image);
								} else {
										$ajax->Fail('image must be png format');
										$ajax->Send();
										die;
								}
							}
							if($_FILES['ldraw']['size'])
								move_uploaded_file($_FILES['ldraw']['tmp_name'], dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $_POST['url'] . '-ldr.zip');
							if($_FILES['instructions']['size'])
								move_uploaded_file($_FILES['instructions']['tmp_name'], dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $_POST['url'] . '-img.zip');
							$q = 'lego_models set title=\'' . $db->escape_string($_POST['title']) . '\', url=\'' . $db->escape_string(trim($_POST['url'])) . '\', ' . 'pieces=\'' . +$_POST['pieces'] . '\', descmd=\'' . $db->escape_string(trim($_POST['descmd'])) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown(trim($_POST['descmd']))) . '\'';
							$q = isset($_POST['id']) && $_POST['id'] ? 'update ' . $q . ' where id=\'' . +$_POST['id'] . '\' limit 1' : 'insert into ' . $q . ', posted=\'' . +time() . '\'';
							if($db->real_query($q)) {
								if(!isset($_POST['id']) || !$_POST['id']) {
									$_POST['id'] = $db->insert_id;
									t7send::Tweet('new lego: ' . $lego->title, t7format::FullUrl(dirname($_SERVER['SCRIPT_NAME']) . '/' . $_POST['url']));
								}
								$ajax->Data->url = dirname($_SERVER['SCRIPT_NAME']) . '/' . $_POST['url'];
							} else
								$ajax->Fail('database error saving lego model data.' . "\n\n" . $q);
						} else
							$ajax->Fail('url “' . $_POST['url'] . '” already in use.');
					else
						$ajax->Fail('error checking uniqueness of lego url.');
				} else
					$ajax->Fail('title is required.');
			else
				$ajax->Fail('image, ldraw, and instructions files must be included with new lego models.');
			break;
	}
	$ajax->Send();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['ko' => true]);
$html->Open(($id ? 'edit' : 'add') . ' lego model');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> lego model</h1>
			<form id=editlego data-bind="submit: Save">
<?php
if($id) {
?>
				<input type=hidden name=id id=legoid value="<?php echo $id; ?>">
<?php
}
?>
				<label>
					<span class=label>title:</span>
					<span class=field><input name=title maxlength=32 required data-bind="textInput: title"></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input id=url name=url maxlength=32 pattern="[a-z0-9\-_]+" data-bind="value: url"></span>
				</label>
				<label title="upload a 3d rendered image" data-bind="visible: !image()">
					<span class=label>image:</span>
					<span class=field>
						<input type=file name=image accept=".png, image/png" data-bind="event: {change: CacheImage}">
					</span>
				</label>
				<label class=multiline title="the 3d rendered image" data-bind="visible: image()">
					<span class=label>image:</span>
					<span class=field>
						<img class="art preview" data-bind="attr: {src: image}">
					</span>
				</label>
				<!-- ldraw data file (zipped) -->
				<label title="upload ldraw data file (zipped)">
					<span class=label>ldraw zip:</span>
					<span class=field>
						<input type=file name=ldraw accept=".zip, application/zip, application/x-zip, application/x-zip-compressed">
					</span>
				</label>
				<!-- step-by-step images (zipped) -->
				<label title="upload step-by-step instruction images (zipped)">
					<span class=label>instructions:</span>
					<span class=field>
						<input type=file name=instructions accept=".zip, application/zip, application/x-zip, application/x-zip-compressed">
					</span>
				</label>
				<label title="number of pieces in this model">
					<span class=label>pieces:</span>
					<span class=field>
						<input name=pieces type=number min=3 max=9999 maxlength=4 step=1 data-bind="value: pieces">
					</span>
				</label>
				<label class=multiline>
					<span class=label>description:</span>
					<span class=field><textarea name=descmd data-bind="value: descmd"></textarea></span>
				</label>
				<button id=save>save</button>
				<p data-bind="visible: id()"><img class="art preview" data-bind="attr: {src: url() ? 'data/' + url() + '.png' : ''}"></p>
			</form>
<?php
$html->Close();
