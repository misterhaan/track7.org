<?php
  define('TR_ART', 6);
  define('STEP_COPYART', 1);
  define('STEP_COPYCOVERS', 2);
  define('STEP_TAGART', 3);
  define('STEP_TAGCOVERS', 4);
  define('STEP_COUNTTAGS', 5);
  define('STEP_COPYCOVERCOMMENTS', 6);
  define('STEP_COPYARTCOMMENTS', 7);
  define('STEP_COPYVOTES', 8);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('art migration');
?>
      <h1>art migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'copyart':
        if($db->real_query('insert into art (url, title, format, posted, deschtml) select id, id, (select id from image_formats where ext=\'png\' limit 1), adddate, replace(description,\'&nbsp;\',\' \') from track7_t7data.art'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYART . ', status=\'visual art migrated\' where id=' . TR_ART . ' and stepnum<' . STEP_COPYART);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copycovers':
        if($db->real_query('insert into art (url, title, format, deschtml) select id, title, (select id from image_formats where ext=\'jpg\' limit 1), concat(\'<p>\',replace(coverart,\'&nbsp;\',\' \'),\'</p><p>\',replace(music,\'&nbsp;\',\' \')) from track7_t7data.compcds order by sort'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYCOVERS . ', status=\'cover art migrated\' where id=' . TR_ART . ' and stepnum<' . STEP_COPYCOVERS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'tagart':
        if($db->real_query('insert into art_taglinks (tag, art) select t.id as tag, na.id as art from track7_t7data.art as oa left join art_tags as t on t.name=oa.type left join art as na on na.url=oa.id'))
          $db->real_query('update transition_status set stepnum=' . STEP_TAGART . ', status=\'sketches and digital art tagged\' where id=' . TR_ART . ' and stepnum<' . STEP_TAGART);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'tagcovers':
        if($db->real_query('insert into art_taglinks (tag, art) select t.id as tag, a.id as art from track7_t7data.compcds as c left join art_tags as t on t.name=\'cover\' left join art as a on a.url=c.id'))
          $db->real_query('update transition_status set stepnum=' . STEP_TAGCOVERS . ', status=\'cover art tagged\' where id=' . TR_ART . ' and stepnum<' . STEP_TAGCOVERS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'counttags':
        if($db->real_query('update art_tags as t set t.count=(select count(1) from art_taglinks as tl where tl.tag=t.id group by tl.tag), t.lastused=(select a.posted from art_taglinks as tl left join art as a on a.id=tl.art where tl.tag=t.id order by a.posted desc limit 1)'))
          $db->real_query('update transition_status set stepnum=' . STEP_COUNTTAGS . ', status=\'tag last used and count updated\' where id=' . TR_ART . ' and stepnum<' . STEP_COUNTTAGS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copycovercomments':
        if($db->real_query('insert into art_comments (art, posted, user, name, contacturl, html) select a.id, c.instant, u.id, c.name, c.url, replace(c.comments, \'&nbsp;\', \' \') from track7_t7data.comments as c left join art as a on a.url=substr(c.page,9,length(c.page)-12) left join transition_users as u on u.olduid=c.uid where c.page like \'/art/cd/%\' order by c.instant'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYCOVERCOMMENTS . ', status=\'cover art comments copied\' where id=' . TR_ART . ' and stepnum<' . STEP_COPYCOVERCOMMENTS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copyartcomments':
        if(isset($_POST['cid']) && isset($_POST['aid']) && is_array($_POST['cid']) && is_array($_POST['aid']) && count($_POST['cid']) == count($_POST['aid'])) {
          $art = $comment = 0;
          $ins = $db->prepare('insert into art_comments (art, posted, user, name, contacturl, html) select ?, c.instant, u.id, c.name, c.url, replace(c.comments,\'&nbsp;\',\' \') from track7_t7data.comments as c left join transition_users as u on u.olduid=c.uid where c.id=?');
          $ins->bind_param('ii', $art, $comment);
          for($i = 0; $i < count($_POST['cid']); $i++) {
            $art = +$_POST['aid'][$i];
            if($art) {
              $comment = +$_POST['cid'][$i];
              $ins->execute();
            }
          }
          $ins->close();
          $db->real_query('update transition_status set stepnum=' . STEP_COPYARTCOMMENTS . ', status=\'sketch comments copied\' where id=' . TR_ART . ' and stepnum<' . STEP_COPYARTCOMMENTS);
        } else
          echo '<p class=error>form fields not present or misaligned.</p>';
        break;
      case 'copyvotes':
        if($db->real_query('insert into art_votes (art, voter, ip, vote, posted) select a.id, u.id, inet_aton(v.ip), case v.vote when -3 then 1 when 3 then 5 else v.vote+3 end as vote, v.time from track7_t7data.votes as v left join track7_t7data.ratings as r on r.id=v.ratingid left join art as a on a.url=r.selector left join transition_users as u on u.olduid=v.uid where r.type=\'sketch\' or r.type=\'digital\' order by v.time'))
          if($db->real_query('update art as a, (select art, round((sum(vote)+3)/(count(1)+1),2) as rating, count(1) as votes from art_votes group by art) as s set a.rating=s.rating, a.votes=s.votes where a.id=s.art'))
            $db->real_query('update transition_status set stepnum=' . STEP_COPYVOTES . ', status=\'completed\' where id=\'' . TR_ART . '\' and stepnum<' . STEP_COPYVOTES);
          else
            echo '<pre><code>' . $db->error . '</code></pre>';
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
  }

  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_ART))
    $status = $status->fetch_object();
?>
      <h2>sketches and digital art</h2>
<?php
  if($status->stepnum < STEP_COPYART) {
?>
      <p>
        the art migration begins with the visual art table, which contains
        sketches and digital art.  make sure the art table, image_formats table
        (with data), and triggers to update contributions (along with
        contribution enum entries) have been created, then copy that art!
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copyart">copy that art!</a></nav>
<?php
  } else {
?>
      <p>sketches and digital art have been migrated successfully.</p>

      <h2>cover art</h2>
<?php
    if($status->stepnum < STEP_COPYCOVERS) {
?>
      <p>
        cover art will be combined with the rest of the art.  this step has no
        new requirements after the previous step.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copycovers">copy cover art!</a></nav>
<?php
    } else {
?>
      <p>cover art has been migrated successfully.</p>

      <h2>tags</h2>
<?php
      if($status->stepnum < STEP_TAGART) {
?>
      <p>
        since sketches, digital art, and cover art are all combined now, they
        need to be tagged to remember where they came from.  the art_tags table
        should have the sketch, digital, and cover tags before taking this step.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=tagart">tag that art!</a></nav>
<?php
      } elseif($status->stepnum < STEP_TAGCOVERS) {
?>
      <p>
        since sketches, digital art, and cover art are all combined now, they
        need to be tagged to remember where they came from.  now that the
        sketches and digital art have been tagged, it’s time to tag the cover
        art.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=tagcovers">tag the covers!</a></nav>
<?php
      } else {
?>
      <p>sketches, digital art, and cover art have been tagged successfully.</p>

<?php
        if($status->stepnum < STEP_COUNTTAGS) {
?>
      <p>
        now that all the art is tagged, it’s time to update the count and last
        used date for each tag.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=counttags">count the tags!</a></nav>
<?php
        } else {
?>
      <h2>comments</h2>
<?php
          if($status->stepnum < STEP_COPYCOVERCOMMENTS) {
?>
      <p>
        cover art was on separate pages, so migrating them is straightforward.
        make sure the art_comments table and its triggers exist (also it must be
        a srctbl option in contributions).
      </p>
      <nav class=calltoaction><a class="okay action" href="?dostep=copycovercomments">copy those comments!</a></nav>
<?php
          } else {
?>
      <p>cover art comments have been copied successfully.</p>

<?php
            if($status->stepnum < STEP_COPYARTCOMMENTS) {
?>
      <p>
        sketches and digital art were on just two pages for all the art of each
        kind, so those comments need to be paired up with the sketch they’re
        commenting on when possible.  others will be lost in time...
      </p>
<?php
              if($comments = $db->query('select id, comments from track7_t7data.comments where page=\'/art/sketch.php\' order by instant'))
                if($arts = $db->query('select id, title from art')) {
                  $artopts = '<option value=0>(ignore)</option>';
                  while($art = $arts->fetch_object())
                    $artopts .= '<option value=' . +$art->id . '>' . htmlspecialchars($art->title) . '</option>';
?>
      <form method=post action="?dostep=copyartcomments">
<?php
                  while($comment = $comments->fetch_object()) {
                    echo $comment->comments;
?>
        <input type=hidden name="cid[]" value="<?php echo $comment->id; ?>">
        <select name="aid[]"><?php echo $artopts; ?></select>
<?php
                  }
?>
        <button>migrate art comments</button>
      </form>
<?php
                }
            } else {
?>
      <p>sketch comments have been copied successfully.</p>

      <h2>votes</h2>
<?php
              if($status->stepnum < STEP_COPYVOTES) {
?>
      <p>
        sketches and digital art have some votes, so bring forward their votes
        and recalculate their ratings.
      </p>
      <nav class=calltoaction><a class="okay action" href="?dostep=copyvotes">copy those votes!</a></nav>
<?php
              } else {
?>
      <p>
        all art votes have been copied and ratings recalculated.  we’re done
        here!
      </p>
<?php
              }
            }
          }
        }
      }
    }
  }
  $html->Close();
?>
