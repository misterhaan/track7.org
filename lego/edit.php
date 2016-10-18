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
        if(isset($_POST['legojson'])) {
          $lego = json_decode($_POST['legojson']);
          if($lego->id || $lego->image && $lego->ldraw && $lego->instructions)
            if($lego->title) {
              if(!$lego->url)
                $lego->url = str_replace(' ', '-', $lego->title);
              if($unique = $db->query('select url from lego_models where url=\'' . $db->escape_string($lego->url) . '\' and id!=\'' . +$lego->id . '\' limit 1'))
                if($unique->num_rows < 1) {
                  if($lego->image) {
                    $image = base64_decode(explode(',', $lego->image)[1]);
                    $size = getimagesizefromstring($image);
                    $image = imagecreatefromstring($image);
                    $aspect = $size[0] / $size[1];
                    if($size[2] == IMAGETYPE_PNG) {
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
                        imagepng($fullsize, dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $lego->url . '.png');
                        imagedestroy($fullsize);
                      } else
                        imagepng($image, dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $lego->url . '.png');
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
                      imagepng($thumb, dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $lego->url . '-thumb.png');
                      imagedestroy($thumb);
                      imagedestroy($image);
                    } else {
                      $ajax->Fail('image must be png format');
                      $ajax->Send();
                      die;
                    }
                  }
                  if($lego->ldraw) {
                    if(false === $ldr = fopen(dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $lego->url . '-ldr.zip', 'wb')) {
                      $ajax->Fail('unable to open ldraw data file for writing');
                      $ajax->Send();
                      die;
                    }
                    if(false === fwrite($ldr, base64_decode(explode(',', $lego->ldraw)[1]))) {
                      $ajax->Fail('error saving ldraw data file');
                      $ajax->Send();
                      die;
                    }
                  }
                  if($lego->instructions) {
                    if(false === $ldr = fopen(dirname($_SERVER['SCRIPT_FILENAME']) . '/data/' . $lego->url . '-img.zip', 'wb')) {
                      $ajax->Fail('unable to open ldraw data file for writing');
                      $ajax->Send();
                      die;
                    }
                    if(false === fwrite($ldr, base64_decode(explode(',', $lego->instructions)[1]))) {
                      $ajax->Fail('error saving ldraw data file');
                      $ajax->Send();
                      die;
                    }
                  }
                  $q = 'lego_models set title=\'' . $db->escape_string($lego->title) . '\', url=\'' . $db->escape_string(trim($lego->url)) . '\', ' . 'pieces=\'' . +$lego->pieces . '\', descmd=\'' . $db->escape_string(trim($lego->descmd)) . '\', deschtml=\'' . $db->escape_string(t7format::Markdown(trim($lego->descmd))) . '\'';
                  $q = $lego->id ? 'update ' . $q . ' where id=\'' . +$lego->id . '\' limit 1' : 'insert into ' . $q . ', posted=\'' . +time() . '\'';
                  if($db->real_query($q)) {
                    if(!$lego->id) {
                      $lego->id = $db->insert_id;
                      t7send::Tweet('new lego: ' . $lego->title, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $lego->url);
                    }
                    $ajax->Data->url = dirname($_SERVER['SCRIPT_NAME']) . '/' . $lego->url;
                  } else
                    $ajax->Fail('database error saving lego model data.' . "\n\n" . $q);
                } else
                  $ajax->Fail('url “' . $lego->url . '” already in use.');
              else
                $ajax->Fail('error checking uniqueness of lego url.');
            } else
              $ajax->Fail('title is required.');
          else
            $ajax->Fail('image, ldraw, and instructions files must be included with new lego models.');
        } else
          $ajax->Fail('missing required parameter legojson.');
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
      <form id=editlego<?php if($id) echo ' data-legoid="' . $id . '"'; ?> data-bind="submit: Save">
        <label>
          <span class=label>title:</span>
          <span class=field><input maxlength=32 required data-bind="value: title"></span>
        </label>
        <label>
          <span class=label>url:</span>
          <span class=field><input maxlength=32 pattern="[a-z0-9\-_]+" data-bind="value: url"></span>
        </label>
        <label title="upload a 3d rendered image" data-bind="visible: !image()">
          <span class=label>image:</span>
          <span class=field>
            <input type=file accept=".png, image/png" data-bind="event: {change: CacheImage}">
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
            <input type=file accept=".zip, application/zip, application/x-zip, application/x-zip-compressed" data-bind="event: {change: CacheLdraw}">
          </span>
        </label>
        <!-- step-by-step images (zipped) -->
        <label title="upload step-by-step instruction images (zipped)">
          <span class=label>instructions:</span>
          <span class=field>
            <input type=file accept=".zip, application/zip, application/x-zip, application/x-zip-compressed" data-bind="event: {change: CacheInstructions}">
          </span>
        </label>
        <label title="number of pieces in this model">
          <span class=label>pieces:</span>
          <span class=field>
            <input type=number min=3 max=9999 maxlength=4 step=1 data-bind="value: pieces">
          </span>
        </label>
        <label class=multiline>
          <span class=label>description:</span>
          <span class=field><textarea data-bind="value: descmd"></textarea></span>
        </label>
        <button>save</button>
        <p data-bind="visible: id()"><img class="art preview" data-bind="attr: {src: url() ? 'data/' + url() + '.png' : ''}"></p>
      </form>
<?php
  $html->Close();
?>
