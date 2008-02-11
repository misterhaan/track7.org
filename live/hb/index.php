<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'hb.inc';

  if(strlen($_GET['tag'])) {
    $page->Start(htmlentities($_GET['tag']) . ' threads', 'thread listing [' . htmlentities($_GET['tag']) .']');
    $page->ShowTagDescription('threads', addslashes($_GET['tag']));
    if($user->GodMode)
      $page->Info('<a href="/tools/taginfo.php?type=threads&amp;name=' . htmlentities($_GET['tag']) . '">add/edit tag description</a>');
    $threads = ' where t.tags=\'' . addslashes($_GET['tag']) . '\' or t.tags like \'' . addslashes($_GET['tag']) . ',%\' or t.tags like \'%,' . addslashes($_GET['tag']) . '\' or t.tags like \'%,' . addslashes($_GET['tag']) . ',%\'';
  } else {
    $page->Start('threads', 'thread listing');
    $page->TagCloud('threads', '?tag=', 10, 15, 25, 40);
  }
?>
      <ul><li><a href="newthread">start a new thread</a></li></ul>
<?
  $threads = 'select t.id, t.tags, t.title, t.instant, tu.login, t.posts, t.lastpost, p.number, p.subject, p.instant as pinstant, pu.login as plogin from hbthreads as t left join users as tu on tu.uid=t.uid left join hbposts as p on p.id=t.lastpost left join users as pu on pu.uid=p.uid' . $threads . ' order by p.instant desc';
  if($threads = $db->GetSplit($threads, 20, 0, '', '', 'error looking up threads', 'no threads found')) {
?>
      <table class="text" id="hbthreadlist" cellspacing="0">
        <thead class="minor"><tr><th>thread</th><th>tags</th><th>posts</th><th>last post</th><th>started</th></tr></thead>
        <tbody>
<?
    while($thread = $threads->NextRecord()) {
      // show list of threads
      $subject = html_entity_decode($thread->subject, ENT_COMPAT, _CHARSET);
      if(strlen($subject) > 16)
        $subject = substr($subject, 0, 15) . '...';
      $subject = htmlentities($subject, ENT_COMPAT, _CHARSET);
?>
          <tr><td><a href="/hb/thread<?=$thread->id; ?>/"><?=$thread->title; ?></a></td><td class="detail"><?=HB::TagLinks($thread->tags); ?></td><td class="number"><?=$thread->posts; ?></td><td class="detail"><a href="/hb/thread<?=$thread->id; ?>/<?=$thread->posts > _FORUM_POSTS_PER_PAGE ? 'skip=' . (floor(($thread->posts - 1) / _FORUM_POSTS_PER_PAGE) * _FORUM_POSTS_PER_PAGE) : ''; ?>#p<?=$thread->lastpost; ?>"<?=$subject != $thread->subject ? ' title="' . $thread->subject . '"' : ''; ?>><?=$subject; ?></a> <?=$user->tzdate('Y-m-d g:i a', $thread->pinstant) . ' by ' . ($thread->plogin ? $thread->plogin : 'anonymous'); ?></td><td class="detail"><?=$user->tzdate('Y-m-d g:i a', $thread->instant) . ' by ' . ($thread->login ? $thread->login : 'anonymous'); ?></td></tr>
<?
    }
?>
        </tbody>
        <tfoot><tr><td colspan="5">
<?
      $page->SplitLinks();
?>
        </td></tr></tfoot>
      </table>
      <ul><li><a href="newthread">start a new thread</a></li></ul>
<?
  }
  $page->End();
?>
