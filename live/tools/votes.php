<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';

  $page->Start('votes');

  $votes = 'select r.type, r.selector, v.vote, v.uid, u.login, v.ip, v.time from ratings as r, votes as v left join users as u on u.uid=v.uid where v.ratingid=r.id order by v.time desc';
  if($votes = $db->GetSplit($votes, 20, 0, '', '', 'error looking up votes', 'no votes have been cast')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>time</th><th>user / ip</th><th>vote</th></tr></thead>
        <tbody>
<?
    while($vote = $votes->NextRecord()) {
?>
          <tr><td><?=auText::SmartTime($vote->time); ?></td><td><?=$vote->uid ? '<a href="/user/' . $vote->login . '/">' . $vote->login . '</a>' : $vote->ip; ?></td><td><?=$vote->vote; ?> on <?=$vote->selector . ' (' . $vote->type . ')'; ?></td></tr>
<?
    }
?>
        </tbody>
      </table>
<?
    $page->SplitLinks();
  }

  $page->End();
?>
