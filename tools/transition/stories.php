<?php
  define('TR_STORIES', 8);
  define('STEP_COPYSERIES', 1);
  define('STEP_COPYSTORIES', 2);
  define('STEP_COPYHTML', 3);
  define('STEP_COPYCOMMENTS', 4);
  define('STEP_ORDERSTORIES', 5);
  define('STEP_COUNTSTORIES', 6);

  define('MAX_HTML', 12);

  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
  $html = new t7html([]);
  $html->Open('story migration');
?>
      <h1>story migration</h1>
<?php
  if(isset($_GET['dostep']))
    switch($_GET['dostep']) {
      case 'copyseries':
        if($db->real_query('insert into stories_series (url, title, deschtml) select id, name, replace(description,\'&nbsp;\',\' \') from track7_t7data.pensections where id in(\'pencil\', \'nancy\', \'children\')'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYSERIES . ', status=\'story series migrated\' where id=' . TR_STORIES . ' and stepnum<' . STEP_COPYSERIES);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copystories':
        if($db->real_query('insert into stories (published, posted, series, number, url, title, deschtml) select 1, if(ps.posted is null, 0, if(char_length(ps.posted)=10, unix_timestamp(concat(right(ps.posted, 4),\'-\',left(ps.posted, 2),\'-\',mid(ps.posted, 4, 2), \' 12:00:00\')), unix_timestamp(concat(right(ps.posted, 4),\'-\',left(ps.posted, 2),\'-15 12:00:00\')))) as postedtimestamp, ss.id, ps.sort, ps.id, ps.title, concat(\'<p>\',replace(replace(ps.description, \'&nbsp;\', \'\'), \'&mdash;\', \'—\'),\'</p>\') as descr from track7_t7data.penstories as ps left join stories_series as ss on ss.url=ps.section where ps.id!=\'ready\' order by postedtimestamp')) {
          $db->real_query('update stories set number=0 where series is null');
          $db->real_query('update transition_status set stepnum=' . STEP_COPYSTORIES . ', status=\'stories migrated\' where id=' . TR_STORIES . ' and stepnum<' . STEP_COPYSTORIES);
        } else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copyhtml':
        if($stories = $db->query('select id, url from stories where storyhtml=\'\' limit ' . MAX_HTML))
          if($stories->num_rows) {
            $id = false;
            $storyhtml = false;
            $import = $db->prepare('update stories set storyhtml=? where id=? limit 1');
            $import->bind_param('si', $storyhtml, $id);
            while($story = $stories->fetch_object()) {
              $id = $story->id;
              $storyhtml = str_replace(['&nbsp;', '&eacute;'], [' ', 'é'], file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/pen/' . $story->url . '.html'));
              $import->execute();
            }
            $import->close();
          } else
            $db->real_query('update transition_status set stepnum=' . STEP_COPYHTML . ', status=\'stories html migrated\' where id=' . TR_STORIES . ' and stepnum<' . STEP_COPYHTML);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'copycomments':
        if($db->real_query('insert into stories_comments (story, posted, user, name, contacturl, html) select s.id, c.instant, u.id, c.name, c.url, replace(c.comments, \'&nbsp;\', \' \') from track7_t7data.comments as c inner join stories as s on s.url=replace(substring_index(c.page,\'/\',-1),\'.php\',\'\') left join transition_users as u on u.olduid=c.uid where page like \'/pen/%\' order by c.instant'))
          $db->real_query('update transition_status set stepnum=' . STEP_COPYCOMMENTS . ', status=\'comments copied\' where id=\'' . TR_STORIES . '\' and stepnum<' . STEP_COPYCOMMENTS);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
      case 'orderstories':
        if(isset($_GET['done']))
          $db->real_query('update transition_status set stepnum=' . STEP_ORDERSTORIES . ', status=\'stories put in order\' where id=\'' . TR_STORIES . '\' and stepnum<' . STEP_ORDERSTORIES);
        elseif(isset($_GET['next']) && +$_GET['next']) {
          if(!$db->real_query('update stories set posted=1+(select * from (select posted from stories where posted<100 order by posted desc limit 1) as strs) where posted=0 and id=\'' . +$_GET['next'] . '\''))
            echo '<pre><code>' . $db->error . '</code></pre>';
        } else
          echo '<p class=error>next story must be specified</p>';
        break;
      case 'countstories':
        if($db->real_query('update stories_series as ss set ss.lastposted=(select posted from stories where series=ss.id order by posted desc limit 1), ss.numstories=(select count(1) from stories where series=ss.id)'))
          $db->real_query('update transition_status set stepnum=' . STEP_COUNTSTORIES . ', status=\'series counted\' where id=\'' . TR_STORIES . '\' and stepnum<' . STEP_COUNTSTORIES);
        else
          echo '<pre><code>' . $db->error . '</code></pre>';
        break;
  }
  if($status = $db->query('select stepnum, status from transition_status where id=' . TR_STORIES))
    $status = $status->fetch_object();
?>
      <h2>series</h2>
<?php
  if($status->stepnum < STEP_COPYSERIES) {
?>
      <p>
        the story migration begins with the series some of the stories belong
        to.  make sure the stories_series table has been created, then copy all
        the series!
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copyseries">copy those series!</a></nav>
<?php
  } else {
?>
      <p>series have been migrated successfully.</p>

      <h2>stories</h2>
<?php
    if($status->stepnum < STEP_COPYSTORIES) {
?>
      <p>
        on to the stories themselves, it’s time to copy the stories table to the
        new database.  make sure the stories and stories_comments tables have
        been created along with their triggers as well as new srctbl and
        conttype enum values in the contributions table, then copy the stories!
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copystories">copy those stories!</a></nav>
<?php
    } else {
?>
      <p>stories have been migrated successfully.</p>
<?php
      if($status->stepnum < STEP_COPYHTML) {
?>
      <p>
        since the actual contents of the stories are currently stored in html
        files, that last step didn’t get that part into the database.  this step
        needs to happen multiple times to get all the stories in.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copyhtml">copy those stories!</a></nav>
<?php
      } else {
?>
      <p>story contents have been migrated successfully.</p>

      <h2>comments</h2>
<?php
        if($status->stepnum < STEP_COPYCOMMENTS) {
?>
      <p>
        some people commented on the stories, so let’s bring those over too.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=copycomments">copy those comments!</a></nav>
<?php
        } else {
?>
      <p>comments have been migrated successfully.</p>

      <h2>story order</h2>
<?php
          if($status->stepnum < STEP_ORDERSTORIES) {
?>
      <p>
        most of the stories are from before i recorded date posted, so the
        database won’t be able to sort them.  select the oldest story from the
        list below:
      </p>
<?php
            if($stories = $db->query('select s.id, s.title, ss.title as stitle, s.number from stories as s left join stories_series as ss on ss.id=s.series where posted=0 order by series, number'))
              if($stories->num_rows) {
?>
      <ul>
<?php
                while($story = $stories->fetch_object()) {
?>
        <li><a href="?dostep=orderstories&amp;next=<?php echo $story->id; ?>"><?php echo $story->title; ?></a><?php if($story->stitle) echo ' (' . $story->stitle . ' #' . $story->number . ')'; ?></li>
<?php
                }
?>
      </ul>
<?php
              } else {
?>
      <nav class=calltoaction><a class="copy action" href="?dostep=orderstories&amp;done">done setting story order!</a></nav>
<?php
              }
            else {
              echo '<pre><code>' . $db->error . '</code></pre>';
            }
          } else {
?>
      <p>stories have been put in order.</p>

      <h2>series stats</h2>
<?php
            if($status->stepnum < STEP_COUNTSTORIES) {
?>
      <p>
        the last step is to count how many stories are in each series and set
        the last posted date for each series.
      </p>
      <nav class=calltoaction><a class="copy action" href="?dostep=countstories">count series stories!</a></nav>
<?php
            } else {
?>
      <p>series stats have been calculated.  we’re done here!</p>
<?php
            }
          }
        }
      }
    }
  }
  $html->Close();
?>
