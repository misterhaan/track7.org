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
      $guides = 'r.rating';
      break;
    default:
      $sort = 'newest';
      $guides = 'g.dateadded';
      break;
  }
  if($tag) {
    $tags = addslashes($_GET['tag']);
    $tags = 'and (g.tags=\'' . $tags . '\' or g.tags like \'' . $tags . ',%\' or g.tags like \'%,' . $tags . '\' or g.tags like \'%,' . $tags . ',%\') ';
  }
  $guides = 'select g.id, g.title, g.description, g.skill, g.tags, g.dateadded, g.pages, g.author, u.login, r.rating, r.votes, s.hits from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector left join hitdetails as s on (s.value=concat(concat(\'/geek/guides/\', g.id), \'/\') and s.type=\'request\' and s.date=\'forever\') where g.status=\'approved\' ' . $tags . 'order by ' . $guides . ' desc';
  if($guides = $db->Get($guides, 20, 0, '', '', 'error getting list of guides', 'no guides found')) {
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
        <dt><span class="when"><?=auText::smartTime($guide->dateadded, $user); ?></span><a href="<?=$guide->id; ?>/"><?=$guide->title; ?></a></dt>
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
    $page->SplitLinks();
  }
  $page->End();
?>
