<?php
  define('TR_BLOG', 2);
  define('STEP_CHECKUSERS', 1);
  define('STEP_COPYENTRIES', 2);
  define('STEP_COPYTAGS', 3);
  define('STEP_LINKTAGS', 4);
  define('STEP_COPYCOMMENTS', 5);
  define('STEP_TIMETAGS', 6);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('blog migration');
?>
      <h1>blog migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'checkusers':
        if($us = $db->query('select u.login, count(1) as comments from track7_t7data.comments as c left join track7_t7data.users as u on u.uid=c.uid left join transition_users as t on t.olduid=c.uid where c.uid is not null and c.uid>0 and c.page like \'/bln/%\' and t.id is null group by c.uid'))
          if($us->num_rows) {
?>
      <p>
        the following users commented on blog entries and haven’t been migrated:
      </p>
      <ul>
<?php
            while($u = $us->fetch_object()) {
?>
        <li><a href="/user/<?php echo $u->login; ?>/"><?php echo $u->login; ?></a> (<?php echo $u->comments; ?> comments)</li>
<?php
            }
?>
      </ul>
      <p>
        visit <a href="users.php">user migration</a> and migrate these users
        before continuing.
      </p>
<?php
          } else
            $db->real_query('update transition_status set stepnum=' . STEP_CHECKUSERS . ', status=\'commenting users migrated\' where id=' . TR_BLOG . ' and stepnum<' . STEP_CHECKUSERS);
        else {
?>
      <p class=error>error checking for unmigrated users who commented</p>
<?php
        }
        break;
      case 'copyentries':
        if($db->real_query('insert into blog_entries (url, status, posted, title, content) select name, status, instant, replace(replace(replace(replace(title, \'&lsquo;\', \'‘\'), \'&rsquo;\', \'’\'), \'&ldquo;\', \'“\'), \'&rdquo;\', \'”\'), replace(replace(replace(replace(replace(replace(replace(post, \'&nbsp;\', \' \'), \'&mdash;\', \'—\'), \'&rsquo;\', \'’\'), \'&lsquo;\', \'‘\'), \'&ldquo;\', \'“\'), \'&rdquo;\', \'”\'), \'&quot;\', \'"\') from track7_t7data.bln'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYENTRIES . ', status=\'blog entries copied\' where id=\'' . TR_BLOG . '\' and stepnum<' . STEP_COPYENTRIES);
        break;
      case 'copytags':
        if($db->real_query('insert into blog_tags (name, description) select name, replace(description, \'&rsquo;\', \'’\') from track7_t7data.taginfo where type=\'entries\' and count>0'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYTAGS . ', status=\'blog tags copied\' where id=\'' . TR_BLOG . '\' and stepnum<' . STEP_COPYTAGS);
        break;
      case 'linktags':
        if($tags = $db->query('select e.id, b.tags from track7_t7data.bln as b left join blog_entries as e on e.url=b.name where e.status=\'published\'')) {
          $tagids = [];
          if($taglistres = $db->query('select id, name from blog_tags'))
            while($taglist = $taglistres->fetch_object())
              $tagids[$taglist->name] = $taglist->id;
          $linktags = $db->prepare('insert into blog_entrytags (tag, entry) values (?, ?)');
          $linktags->bind_param('ii', $tagid, $entryid);
          $tagcounts = [];
          while($tag = $tags->fetch_object()) {
            $entryid = $tag->id;
            $tag->tags = explode(',', $tag->tags);
            foreach($tag->tags as $tagname) {
              $tagid = $tagids[$tagname];
              if(!isset($tagcounts[$tagid]))
                $tagcounts[$tagid] = 0;
              $tagcounts[$tagid]++;
              $linktags->execute();
            }
          }
          $linktags->close();
          $settagcount = $db->prepare('update blog_tags set count=? where id=?');
          $settagcount->bind_param('ii', $count, $tagid);
          foreach($tagcounts as $tagid => $count)
            $settagcount->execute();
          $settagcount->close();
          $db->real_query('update transition_status set stepnum=' . STEP_LINKTAGS . ', status=\'blog tags linked\' where id=\'' . TR_BLOG . '\' and stepnum<' . STEP_LINKTAGS);
        }
        break;
      case 'copycomments':
        if($db->real_query('insert into blog_comments (entry, posted, user, name, contacturl, html) select e.id, c.instant, u.id, c.name, c.url, replace(replace(replace(c.comments, \'&nbsp;\', \' \'), \'&rsquo;\', \'’\'), \'&mdash;\', \'—\') from track7_t7data.comments as c left join blog_entries as e on e.url=substr(c.page, 6) left join transition_users as u on u.olduid=c.uid where page like \'/bln/%\' order by c.instant'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYCOMMENTS . ', status=\'completed\' where id=\'' . TR_BLOG . '\' and stepnum<' . STEP_COPYCOMMENTS);
        break;
      case 'timetags':
        if($db->real_query('update blog_tags set lastused=(select max(e.posted) as lastused from blog_entrytags as et left join blog_entries as e on e.id=et.entry where et.tag=blog_tags.id group by et.tag)'))
          $db->real_query('update transition_status set stepnum=' . STEP_TIMETAGS . ', status=\'completed\' where id=\'' . TR_BLOG . '\' and stepnum<' . STEP_TIMETAGS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
    }

  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_BLOG))
    $status = $status->fetch_object();
?>
      <h2>commenters</h2>
<?php
  if($status->stepnum < STEP_CHECKUSERS) {
?>
      <p>
        before blog entries can be migrated, all users who have commented on a
        blog entry must migrate.
      </p>
      <nav class=actions><a href="?dostep=checkusers">check user migration status</a></nav>
<?php
  } else {
?>
      <p>
        all blog commenters have been migrated.
      </p>

      <h2>entries</h2>
<?php
    if($status->stepnum < STEP_COPYENTRIES) {
?>
      <p>
        both published and draft entries will be copied.  any draft entries
        that are no longer wanted can be deleted later.
      </p>
      <nav class=actions><a href="?dostep=copyentries">copy blog entries</a></nav>
<?php
    } else {
?>
      <p>
        all blog entries have been copied to the new database.
      </p>

      <h2>tags</h2>
<?php
      if($status->stepnum < STEP_COPYTAGS) {
?>
      <p>
        tags are a 2-step process, which starts with copying blog tag
        definitions.
      </p>
      <nav class=actions><a href="?dostep=copytags">copy blog tags</a></nav>
<?php
      } elseif($status->stepnum < STEP_LINKTAGS) {
?>
      <p>
        tags are a 2-step process, which finishes with linking blogs to tags.
      </p>
      <nav class=actions><a href="?dostep=linktags">link blog entries to tags</a></nav>
<?php
      } else {
?>
      <p>
        all blog tags have been copied to the new database.
      </p>

      <h2>comments</h2>
<?php
        if($status->stepnum < STEP_COPYCOMMENTS) {
?>
      <p>
        blog comments are ready to be copied.
      </p>
      <nav class=actions><a href="?dostep=copycomments">copy blog comments</a></nav>
<?php
        } else {
?>
      <p>
        all blog comments have been copied to the new database.
      </p>

      <h2>tags last used</h2>
<?php
          if($status->stepnum < STEP_TIMETAGS) {
?>
      <p>
        i forgot to set the last used timestamp on blog tags, so they need to be
        recalculated.
      </p>
      <nav class=actions><a href="?dostep=timetags">find tag last used times</a></nav>
<?php
          } else {
?>
      <p>
        tags last used times have been updated.  we’re done here!
      </p>
<?php
          }
        }
      }
    }
  }
  $html->Close();
?>
