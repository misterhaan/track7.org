<?php
  define('TR_PHOTOS', 5);
  define('STEP_COPYPHOTOS', 1);
  define('STEP_COPYTAGS', 2);
  define('STEP_LINKTAGS', 3);
  define('STEP_COPYCOMMENTS', 4);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('photos');
?>
      <h1>photo album migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'copyphotos':
        if($db->real_query('insert into photos (url, youtube, posted, taken, year, caption, story) select id as url, youtubeid as youtube, added as posted, if(taken>9999,taken,null) as taken, if(taken>9999,year(from_unixtime(taken)),taken) as year, replace(replace(replace(replace(caption,\'&rsquo;\',\'’\'),\'&ccdeil;\',\'ç\'),\'&ouml;\',\'ö\'), \'&uuml;\', \'ü\') as caption, concat(\'<p>\',replace(replace(replace(replace(replace(replace(description,\'&nbsp;\',\' \'),\'<br />\',\'</p><p>\'),\'&rsquo;\',\'’\'),\'&ldquo;\',\'“\'),\'&rdquo;\',\'”\'),\'&mdash;\',\'—\'),\'</p>\') as story from track7_t7data.photos'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYPHOTOS . ', status=\'photos copied\' where id=' . TR_PHOTOS . ' and stepnum<' . STEP_COPYPHOTOS);
        else {
?>
      <p class=error>error copying photos:  <?php echo $db->error; ?></p>
<?php
        }
        break;
      case 'copytags':
        if($db->query('insert into photos_tags (name, description) select name, concat(\'<p>\',replace(description, \'&nbsp;\', \' \'),\'</p>\') from track7_t7data.taginfo where type=\'photos\' and count>0 and name!=\'\''))
          $db->query('update transition_status set stepnum=' . STEP_COPYTAGS . ', status=\'photo tags copied\' where id=\'' . TR_PHOTOS . '\' and stepnum<' . STEP_COPYTAGS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'linktags':
        if($tags = $db->query('select p.id, p.posted, t.tags from track7_t7data.photos as t left join photos as p on p.url=t.id where t.tags!=\'\'')) {
          $tagids = [];
          if($taglistres = $db->query('select id, name from photos_tags'))
            while($taglist = $taglistres->fetch_object())
              $tagids[$taglist->name] = $taglist->id;
          $linktags = $db->prepare('insert into photos_taglinks (tag, photo) values (?, ?)');
          $linktags->bind_param('ii', $tagid, $photoid);
          $tagcounts = [];
          $lastused = [];
          while($tag = $tags->fetch_object()) {
            $photoid = $tag->id;
            $tag->tags = explode(',', $tag->tags);
            foreach($tag->tags as $tagname)
              if(isset($tagids[$tagname])) {
                $tagid = $tagids[$tagname];
                if(!isset($tagcounts[$tagid]))
                  $tagcounts[$tagid] = 0;
                $tagcounts[$tagid]++;
                if(!isset($lastused[$tagid]) || $tag->posted > $lastused[$tagid])
                  $lastused[$tagid] = $tag->posted;
                $linktags->execute();
              } else {
?>
      <p class=error>tag “<?php echo $tagname; ?>” doesn’t have an id somehow.  it came from photo id <?php echo $photoid; ?>.</p>
<?php
              }
          }
          $linktags->close();
          $settagcount = $db->prepare('update photos_tags set count=?, lastused=? where id=?');
          $settagcount->bind_param('iii', $count, $taglastused, $tagid);
          foreach($tagcounts as $tagid => $count) {
            $taglastused = $lastused[$tagid];
            $settagcount->execute();
          }
          $settagcount->close();
          $db->real_query('update transition_status set stepnum=' . STEP_LINKTAGS . ', status=\'photo tags linked\' where id=\'' . TR_PHOTOS . '\' and stepnum<' . STEP_LINKTAGS);
        }
        break;
      case 'copycomments':
        if($db->real_query('insert into photos_comments (photo, posted, user, name, contacturl, html) select p.id, c.instant, u.id, c.name, c.url, replace(c.comments, \'&nbsp;\', \' \') from track7_t7data.comments as c left join photos as p on p.url=substr(c.page,14,length(c.page)-13) left join transition_users as u on u.olduid=c.uid where page like \'/album/photo/%\' order by c.instant'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYCOMMENTS . ', status=\'comments copied\' where id=\'' . TR_PHOTOS . '\' and stepnum<' . STEP_COPYCOMMENTS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
    }

  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_PHOTOS))
    $status = $status->fetch_object();
?>
      <h2>photos</h2>
<?php
  if($status->stepnum < STEP_COPYPHOTOS) {
?>
      <p>
        make sure to add 'photos' to contributions.srctbl and 'photo' to
        contributions.conttype, create the photos table, and add its triggers.
        then photos can be copied.
      </p>
      <nav class=calltoaction><a class=action href="?dostep=copyphotos">copy photos</a></nav>
<?php
  } else {
?>
      <p>photos have been copied.</p>

      <h2>tags</h2>
<?php
    if($status->stepnum < STEP_COPYTAGS) {
?>
      <p>
        make sure the photos_tags table exists, then photo tags can be copied.
      </p>
      <nav class=calltoaction><a class=action href="?dostep=copytags">copy tags</a></nav>
<?php
    } elseif($status->stepnum < STEP_LINKTAGS) {
?>
      <p>tags have been copied.</p>
      <p>
        make sure the photos_taglinks table exists, then photos and tags can be
        linked.
      </p>
      <nav class=calltoaction><a class=action href="?dostep=linktags">link tags</a></nav>
<?php
    } else {
?>
      <p>tags have been copied, linked, and counted.</p>

      <h2>comments</h2>
<?php
      if($status->stepnum < STEP_COPYCOMMENTS) {
?>
      <p>
        make sure the photos_comments table and its triggers exist, then photo
        comments can be copied.
      </p>
      <nav class=calltoaction><a class=action href="?dostep=copycomments">copy comments</a></nav>
<?php
      } else {
?>
        <p>comments have been copied.  we’re done here!</p>
<?php
      }
    }
  }
  $html->Close();
?>
