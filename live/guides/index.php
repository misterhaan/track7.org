<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  if(isset($_GET['tag'])) {
    $tag = htmlspecialchars($_GET['tag']);
    $page->AddFeed('guides [' . $tag . ']', '/feeds/guides.rss?tags=' . $tag);
    $page->Start($tag . ' - guides', 'guides [' . $tag . ']<a class="feed" href="/feeds/guides.rss?tags=' . $tag . '" title="rss feed of ' . $tag . ' guides"><img src="/style/feed.png" alt="feed" /></a>');
    $page->ShowTagDescription('guides', $tag);
    if($user->GodMode)
      $page->Info('<a href="/tools/taginfo.php?type=guides&amp;name=' . $tag . '">add/edit tag description</a>');
  } else {
    $tag = false;
    $page->AddFeed('guides', '/feeds/guides.rss');
    $page->Start('guides', 'guides<a class="feed" href="/feeds/guides.rss" title="rss feed of all guides"><img src="/style/feed.png" alt="feed" /></a>');
?>
      <p>
        i tend to figure things out on my own as much as i can, but sometimes it
        sure is nice to find helpful information on the internet.&nbsp; with
        that in mind, i’ve written up a few things i’ve figured out and think
        might be of use to other people.&nbsp; if you have something you would
        like to contribute, use the link below and i’ll review it and probably
        put it up here.
      </p>
<?
  }
?>
      <ul><li><a href="contribute">contribute a guide</a></li></ul>
<?
  if(!$tag)
    $page->TagCloud('guides', 'tag=');
  switch($_GET['sort']) {
    case 'views':
      $sort = 'most viewed';
      $guides = 's.hits';
      break;
    case 'rating':
      $sort = 'highest rated';
      $guides = 'rating';
      break;
    case 'added':
      $sort = 'newest';
      $guides = 'g.dateadded';
    default:
      $sort = 'last updated';
      $guides = 'g.dateupdated';
      break;
  }
  if($tag) {
    $tags = addslashes($_GET['tag']);
    $tags = 'and (g.tags=\'' . $tags . '\' or g.tags like \'' . $tags . ',%\' or g.tags like \'%,' . $tags . '\' or g.tags like \'%,' . $tags . ',%\') ';
  } elseif($user->GodMode) {
    $pendguides = 'select g.id, g.title, g.description, g.status, g.pages, u.login from guides as g left join users as u on u.uid=g.author where status=\'new\' or status=\'pending\'';
    if($pendguides = $db->Get($pendguides, 'error checking for pending guides', '')) {
      $page->Heading('pending guides');
?>
      <dl class="guides">
<?
      while($guide = $pendguides->NextRecord()) {
?>
        <dt><a href="<?=$guide->id; ?>/"><?=$guide->title; ?></a></dt>
        <dd>
          <div class="guideinfo">
            <span>status:&nbsp; <?=$guide->status; ?></span>
            <span>author:&nbsp; <a href="/user/<?=$guide->login; ?>/"><?=$guide->login; ?></a></span>
            <span>pages:&nbsp; <?=$guide->pages; ?></span>
          </div>
          <?=$guide->description; ?>

        </dd>
<?
      }
?>
      </dl>
<?
    }
  } elseif($user->Valid) {
    $userguides = 'select id, title, description, status, pages from guides where author=\'' . $user->ID . '\' and (status=\'new\' or status=\'pending\')';
    if($userguides = $db->Get($userguides, 'error checking for your pending guides', '')) {
      $page->Heading($user->Name . '’s pending guides');
?>
      <dl class="guides">
<?
      while($guide = $userguides->NextRecord()) {
?>
        <dt><a href="<?=$guide->id; ?>/"><?=$guide->title; ?></a></dt>
        <dd>
          <div class="guideinfo">
            <span>status:&nbsp; <?=$guide->status; ?></span>
            <span>author:&nbsp; <a href="/user/<?=$user->Name; ?>/"><?=$user->Name; ?></a></span>
            <span>pages:&nbsp; <?=$guide->pages; ?></span>
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
  $guides = 'select g.id, g.title, g.description, g.skill, g.tags, g.dateadded, g.dateupdated, g.pages, g.author, u.login, ifnull(r.rating,0) as rating, ifnull(r.votes,0) as votes, s.hits from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector left join hitdetails as s on (s.value=concat(concat(\'/guides/\', g.id), \'/\') and s.type=\'request\' and s.date=\'forever\') where g.status=\'approved\' ' . $tags . 'order by ' . $guides . ' desc';
  if($guides = $db->GetSplit($guides, 20, 0, '', '', 'error getting list of guides', 'no guides found')) {
    $page->Heading($sort . ' guides');
?>
      <div id="sortoptions">
        sort guides by:&nbsp;
        <?=$sort == 'last updated' ? 'last updated' : '<a href="/guides/">last updated</a>'; ?>  |
        <?=$sort == 'newest' ? 'newest' : '<a href="sort=added">newest</a>'; ?> |
        <?=$sort == 'highest rated' ? 'highest rated' : '<a href="sort=rating">highest rated</a>'; ?> |
        <?=$sort == 'most viewed' ? 'most viewed' : '<a href="sort=views">most viewed</a>'; ?>
      </div>

      <dl class="guides">
<?
    while($guide = $guides->NextRecord()) {
?>
        <dt><span class="when"><?=GuideDate($user, $guide->dateadded, $guide->dateupdated); ?></span><a href="<?=$guide->id; ?>/"><?=$guide->title; ?></a></dt>
        <dd>
          <div class="guideinfo">
            <span>author:&nbsp; <?=$guide->author ? '<a href="/user/' . $guide->login . '/">' . $guide->login . '</a>' : ''; ?></span>
            <span>level:&nbsp; <?=$guide->skill; ?></span>
            <span>pages:&nbsp; <?=$guide->pages; ?></span>
            <span>rated:&nbsp; <?=round($guide->rating, 2); ?> (<?=+$guide->votes; ?> vote<?=$guide->votes != 1 ? 's' : ''; ?>)</span>
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
    $page->SplitLinks();
  }
  $page->End();

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
        $links[] = '<a href="tag=' . $tag . '">' . $tag . '</a>';
    return implode(', ', $links);
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
?>
