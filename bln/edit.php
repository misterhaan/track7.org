<?php
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
    $html->Open('entry not found - blog');
?>
      <h1>404 blog entry not found</h1>

      <p>
        sorry, we don’t seem to have a blog entry by that name.  try the list of
        <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all blog entries</a>.
      </p>
<?php
    $html->Close();
    die;
  }

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'get':
        if(isset($_GET['id']) && $_GET['id']) {
          if($entry = $db->query('select url, title, coalesce(nullif(markdown, \'\'),content) as markdown from blog_entries where id=\'' . +$_GET['id'] . '\' limit 1')) {
            if($entry = $entry->fetch_object()) {
              $ajax->Data->url = $entry->url;
              $ajax->Data->title = $entry->title;
              $ajax->Data->content = $entry->markdown;
              $ajax->Data->tags = [];
              if($tags = $db->query('select t.name from blog_entrytags as et left join blog_tags as t on t.id=et.tag where et.entry=\'' . +$_GET['id'] . '\''))
                while($tag = $tags->fetch_object())
                  $ajax->Data->tags[] = $tag->name;
            } else
              $ajax->Fail('entry not found.');
          } else
            $ajax->Fail('database error looking up entry for editing.');
        } else
          $ajax->Fail('get requires an id.');
        break;
      case 'save':
        $ajax->Data->fieldIssues = [];
        $_POST['title'] = trim($_POST['title']);
        if(!$_POST['title']) {
          $ajax->Data->fail = true;
          $ajax->Data->fieldIssues[] = ['field' => 'title', 'issue' => 'title is required'];
        }
        $_POST['url'] = trim($_POST['url']);
        if(!$_POST['url'])
          $_POST['url'] = preg_replace('/[^a-z0-9\-_]*/', '', str_replace(' ', '-', $_POST['title']));
        if(!preg_match('/^[a-z0-9\-_]{1,32}$/', $_POST['url'])) {
          $ajax->Data->fail = true;
          $ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be 1 - 32 lowercase letters, numbers, and dashes.'];
        }
        if($check = $db->query('select id, title from blog_entries where url=\'' . $db->escape_string($_POST['url']) . '\' limit 1'))
          if($check = $check->fetch_object())
            if($check->id != $_POST['id']) {
              $ajax->Data->fail = true;
              $ajax->Data->fieldIssues[] = ['field' => 'url', 'issue' => 'url must be unique!  already in use by entry named ' . $check->title];
            }
        $_POST['content'] = trim($_POST['content']);
        if(!$_POST['content']) {
          $ajax->Data->fail = true;
          $ajax->Data->fieldIssues[] = ['field' => 'content', 'issue' => 'content is required.'];
        }
        $_POST['newtags'] = trim($_POST['newtags']);
        $_POST['deltags'] = trim($_POST['deltags']);
        if(!$ajax->Data->fail) {
          $id = false;
          if(isset($_POST['id']))
            if($db->query('update blog_entries set title=\'' . $db->escape_string($_POST['title']) . '\', url=\'' . $db->escape_string($_POST['url']) . '\', markdown=\'' . $db->escape_string($_POST['content']) . '\', content=\'' . $db->escape_string(t7format::Markdown($_POST['content'])) . '\' where id=\'' . +$_POST['id'] . '\' limit 1'))
              $id = +$_POST['id'];
            else {
              $ajax->Data->fail = true;
              $ajax->Data->message = 'failed to update entry due to database error.';
            }
          else
            if($db->query('insert into blog_entries (title, url, markdown, content, posted) values (\'' . $db->escape_string($_POST['title']) . '\', \'' . $db->escape_string($_POST['url']) . '\', \'' . $db->escape_string($_POST['content']) . '\', \'' . $db->escape_string(t7format::Markdown($_POST['content'])) . '\', \'' . +time() . '\')'))
              $id = $db->insert_id;
            else {
              $ajax->Data->fail = true;
              $ajax->Data->message = 'failed to add entry due to database error.';
            }
          if($id) {
            $del = [];
            $new = [];
            if($_POST['deltags']) {
              $del = explode(',', $_POST['deltags']);
              $db->query('delete from blog_entrytags where entry=\'' . +$id . '\' and tag in (select id from blog_tags where name in (\'' . implode('\', \'', $del) . '\'))');
            }
            if($_POST['newtags']) {
              $new = explode(',', $_POST['newtags']);
              $db->query('insert into blog_tags (name) values (\'' . implode('\'), (\'', $new) . '\') on duplicate key update name=name');
              $db->query('insert into blog_entrytags (entry, tag) select \'' . +$id . '\' as entry, id as tag from blog_tags where name in (\'' . implode('\', \'', $new) . '\')');
            }
            if($entry = $db->query('select url, status from blog_entries where id=\'' . +$id . '\' limit 1'))
              if($entry = $entry->fetch_object()) {
                if($entry->status == 'published') {
                  $tags = array_keys(array_flip($del) + array_flip($new));
                  $db->real_query('update blog_tags as t inner join (select et.tag as tag, count(1) as count, max(e.posted) as lastused from blog_entrytags as et left join blog_entries as e on e.id=et.entry left join blog_tags as tn on tn.id=et.tag where tn.name in (\'' . implode('\', \'', $tags) . '\') and e.status=\'published\' group by et.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
                }
                $ajax->Data->url = $entry->url;
              } else
                $ajax->Fail('saved entry not found in database.');
            else
              $ajax->Fail('error looking up saved entry in database');
          }
        }
        break;
      case 'publish':
        if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
          if($db->real_query('update blog_entries set status=\'published\', posted=\'' . +time() . '\' where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
            if($db->affected_rows)
              if($tags = $db->query('select tag from blog_entrytags where entry=\'' . +$_POST['id'] . '\'')) {
                $tagids = [];
                while($tag = $tags->fetch_object())
                  $tagids[] = $tag->tag;
                $db->real_query('update blog_tags as t inner join (select et.tag as tag, count(1) as count, max(e.posted) as lastused from blog_entrytags as et left join blog_entries as e on e.id=et.entry where et.tag in (\'' . implode('\', \'', $tagids) . '\') and e.status=\'published\' group by et.tag) as s on s.tag=t.id set t.count=s.count, t.lastused=s.lastused');
                if($entry = $db->query('select url, title from blog_entries where id=\'' . +$_POST['id'] . '\' limit 1'))
                  if($entry = $entry->fetch_object())
                    t7send::Tweet('new blog: ' . $entry->title, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $entry->url);
              } else
                $ajax->Fail('entry published but could not find tags to recount.');
            else
              $ajax->Fail('entry not updated.  this should only happen if the id doesn’t exist or the entry is already published.');
          else
            $ajax->Fail('database error publishing entry.');
        else
          $ajax->Fail('numeric id required to publish an entry.');
        break;
      case 'delete':
        if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
          if($db->real_query('delete from blog_entries where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
            if($db->affected_rows) {}
            else
              $ajax->Fail('unable to delete entry.  it may be published or already deleted.');
          else
            $ajax->Fail('error deleting entry from database.');
        else
          $ajax->Fail('numeric id required to delete an entry.');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: get, save, publish, delete.');
        break;
    }
    $ajax->Send();
    die;
  }

  $id = isset($_GET['id']) ? +$_GET['id'] : false;
  $html = new t7html([]);
  $html->Open(($id ? 'edit' : 'add') . ' entry - blog');
?>
      <h1><?php echo $id ? 'edit' : 'add'; ?> entry</h1>
      <form id=editentry<?php if($id) echo ' data-entryid="' . $id . '"'; ?>>
        <label>
          <span class=label>title:</span>
          <span class=field><input id=title maxlength=128 required data-bind="value: title"></span>
        </label>
        <label>
          <span class=label>url:</span>
          <span class=field><input id=url maxlength=32 required pattern="[a-z0-9\-_]+" data-bind="value: url"></span>
        </label>
        <label class=multiline>
          <span class=label>entry:</span>
          <span class=field><textarea id=content required rows="" cols="" data-bind="value: content"></textarea></span>
        </label>
        <label>
          <span class=label>tags:</span>
          <span class=field><input id=tags pattern="([a-z0-9\.]+(,[a-z0-9\.]+)*)?" data-bind="value: tags"></span>
        </label>
        <button id=save>save</button>
      </form>
<?php
  $html->Close();
?>
