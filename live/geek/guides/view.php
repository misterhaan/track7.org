<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if($_GET['page'] == 'all') {
    $guide = 'select g.title, g.description, u.login, r.rating, r.votes from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.status=\'approved\' and g.id=\'' . addslashes($_GET['guide']) . '\'';
    if($guide = $db->GetRecord($guide, 'error looking up guide content', 'guide content not found')) {
      $page->Start($guide->title);
      // DO:  add edit link for admin / author
?>
      <p>
        <?=$guide->description; ?>

      </p>
<?
      $pages = 'select heading, content from guidepages where guideid=\'' . addslashes($_GET['guide']) . '\' and version=0 order by pagenum';
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
      // DO:  enable voting for all-pages view
      //$rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'guide\' and r.selector=\'' . $_GET['guide'] . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
      //$rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
      //$vote = new auForm('vote');
      //$vote->AddSelect('vote', 'rating', 'choose your rating of this guide', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
      //$vote->AddButtons('vote', 'cast your vote for this guide');
      //$vote->WriteHTML($user->Valid);

      $page->End();
      die;
    }
  } else {
    if(!is_numeric($_GET['page'])) {
      $_GET['page'] = 0;
      $guide = 'select g.title, g.description, u.login, r.rating, r.votes from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.status=\'approved\' and g.id=\'' . addslashes($_GET['guide']) . '\'';
    } else
      $guide = 'select g.title, u.login, p.heading, p.content, r.rating, r.votes from guides as g left join guidepages as p on g.id=p.guideid left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.status=\'approved\' and g.id=\'' . addslashes($_GET['guide']) . '\' and p.pagenum=' . +$_GET['page'];
    if($guide = $db->GetRecord($guide, 'error looking up guide content', 'guide content not found')) {
      $page->Start($guide->title);
      // DO:  add edit link for admin / author
      if(!$_GET['page']) {
?>
      <p>
        <?=$guide->description; ?>

      </p>
<?
        $pages = 'select heading, pagenum from guidepages where guideid=\'' . addslashes($_GET['guide']) . '\' and version=0 order by pagenum';
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
      <p><?=$guide->content; ?></p>

<?
        $nextpage = 'select heading, pagenum from guidepages where guideid=\'' . addslashes($_GET['guide']) . '\' and version=0 and pagenum=' . ($_GET['page'] + 1);
        if($nextpage = $db->GetRecord($nextpage, 'error looking up next page', '')) {
?>
      <ul>
        <li>next:&nbsp; <a href="page<?=$nextpage->pagenum; ?>"><?=$nextpage->heading; ?></a></li>
      </ul>
<?
        } else {
?>
      <p>
        if you liked this guide, please rate it or leave a comment below.
      </p>
<?
        }
      }
?>
      <p>this guide has been rated <?=+$guide->rating; ?> after <?=+$guide->votes; ?> votes.</p>
<?
      // DO:  enable voting
      /*$rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'guide\' and r.selector=\'' . $_GET['guide'] . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
      $rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
      $vote = new auForm('vote');
      $vote->AddSelect('vote', 'rating', 'choose your rating of this guide', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
      $vote->AddButtons('vote', 'cast your vote for this guide');
      $vote->WriteHTML($user->Valid);*/
      $page->End();
    } else {
      $page->Start('guides');
      $page->End();
    }
  }
?>
