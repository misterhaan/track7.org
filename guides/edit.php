<?
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(!$user->IsAdmin()) {
    // this page is only for admin, so give an ajax error or try to go to page 1
    if(isset($_GET['ajax'])) {
      $ajax = new t7ajax();
      $ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
      $ajax->Send();
      die;
    }
    if(isset($_GET['url']))
      if(isset($_GET['tag']))
        header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $_GET['tag'] . '/' . $_GET['url'] . '/1');
      else
        header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $_GET['url'] . '/1');
    else
      header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/');
    die;
  }

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'get':
        if(isset($_GET['url']) && $_GET['url'])
          if($guide = $db->query('select id, status, title, url, if(length(summary_markdown) > 0, summary_markdown, summary) as summary, level from guides where url=\'' . $db->escape_string($_GET['url']) . '\''))
            if($guide = $guide->fetch_object()) {
              $ajax->Data->id = +$guide->id;
              $ajax->Data->status = $guide->status;
              $ajax->Data->title = $guide->title;
              $ajax->Data->url = $guide->url;
              $ajax->Data->summary = $guide->summary;
              $ajax->Data->level = $guide->level;
              $ajax->Data->tags = [];
              if($tags = $db->query('select t.name from guide_taglinks as tl left join guide_tags as t on t.id=tl.tag where tl.guide=\'' . +$guide->id . '\''))
                while($tag = $tags->fetch_object())
                  $ajax->Data->tags[] = $tag->name;
              $ajax->Data->pages = [];
              if($pages = $db->query('select id, number, heading, if(length(markdown) > 0, markdown, html) as markdown from guide_pages where guide=\'' . +$guide->id . '\' order by number'))
                while($page = $pages->fetch_object()) {
                  $page->number = +$page->number;
                  $ajax->Data->pages[] = $page;
                }
            } else
              $ajax->Fail('guide not found');
          else
            $ajax->Fail('database error looking up guide for editing.');
        else
          $ajax->Fail('get requires a url.');
        break;
      case 'save':
        if(isset($_POST['guidejson'])) {
          $guide = json_decode($_POST['guidejson']);
          $ajax->Data->url = $guide->url;
          $q = 'guides set url=\'' . $db->escape_string($guide->url) . '\', title=\'' . $db->escape_string(trim($guide->title)) . '\', summary_markdown=\'' . $db->escape_string(trim($guide->summary)) . '\', summary=\'' . $db->escape_string(t7format::Markdown(trim($guide->summary))) . '\', level=\'' . $db->escape_string($guide->level) . '\'';
          if($guide->status != 'published' || !$guide->correctionsOnly)
            $q .= ', updated=\'' . +time() . '\'';
          $q = $guide->id ? 'update ' . $q . ' where id=\'' . +$guide->id . '\' limit 1' : 'insert into ' . $q . ', author=1';
          if($db->real_query($q)) {
            if(!$guide->id)
              $guide->id = $db->insert_id;
          } else
            $ajax->Fail('database error saving guide data.');
          foreach($guide->pages as $page) {
            $q = 'guide_pages set number=\'' . +$page->number . '\', heading=\'' . $db->escape_string(trim($page->heading)) . '\', markdown=\'' . $db->escape_string(trim($page->markdown)) . '\', html=\'' . $db->escape_string(t7format::Markdown(trim($page->markdown))) . '\'';
            $q = $page->id ? 'update ' . $q . ' where id=\'' . +$page->id . '\' limit 1' : 'insert into ' . $q . ', guide=\'' . +$guide->id . '\'';
            if(!$db->real_query($q))
              $ajax->Fail('database error saving page ' . +$page->number);
          }
          if(count($guide->deletedPageIDs))
            $db->real_query('delete from guide_pages where id in (' . implode(',', $guide->deletedPageIDs) . ')');
          $addtags = array_diff($guide->taglist, $guide->originalTaglist);
          if(count($addtags)) {
            $qat = $db->prepare('insert into guide_tags (name) values (?) on duplicate key update id=id');
            $qat->bind_param('s', $name);
            $qlt = $db->prepare('insert into guide_taglinks set guide=\'' . +$guide->id . '\', tag=(select id from guide_tags where name=? limit 1)');
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
          $deltags = array_diff($guide->originalTaglist, $guide->taglist);
          if(count($deltags))
            $db->real_query('delete from guide_taglinks where guide=\'' . +$guide->id . '\' and tag in (select id from guide_tags where name in (\'' . implode('\', \'', $deltags) . '\'))');
          if($guide->status = 'published') {
            $tags = array_merge($addtags, $deltags);
            if(count($tags))
              if(!$db->real_query('update guide_tags set count=(select count(1) as count from guide_taglinks as tl left join guides as g on g.id=tl.guide where g.status=\'published\' and tl.tag=guide_tags.id group by tl.tag), lastused=(select max(g.updated) as lastused from guide_taglinks as tl left join guides as g on g.id=tl.guide where g.status=\'published\' and tl.tag=guide_tags.id group by tl.tag) where name in (\'' . implode('\', \'', $tags) . '\')'))
                $ajax->Fail('error updating tag stats:  ' . $db->error);
          }
        } else
          $ajax->Fail('missing required parameter guidejson.');
        break;
      case 'publish':
        if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
          if($db->real_query('update guides set status=\'published\', posted=\'' . +time() . '\', updated=\'' . +time() . '\' where id=\'' . +$_POST['id'] . '\' and status=\'draft\' limit 1'))
            if($db->affected_rows) {
              $db->real_query('update guide_tags inner join guide_taglinks as tl on tl.tag=guide_tags.id and tl.guide=\'' . +$_POST['id'] .'\' set count=(select count(1) as count from guide_taglinks as tl left join guides as g on g.id=tl.guide where g.status=\'published\' and tl.tag=guide_tags.id group by tl.tag), lastused=(select max(g.updated) as lastused from guide_taglinks as tl left join guides as g on g.id=tl.guide where g.status=\'published\' and tl.tag=guide_tags.id group by tl.tag)');
              if($guide = $db->query('select url, title from guides where id=\'' . +$_POST['id'] . '\' limit 1'))
                if($guide = $guide->fetch_object())
                  t7send::Tweet('new guide: ' . $guide->title, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->url . '/1');
            } else
              $ajax->Fail('guide not updated.  this should only happen if the id doesn’t exist or the guide is already published.');
          else
            $ajax->Fail('database error publishing guide.');
        else
          $ajax->Fail('numeric id required to publish a guide.');
        break;
      case 'delete':
        if(isset($_POST['id']) && $_POST['id'] == +$_POST['id'])
          if($db->real_query('delete from guide_taglinks where guide=\'' . +$_POST['id'] . '\''))
            if($db->real_query('delete from guide_pages where guide=\'' . +$_POST['id'] . '\''))
              if($db->real_query('delete from guides where id=\'' . $_POST['id'] . '\''))
                ;
              else
                $ajax->Fail('database error deleting guide.');
            else
              $ajax->Fail('database error deleting guide pages.');
          else
            $ajax->Fail('database error deleting guide tags.');
        else
          $ajax->Fail('numeric id required to delete a guide.');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: get, save, publish, delete.');
        break;
    }
    $ajax->Send();
    die;
  }

  $url = isset($_GET['url']) ? $_GET['url'] : false;
  $html = new t7html(['ko' => true]);
  $html->Open(($url ? 'edit' : 'add') . ' guide');
?>
      <h1><?php echo $url ? 'edit' : 'add'; ?> guide</h1>
      <form id=editguide<?php if($url) echo ' data-url="' . $url . '"'; ?>>
        <label>
          <span class=label>title:</span>
          <span class=field><input maxlength=128 required data-bind="value: title"></span>
        </label>
        <label>
          <span class=label>url:</span>
          <span class=field><input maxlength=32 required pattern="[a-z0-9\-_]+" data-bind="value: url"></span>
        </label>
        <label class=multiline>
          <span class=label>summary:</span>
          <span class=field><textarea required rows="" cols="" data-bind="value: summary"></textarea></span>
        </label>
        <label>
          <span class=label>level:</span>
          <span class=field><select data-bind="value: level"><option>beginner</option><option>intermediate</option><option>advanced</option></select></span>
        </label>
        <label>
          <span class=label>tags:</span>
          <span class=field><input pattern="([a-z0-9\.]+(,[a-z0-9\.]+)*)?" data-bind="value: tags"></span>
        </label>
        <!--ko foreach: pages -->
        <fieldset>
          <legend data-bind="text: 'page ' + number()"></legend>
          <a class="action up" href="#moveup" title="move this page earlier" data-bind="visible: $index() > 0, click: MoveUp"></a>
          <a class="action down" href="#movedown" title="move this page later" data-bind="visible: $index() < $parent.pages().length - 1, click: MoveDown"></a>
          <a class="action del" href="#del" title="remove this page" data-bind="click: Remove"></a>
          <label>
            <span class=label>heading:</span>
            <span class=field><input maxlength=128 required data-bind="value: heading"></span>
          </label>
          <label class=multiline>
            <span class=label>content:</span>
            <span class=field><textarea required rows="" cols="" data-bind="value: markdown"></textarea></span>
          </label>
        </fieldset>
        <!--/ko-->
        <label>
          <span class=label></span>
          <span class=field><a class="action new" href="#addpage" title="add a new blank page to the end" data-bind="click: AddPage">add page</a></span>
        </label>
        <label data-bind="visible: status() != 'draft'">
          <span class=label></span>
          <span class=field><span><input type=checkbox data-bind="checked: correctionsOnly"> this edit is formatting / spelling / grammar only</span></span>
        </label>
        <button data-bind="click: Save">save</button>
      </form>
<?php
  $html->Close();
?>
