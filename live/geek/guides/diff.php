<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(!$user->GodMode) {
    $page->Show404();
    die;
  }
  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  $guide = 'select g.id, g.pages, g.status, g.tags, g.title, g.description, g.author, g.dateadded, g.dateupdated, u.login, r.rating, r.votes from guides as g left join users as u on g.author=u.uid left join ratings as r on g.id=r.selector where g.id=\'' . addslashes($_GET['guide']) . '\'';
  if($guide = $db->GetRecord($guide, 'error looking up guide', 'guide not found')) {
    $pold = 'select heading, content from guidepages where guideid=\'' . $guide->id . '\' and pagenum=\'' . addslashes($_GET['page']) . '\' and version=0';
    if($pold = $db->GetRecord($pold, 'error looking up old version of page', 'old version of page not found')) {
      $pnew = 'select heading, content from guidepages where guideid=\'' . $guide->id . '\' and pagenum=\'' . addslashes($_GET['page']) . '\' and version=-1';
      if($pnew = $db->GetRecord($pnew, 'error looking up new version of page', 'new version of page not found')) {
        if(isset($_GET['approve'])) {
          $update = 'update guidepages set version=version+1 where guideid=\'' . $guide->id . '\' and pagenum=\'' . addslashes($_GET['page']) . '\' order by version desc';
          if(false !== $db->Change($update, 'error approving new version of this page')) {
            $db->Change('update guides set dateupdated=\'' . time() . '\' where id=\'' . $guide->id . '\'');
            header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id . (+$_GET['page'] == 1 ? '/' : '/page' . +$_GET['page']));
            die;
          }
        }
        $_SERVER['HTTP_ACCEPT'] = 'text/html';  // override xhtml content-type so bad xhtml can still display as html
        $page->Start('highlight changes', 'highlight changes', $guide->title);
        $page->Info('comparing old and new version of page ' . htmlspecialchars($_GET['page']));
        $page->Heading(auText::Diff($pold->heading, $pnew->heading));
        echo auText::Diff($pold->content, $pnew->content);
?>
      <ul>
        <li><a href="edit&amp;page=<?=htmlspecialchars($_GET['page']); ?>">edit</a></li>
        <li><a href="diff<?=htmlspecialchars($_GET['page']); ?>&amp;approve">approve</a></li>
      </ul>
<?
      }
    }
  }
  $page->Start('highlight changes');
  $page->End();
?>
