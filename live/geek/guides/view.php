<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if($guide = GetGuide($db, $user, $_GET['guide'])) {
    $page->Start($guide->title . ' - guides', $guide->title, 'by ' . $guide->login . ', ' . GuideDate($user, $guide->dateadded, $guide->dateupdated));
    if($guide->author == $user->ID || $user->GodMode)
      $page->info('as the author of this guide, you may <a href="edit">edit</a> it');
    if($guide->status == 'new')
      $page->info('this guide has not yet been <a href="edit&amp;page=end">submitted for approval</a>, so only you as the author can see it');
    elseif($guide->status == 'pending')
      $page->info('this guide is pending approval from the administrator.&nbsp; it is only visible to you as the author until it is approved.');
?>
      <p>
        <?=$guide->description; ?>

      </p>

      <div class="tagcloud"><?=TagLinks($guide->tags); ?></div>

<?
    // all-pages view
    if($_GET['page'] == 'all')
      for($num = 1; $num <= $guide->pages; $num++)
        ShowPage($db, $user, $page, $guide, $num);
    else {
      if(!is_numeric($_GET['page']) || $_GET['page'] < 1 || $_GET['page'] > $guide->pages)
        $_GET['page'] = 1;
      ShowPage($db, $user, $page, $guide, +$_GET['page']);
    }
?>
      <div id="guidetools">
<?
    ShowTableOfContents($db, $user, $guide, $_GET['page']);
    // DO:  improve rating display
?>
        <p>this entire guide has been rated <?=+$guide->rating; ?> after <?=+$guide->votes; ?> votes.</p>
<?
    // DO:  enable voting
    //$rating = 'select v.vote from votes as v, ratings as r where r.id=v.ratingid and r.type=\'guide\' and r.selector=\'' . $_GET['guide'] . '\' and (v.uid=' . $user->ID . ' or v.ip=\'' . $_SERVER['REMOTE_ADDR'] . '\') order by v.ip';
    //$rating = $db->GetValue($rating, 'error checking to see if you have already voted', '');
    //$vote = new auForm('vote');
    //$vote->AddSelect('vote', 'rating', 'choose your rating of this guide', array(-3 => '-3 (worst)', -2 => '-2', -1 => '-1', 0 => '0 (average)', 1 => '1', 2 => '2', 3 => '3 (best)'), +$rating);
    //$vote->AddButtons('vote', 'cast your vote for this guide');
    //$vote->WriteHTML($user->Valid);
?>
      </div>
<?
    $page->End();
  } else {  // couldn't read guide
    $page->Start('guides');
    $page->End();
  }

  /**
   * Get the guide from the database.
   *
   * @param auDBBase $db Database connection object
   * @param auUserTrack7 $user Currently logged-in user object
   * @param string $id ID of the guide to look up
   * @return object Guide object from the database
   */
  function GetGuide(&$db, &$user, $id) {
    $where = 'g.id=\'' . addslashes($id) . '\' and (g.status=\'approved\'';
    if($user->GodMode)
      $where .= ' or g.status=\'new\' and g.author=\'' . $user->ID . '\' or g.status=\'pending\')';
    elseif($user->Valid)
      $where .= ' or g.author=\'' . $user->ID . '\' and (g.status=\'new\' or g.status=\'pending\'))';
    else
      $where .= ')';
    $guide = 'select g.id, g.pages, g.status, g.tags, g.title, g.description, g.author, g.dateadded, g.dateupdated, u.login, r.rating, r.votes from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where ' . $where;
    return $db->GetRecord($guide, 'error looking up guide content', 'guide content not found', true);
  }
  
  /**
   * Get a page of the guide from the database and display it.
   *
   * @param auDBDase $db Database connection object
   * @param auUserTrack7 $user Currently logged-in user object
   * @param auPage $page Page object for writing headings
   * @param object $guide Current guide object from the database
   * @param integer $num Page number to look up
   */
  function ShowPage(&$db, &$user, &$page, $guide, $num) {
    $pg = 'select heading, content, version from guidepages where guideid=\'' . addslashes($guide->id) . '\' and pagenum=\'' . $num . '\'';
    if($user->ID != $guide->author && !$user->GodMode)
      $pg .= ' and version>-1';
    $pg .= ' order by version';
    if($pg = $db->GetRecord($pg, 'error looking up page ' . $num, 'page ' . $num . ' not found')) {
      if($pg->version < 0)
        if($user->GodMode)
          $page->Heading($pg->heading . ' (new version, not yet approved) <a href="diff' . $num . '">diff</a>');
        else
          $page->Heading($pg->heading . ' (new version, not yet approved)');
      else
        $page->Heading($pg->heading);
      if($user->ID == $guide->author || $user->GodMode)
        $page->Info('as the author of this guide, you may <a href="edit&amp;page=' . $num . '">edit this page</a>');
?>
      <?=$pg->content; ?>

<?
    }
  }

  /**
   * Display table of contents links for the guide.
   *
   * @param auDBBase $db Database connection object
   * @param auUserTrack7 $user Currently logged-in user object
   * @param object $guide Current guide object from the database
   * @param int|string $thisnum Current page number, or 'all'
   */
  function ShowTableOfContents($db, $user, $guide, $thisnum) {
    $pages = 'select heading, pagenum from guidepages where guideid=\'' . addslashes($guide->id) . '\'' . (($user->ID == $guide->author || $user->GodMode) ? '' : ' and version>-1') . ' group by pagenum order by pagenum, version';
    if($pages = $db->Get($pages, 'error looking up pages', 'no pages found')) {
?>
        <ol class="toc">
<?
      while($p = $pages->NextRecord())
        if(+$p->pagenum == +$thisnum) {
?>
          <li class="active">page <?=$p->pagenum; ?>:&nbsp; <?=$p->heading; ?></li>
<?
        } else {
?>
          <li>page <?=$p->pagenum; ?>:&nbsp; <a href="page<?=$p->pagenum; ?>"><?=$p->heading; ?></a></li>
<?
        }
      if($thisnum == 'all') {
?>
          <li class="active">all <?=$guide->pages; ?> pages</li>
<?
      } else {
?>
          <li><a href="all">all <?=$guide->pages; ?> pages</a></li>
<?
      }
?>
          </ol>

<?
    }
  }
  
  /**
   * Show the date added (and updated, if different) for a guide.
   *
   * @param auUserTrack7 $user user viewing the page
   * @param int $added Unix timestamp of when the guide was added to track7
   * @param int $updated Unix timestamp of when the guide was last updated
   * @return formatted date the guide was added and possibly updated.
   */
  function GuideDate($user, $added, $updated) {
    $added = strtolower(auText::SmartTime($added, $user));
    $updated = strtolower(auText::SmartTime($updated, $user));
    if($added == $updated)
      return $added;
    return $added . ' (updated ' . $updated . ')';
  }

  /**
   * Turn a list of tags into HTML links.
   *
   * @param string $tags comma-separated list of tags
   * @return HTML tag links
   */
  function TagLinks($tags) {
    if(!$tags)
      return '<em>(none)</em>';
    $tags = explode(',', $tags);
    foreach($tags as $tag)
      if($tag)
        $links[] = '<a href="/geek/guides/tag=' . $tag . '">' . $tag . '</a>';
    return implode(', ', $links);
  }
?>
