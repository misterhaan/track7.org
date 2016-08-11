<?php
  define('TR_GUIDES', 3);
  define('STEP_CHECKUSERS', 1);
  define('STEP_COPYGUIDES', 2);
  define('STEP_COPYGUIDEPAGES', 3);
  define('STEP_COPYTAGS', 4);
  define('STEP_LINKTAGS', 5);
  define('STEP_COPYCOMMENTS', 6);
  define('STEP_COPYVOTES', 7);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('guide migration');
?>
      <h1>guide migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'checkusers':
        if($us = $db->query('select u.login, count(1) as comments from track7_t7data.comments as c left join track7_t7data.users as u on u.uid=c.uid left join transition_users as t on t.olduid=c.uid where c.uid is not null and c.uid>0 and c.page like \'/guides/%\' and t.id is null group by c.uid'))
          if($us->num_rows) {
?>
      <p>
        the following users commented on guides and haven’t been migrated:
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
            $db->real_query('update transition_status set stepnum=' . STEP_CHECKUSERS . ', status=\'commenting users migrated\' where id=' . TR_GUIDES . ' and stepnum<' . STEP_CHECKUSERS);
        else {
?>
      <p class=error>error checking for unmigrated users who commented</p>
<?php
        }
        break;
      case 'copyguides':
        if($db->query('insert into guides (url, status, title, summary, posted, updated, author, level, views) select g.id, case g.status when \'new\' then \'draft\' when \'pending\' then \'submitted\' when \'approved\' then \'published\' else g.status end as status, g.title, concat(\'<p>\',replace(replace(g.description,\'&quot;\',\'"\'),\'&nbsp;\',\' \'),\'</p>\') as description, g.dateadded, g.dateupdated, 1, g.skill, s.hits from track7_t7data.guides as g left join track7_t7data.hitdetails as s on s.value=concat(\'/guides/\', g.id, \'/\') and s.type=\'request\' and s.date=\'forever\' order by g.status desc, g.dateadded'))
          $db->query('update transition_status set stepnum=' . STEP_COPYGUIDES . ', status=\'guide information migrated\' where id=' . TR_GUIDES . ' and stepnum<' . STEP_COPYGUIDES);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copyguidepages':
        if($db->query('insert into guide_pages (guide, number, heading, html) select g.id, p.pagenum, replace(p.heading,\'&quot;\',\'"\'), replace(replace(replace(p.content,\'&nbsp;\',\' \'),\'<br />\n        <br />\n        \',\'</p><p>\'),\'&quot;\',\'"\') from track7_t7data.guidepages as p left join guides as g on g.url=p.guideid where p.version<1'))
          $db->query('update transition_status set stepnum=' . STEP_COPYGUIDEPAGES . ', status=\'guide content migrated\' where id=' . TR_GUIDES . ' and stepnum<' . STEP_COPYGUIDEPAGES);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copytags':
        if($db->query('insert into guide_tags (name, description) select name, replace(description, \'&nbsp;\', \' \') from track7_t7data.taginfo where type=\'guides\' and count>0 and name!=\'\''))
          $db->query('update transition_status set stepnum=' . STEP_COPYTAGS . ', status=\'guide tags copied\' where id=\'' . TR_GUIDES . '\' and stepnum<' . STEP_COPYTAGS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'linktags':
        if($tags = $db->query('select g.id, g.posted, t.tags from track7_t7data.guides as t left join guides as g on g.url=t.id where g.status=\'published\'')) {
          $tagids = [];
          if($taglistres = $db->query('select id, name from guide_tags'))
            while($taglist = $taglistres->fetch_object())
              $tagids[$taglist->name] = $taglist->id;
          $linktags = $db->prepare('insert into guide_taglinks (tag, guide) values (?, ?)');
          $linktags->bind_param('ii', $tagid, $guideid);
          $tagcounts = [];
          $lastused = [];
          while($tag = $tags->fetch_object()) {
            $guideid = $tag->id;
            $tag->tags = explode(',', $tag->tags);
            foreach($tag->tags as $tagname) {
              $tagid = $tagids[$tagname];
              if(!isset($tagcounts[$tagid]))
                $tagcounts[$tagid] = 0;
              $tagcounts[$tagid]++;
              if(!isset($lastused[$tagid]) || $tag->posted > $lastused[$tagid])
                $lastused[$tagid] = $tag->posted;
              $linktags->execute();
            }
          }
          $linktags->close();
          $settagcount = $db->prepare('update guide_tags set count=?, lastused=? where id=?');
          $settagcount->bind_param('iii', $count, $taglastused, $tagid);
          foreach($tagcounts as $tagid => $count) {
            $taglastused = $lastused[$tagid];
            $settagcount->execute();
          }
          $settagcount->close();
          $db->real_query('update transition_status set stepnum=' . STEP_LINKTAGS . ', status=\'guide tags linked\' where id=\'' . TR_GUIDES . '\' and stepnum<' . STEP_LINKTAGS);
        }
        break;
      case 'copycomments':
        if($db->real_query('insert into guide_comments (guide, posted, user, name, contacturl, html) select g.id, c.instant, u.id, c.name, c.url, replace(c.comments, \'&nbsp;\', \' \') from track7_t7data.comments as c left join guides as g on g.url=substr(c.page,9,length(c.page)-9) left join transition_users as u on u.olduid=c.uid where page like \'/guides/%\' order by c.instant'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYCOMMENTS . ', status=\'comments copied\' where id=\'' . TR_GUIDES . '\' and stepnum<' . STEP_COPYCOMMENTS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copyvotes':
        if($db->real_query('insert into guide_votes (guide, voter, ip, vote, posted) select g.id, u.id, inet_aton(v.ip), case v.vote when -3 then 1 when 3 then 5 else v.vote+3 end as vote, v.time from track7_t7data.votes as v left join track7_t7data.ratings as r on r.id=v.ratingid left join guides as g on g.url=r.selector left join transition_users as u on u.olduid=v.uid where r.type=\'guide\' order by v.time'))
          if($db->real_query('update guides, (select guide, round((sum(vote)+3)/(count(1)+1),2) as rating, count(1) as votes from guide_votes group by guide) as s set guides.rating=s.rating, guides.votes=s.votes where guides.id=s.guide'))
            $db->real_query('update transition_status set stepnum=' . STEP_COPYVOTES . ', status=\'completed\' where id=\'' . TR_GUIDES . '\' and stepnum<' . STEP_COPYVOTES);
          else
            echo '<pre><code>' . $db->error . '</code></pre>';
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
  }

  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_GUIDES))
    $status = $status->fetch_object();
?>
      <h2>commenters</h2>
<?php
  if($status->stepnum < STEP_CHECKUSERS) {
?>
      <p>
        before guides can be migrated, all users who have commented on a guide
        must migrate.
      </p>
      <nav class=actions><a href="?dostep=checkusers">check user migration status</a></nav>
<?php
  } else {
?>
      <p>all blog commentors have been migrated.</p>

      <h2>guides</h2>
<?php
    if($status->stepnum < STEP_COPYGUIDES) {
?>
      <p>
        both published and draft guides will be copied.  any draft guides that
        are no longer wanted can be deleted later.
      </p>
      <nav class=actions><a href="?dostep=copyguides">copy guides</a></nav>
<?php
    } else {
?>
      <p>all guides have been migrated.</p>

      <h3>guide content</h3>
<?php
      if($status->stepnum < STEP_COPYGUIDEPAGES) {
?>
      <p>
        guides have pages, and since the number of pages varies their content
        needs to get copied separately.
      </p>
      <nav class=actions><a href="?dostep=copyguidepages">copy guide pages</a></nav>
<?php
      } else {
?>
      <p>all guide content has been copied.</p>

      <h2>tags</h2>
<?php
        if($status->stepnum < STEP_COPYTAGS) {
?>
      <p>
        tags are a 2-step process, which starts with copying guide tag
        definitions.
      </p>
      <nav class=actions><a href="?dostep=copytags">copy guide tags</a></nav>
<?php
        } elseif($status->stepnum < STEP_LINKTAGS) {
?>
      <p>
        tags are a 2-step process, which finishes with linking guides to tags.
      </p>
      <nav class=actions><a href="?dostep=linktags">link guides to tags</a></nav>
<?php
        } else {
?>
      <p>all guide tags have been copied to the new database.</p>

      <h2>comments</h2>
<?php
          if($status->stepnum < STEP_COPYCOMMENTS) {
?>
      <p>
        guide comments are ready to be copied.
      </p>
      <nav class=actions><a href="?dostep=copycomments">copy guide comments</a></nav>
<?php
          } else {
?>
      <p>all guide comments have been copied to the new database.</p>

      <h2>votes</h2>
<?php
            if($status->stepnum < STEP_COPYVOTES) {
?>
      <p>
        the last step is copying guide votes and recalculating ratings.
      </p>
      <nav class=actions><a href="?dostep=copyvotes">copy guide votes</a></nav>
<?php
            } else {
?>
      <p>
        all guide votes have been copied and ratings recalculated.  we’re done
        here!
      </p>
<?php
            }
          }
        }
      }
    }
  }
  $html->Close();
?>
