<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'list':
        switch($_GET['type']) {
          case 'blog':
          case 'guide':
          case 'photos':
          case 'art':
          case 'forum':
            $tags = $db->query('select name, count' . (isset($_GET['full']) ? ', id, lastused, description' : '') . ' from ' . $_GET['type'] . '_tags where count>' . (isset($_GET['full']) ? '=' : '') . '1 order by lastused desc');
            if($tags) {
              $ajax->Data->tags = [];
              while($tag = $tags->fetch_object()) {
                if(isset($_GET['full']))
                  $tag->lastused = t7format::TimeTag('ago', $tag->lastused, 'g:i a \o\n l F jS Y');
                $ajax->Data->tags[] = $tag;
              }
            } else
              $ajax->Fail('error getting list of ' . $_GET['type'] . ' tags.');
            break;
          default:
            $ajax->Fail('unknown tag type for list.  supported tag types are: blog, guide, photos, art, forum.');
            break;
        }
        break;
      case 'setdesc':
        if($user->IsAdmin())
          if(isset($_POST['description']) && isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
            switch($_GET['type']) {
              case 'blog':
              case 'guide':
              case 'photos':
              case 'art':
              case 'forum':
                if(!$db->real_query('update ' . $_GET['type'] . '_tags set description=\'' . $db->escape_string($_POST['description']) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
                  $ajax->Fail('database error saving description.');
                break;
              default:
                $ajax->Fail('unknown tag type for setdesc.  supported tag types are: blog, guide, photos, art, forum.');
            }
          else
            $ajax->Fail('required fields missing or id non-numeric.');
        else
          $ajax->Fail('only the administrator can set tag descriptions.');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: list, setdesc.');
        break;
    }
    $ajax->Send();
    die;
  }

  $html = new t7html(['ko' => true]);
  $html->Open('tags');
?>
      <h1>tag information</h1>
      <div class=tabbed>
        <nav class=tabs>
          <a href="#blog" title="tags for blog entries">blog entries</a>
          <a href="#guide" title="tags for guides">guides</a>
          <a href="#photos" title="tags for photos">photos</a>
          <a href="#art" title="tags for art">art</a>
          <a href="#forum" title="tags for forum discussions">forum</a>
        </nav>
        <ul id=taginfo data-bind="foreach: tags">
          <li>
            <div class=tagdata>
              <a data-bind="text: name, attr: {href: $parent.MakeUrl(name)}"></a>
              <span class=count data-bind="text: count + ' uses'"></span>
              <time data-bind="text: lastused.display + ' ago'"></time>
            </div>
            <div class=description>
              <span class=prefix data-bind="text: $parent.prefix"></span>
              <span class=editable data-bind="html: description, visible: !editing()"></span>
<?php
  if($user->IsAdmin()) {
?>
              <label class=multiline data-bind="visible: editing()">
                <span class=field><textarea data-bind="value: $parent.descriptionedit"></textarea></span>
                <span>
                  <a href="#save" title="save tag description" class="action okay" data-bind="click: $parent.Save"></a>
                  <a href="#cancel" title="cancel editing" class="action cancel" data-bind="click: $parent.Cancel"></a>
                </span>
              </label>
<?php
  }
?>
              <span class=postfix data-bind="text: $parent.postfix"></span>
<?php
  if($user->IsAdmin()) {
?>
              <a href="#edit" class="action edit" data-bind="visible: !editing() && $parent.descriptionedit() === false, click: $parent.Edit"></a>
<?php
  }
?>
            </div>
          </li>
        </ul>
      </div>
<?php
  $html->Close();
?>
