<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';

  if(isset($_GET['guide'])) {
    if($_POST['submit'] == 'vote' && is_numeric($_POST['vote']) && $_POST['vote'] >= -3 && $_POST['vote'] <= 3) {
      $ratingid = 'select id from ratings where type=\'guide\' and selector=\'' . addslashes($_POST['sketch']) . '\'';
      if(false !== ($ratingid = $db->GetValue($ratingid, 'error looking up rating id', '')))
        $ratingid = $db->Put('insert into ratings (type, selector) values (\'guide\', \'' . addslashes($_GET['guide']) . '\')', 'error initializing rating');
      $vote = 'replace into votes (ratingid, vote, uid, ip, time) values (' . $ratingid . ', ' . $_POST['vote'] . ', ' . $user->ID . ', \'' . ($user->Valid ? '' : $_SERVER['REMOTE_ADDR']) . '\', ' . time() . ')';
      if(false !== $db->Change($vote, 'error adding vote')) {
        $rating = 'select sum(vote) as ratesum, count(1) as ratecnt from votes where ratingid=' . $ratingid;
        if($rating = $db->GetRecord($rating, 'error calculating new rating', 'no votes found')) {
          $rating = 'update ratings set rating=' . ($rating->ratesum / $rating->ratecnt) . ', votes=' . $rating->ratecnt . ' where id=' . $ratingid;
          if(false !== $db->Change($rating, 'error updating new rating'))
            $page->Info('vote sucessfully added or updated');
        }
      }
    }
    if($_GET['page'] == 'all') {
      $guide = 'select g.title, g.description, u.login, r.rating, r.votes from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.status=\'approved\' and g.id=\'' . addslashes($_GET['guide']) . '\'';
      if($guide = $db->GetRecord($guide, 'error looking up guide content', 'guide content not found')) {
        $page->Start($guide->title);
?>
      <p>
        <?=$guide->description; ?>

      </p>
<?
        $pages = 'select heading, content from guidepages where guideid=\'' . addslashes($_GET['guide']) . '\' order by pagenum';
        if($pages = $db->Get($pages, 'error looking up guide pages', 'no pages found for guide')) {
          while($p = $pages->NextRecord()) {
            $page->Heading($p->heading);
?>
      <p>
        <?=$p->content; ?>

      </p>

<?
          }
        }
?>
      <p>this guide has been rated <?=+$guide->rating; ?> after <?=+$guide->votes; ?> votes.</p>
<?
        $rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'guide\' and r.selector=\'' . $_GET['guide'] . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
        $rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
        $vote = new auForm('vote');
        $vote->AddSelect('vote', 'rating', 'choose your rating of this guide', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
        $vote->AddButtons('vote', 'cast your vote for this guide');
        $vote->WriteHTML($user->Valid);

        $page->End();
        die;
      }
    } else {
      if(!is_numeric($_GET['page'])) {
        $_GET['page'] = 0;
        $guide = 'select g.title, g.description, u.login, r.rating, r.votes from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.status=\'approved\' and g.id=\'' . addslashes($_GET['guide']) . '\'';
      } else
        $guide = 'select g.title, u.login, p.heading, p.content, r.rating, r.votes from guides as g left join guidepages as p on g.id=p.guideid left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.status=\'approved\' and g.id=\'' . addslashes($_GET['guide']) . '\' and p.pagenum=' . $_GET['page'];
      if($guide = $db->GetRecord($guide, 'error looking up guide content', 'guide content not found')) {
        $page->Start($guide->title);
        if(!$_GET['page']) {
?>
      <p>
        <?=$guide->description; ?>

      </p>
<?
          $pages = 'select heading, pagenum from guidepages where guideid=\'' . addslashes($_GET['guide']) . '\' order by pagenum';
          if($pages = $db->Get($pages, 'error looking up pages', 'no pages found')) {
?>
      <p>
        there are <?=$pages->NumRecords(); ?> pages in this guide:
      </p>
      <ol>
<?
            while($p = $pages->NextRecord()) {
?>
        <li><a href="page<?=$p->pagenum; ?>"><?=$p->heading; ?></a></li>
<?
            }
?>
      </ol>
      <p>alternatively, you can <a href="all">view all pages of this guide together as one page</a>.</p>

<?
          }
        } else {
          $page->Heading($guide->heading);
?>
      <p>
<?=$guide->content; ?>

      </p>

<?
          $nextpage = 'select heading, pagenum from guidepages where guideid=\'' . addslashes($_GET['guide']) . '\' and pagenum=' . ($_GET['page'] + 1);
          if($nextpage = $db->GetRecord($nextpage, 'error looking up next page', '')) {
?>
      <ul>
        <li>next:&nbsp; <a href="page<?=$nextpage->pagenum; ?>"><?=$nextpage->heading; ?></a></li>
      </ul>
<?
          } else {
?>
      <p>
        that should take care of it!&nbsp; if you liked this guide, please rate
        it or leave a comment below.
      </p>
<?
          }
        }
?>
      <p>this guide has been rated <?=+$guide->rating; ?> after <?=+$guide->votes; ?> votes.</p>
<?
        $rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'guide\' and r.selector=\'' . $_GET['guide'] . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
        $rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
        $vote = new auForm('vote');
        $vote->AddSelect('vote', 'rating', 'choose your rating of this guide', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
        $vote->AddButtons('vote', 'cast your vote for this guide');
        $vote->WriteHTML($user->Valid);
      } else {
        $page->Start('guides');
        $page->End();
      }
    }
  } else { // no guide set, show list instead
    $page->ResetFlag(_FLAG_PAGES_COMMENTS);
    $page->Start('guides');
?>
      <p>
        i tend to figure things out on my own as much as i can, but sometimes it
        sure is nice to find helpful information on the internet.&nbsp; with
        that in mind, i've written up a few things i've figured out and think
        might be of use to other people.&nbsp; if you have something you would
        like to contribute, use the link below and i'll review it and probably
        put it up here.
      </p>
      <ul><li><a href="contribute">contribute a guide</a></li></ul>
<?
    switch($_GET['sort']) {
      case 'views':
        $sort = 'most viewed';
        $guides = 's.hits';
        break;
      case 'rating':
        $sort = 'highest rated';
        $guides = 'r.rating';
        break;
      default:
        $sort = 'newest';
        $guides = 'g.dateadded';
        break;
    }
    if($_GET['tag']) {
      $tags = addslashes($_GET['tag']);
      $tags = 'and (tags=\'' . $tags . '\' tags like \'' . $tags . ',%\' or tags like \'%,' . $tags . '\' or tags like \'%,' . $tags . ',%\') ';
    }
    $guides = 'select g.id, g.title, g.description, g.skill, g.tags, g.dateadded, g.pages, g.author, u.login, r.rating, r.votes, s.hits from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector left join hitdetails as s on (s.value=concat(concat(\'/geek/guides/\', g.id), \'/\') and s.type=\'request\' and s.date=\'forever\') where g.status=\'approved\' ' . $tags . 'order by ' . $guides . ' desc';
    if($guides = $db->Get($guides, 'error getting list of guides', 'no guides found')) {
      $page->Heading($sort . ' guides');
?>
      <div id="sortoptions">
        sort guides by:&nbsp;
        <?=$sort == 'newest' ? 'newest' : '<a href="/geek/guides/">newest</a>'; ?> |
        <?=$sort == 'highest rated' ? 'highest rated' : '<a href="sort=rating">highest rated</a>'; ?> |
        <?=$sort == 'most viewed' ? 'most viewed' : '<a href="sort=views">most viewed</a>'; ?>
      </div>

      <dl class="guides">
<?
      while($guide = $guides->NextRecord()) {
?>
        <dt><span class="when"><?=auText::smartTime($guide->dateadded); ?></span><a href="<?=$guide->id; ?>/"><?=$guide->title; ?></a></dt>
        <dd>
          <div class="guideinfo">
            <span>author:&nbsp; <?=$guide->author ? '<a href="/user/' . $guide->login . '/">' . $guide->login . '</a>' : ''; ?></span>
            <span>level:&nbsp; <?=$guide->skill; ?></span>
            <span>pages:&nbsp; <?=$guide->pages; ?></span>
            <span>rated:&nbsp; <?=+$guide->rating; ?> (<?=+$guide->votes; ?> vote<?=$guide->votes != 1 ? 's' : ''; ?>)</span>
            <span>views:&nbsp; <?=+$guide->hits; ?></span>
            <span class="tags">tags:&nbsp; <?=TagLinks($guide->tags); ?></span>
          </div>
          <?=$guide->description; ?>

        </dd>
<?
      }
?>
      </dl>
<?
    }
  }
  $page->End();

  function TagLinks($tags) {
    if(!$tags)
      return '<em>(none)</em>';
    $tags = explode(',', $tags);
    foreach($tags as $tag)
      if($tag)
        $links[] = '<a href="tag=' . $tag . '">' . $tag . '</a>';
    return implode(', ', $links);
  }
?>
