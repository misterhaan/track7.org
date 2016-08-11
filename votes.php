<?
  if(isset($_GET['ajax'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'cast':
        if(isset($_POST['type']) && isset($_POST['key']) && isset($_POST['vote']))
          switch($_POST['type']) {
            case 'guide':
            case 'art':
              if($db->real_query('insert into ' . $_POST['type'] . '_votes (' . $_POST['type'] . ', voter, ip, vote, posted) values (\'' . $db->escape_string($_POST['key']) . '\', \'' . ($user->IsLoggedIn() ? +$user->ID : 0) . '\', ' . ($user->IsLoggedIn() ? 0 : 'inet_aton(\'' . $_SERVER['REMOTE_ADDR'] . '\')') . ', \'' . +$_POST['vote'] . '\', \'' . +time() . '\') on duplicate key update vote=\'' . +$_POST['vote'] . '\', posted=\'' . +time() .'\'')) {
                $ajax->Data->vote = +$_POST['vote'];
                if($db->real_query('update ' . $_POST['type'] . ($_POST['type'] == 'guide' ? 's' : '') . ' set rating=(select round((sum(vote)+3)/(count(vote)+1), 2) from ' . $_POST['type'] . '_votes where ' . $_POST['type'] . '=\'' . $db->escape_string($_POST['key']) . '\' group by ' . $_POST['type'] . '), votes=(select count(vote) from ' . $_POST['type'] . '_votes where ' . $_POST['type'] . '=\'' . $db->escape_string($_POST['key']) . '\' group by ' . $_POST['type'] . ') where id=\'' . $db->escape_string($_POST['key']) . '\'')) {
                  if($gi = $db->query('select rating, votes from ' . $_POST['type'] . ($_POST['type'] == 'guide' ? 's' : '') . ' where id=\'' . $db->escape_string($_POST['key']) . '\' limit 1'))
                    if($gi = $gi->fetch_object()) {
                      $ajax->Data->rating = +$gi->rating;
                      $ajax->Data->votes = +$gi->votes;
                    }
                }
              } else
                $ajax->Fail('error recording your rating.');
              break;
            default:
              $ajax->Fail('unknown type.  known types are:  guide.');
          }
        else
          $ajax->Fail('missing at least one required parameter:  type, key, and vote.');
        break;
      default:
        $ajax->Fail('unknown function name.  supported function names are: cast.');
        break;
    }
    $ajax->Send();
    die;
  }

  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  if(is_numeric($_GET['delete']) && $user->GodMode) {
    $rating = 'select ratingid from votes where id=\'' . +$_GET['delete'] . '\'';
    if($rating = $db->GetValue($rating, 'error finding which rating this vote is for', 'rating not found')) {
      $del = 'delete from votes where id=\'' . +$_GET['delete'] . '\'';
      if(false !== $db->Change($del, 'error deleting vote')) {
        $stats = 'select sum(vote) as totalrate, count(1) as count from votes where ratingid=\'' .+$_GET['delete'] . '\'';
        if($stats = $db->GetRecord($stats, 'error re-calculating rating')) {
          if($stats->count)
            $rating = 'update ratings set rating=\'' . (1.0 * $stats->totalrate / $stats->count) . '\', votes=\'' . $stats->count . '\' where id=\'' . $rating . '\'';
          else
            $rating = 'update ratings set rating=0, votes=0 where id=\'' . $rating . '\'';
          if(false !== $db->Change($rating, 'error updating rating'))
            $page->Info('vote deleted successfully');
        }
      }
    }
  }

  if(is_numeric($_GET['vote']) && $_GET['type'] && $_GET['selector']) {
    if(!validType($_GET['type'], $db)) {
      $page->Error('invalid type — cannot vote');
      if($_POST['return'] == 'xml')
        $page->SendXmlErrors();
    } else {
      $castvote = getVoteForm($_GET['type'], $_GET['selector'], +$_GET['vote']);
      if($castvote->CheckInput($user->Valid)) {
        $ratingid = getRating($db, $_GET['type'], $_GET['selector']);
        $ins = 'replace into votes (ratingid, vote, uid, ip, time) values (' . $ratingid . ', ' . $_POST['vote'] . ', ' . $user->ID . ', \'' . ($user->Valid ? '' : $_SERVER['REMOTE_ADDR']) . '\', ' . time() . ')';
        if(false !== $db->Put($ins, 'error saving vote')) {
          $rating = 'select sum(vote) ratesum, count(1) ratecnt from votes where ratingid=' . $ratingid;
          if($rating = $db->GetRecord($rating, 'error calculating new rating', 'no votes found')) {
            $update = 'update ratings set rating=' . ($rating->ratesum / ($rating->ratecnt + 1)) . ', votes=' . $rating->ratecnt . ' where id=' . $ratingid;
            if(false !== $db->Change($update, 'error updating new rating')) {
              if($_POST['return'] == 'xml') {
                header('Content-Type: text/xml; charset=utf-8');
                header('Cache-Control: no-cache');
                echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<response result="success">
<vote><?=$_POST['vote']; ?></vote>
<rating><?=round($rating->ratesum / ($rating->ratecnt + 1), 1); ?></rating>
<votes><?=$rating->ratecnt; ?></votes>
</response>
<?
              } else
                header('Location: http://' . $_SERVER['HTTP_HOST'] . getItemUrl($_GET['type'], $_GET['selector']));
              die;
            }
          }
        }
      }
      if($_POST['return'] == 'xml')
        $page->SendXmlErrors();
      $page->Start('confirm vote');
      $castvote->WriteHTML($user->Valid);
      $page->End();
      die;
    }
  }

  $page->Start('votes');

  $votes = 'select v.id, r.type, r.selector, v.vote, v.uid, u.login, v.ip, v.time from votes as v left join users as u on u.uid=v.uid left join ratings as r on r.id=v.ratingid order by v.time desc';
  if($votes = $db->GetSplit($votes, 50, 0, '', '', 'error looking up votes', 'no votes found')) {
    echo "      <table class=\"text\" cellspacing=\"0\">\n";
    echo '        <thead><tr><th>date</th><th>vote</th><th>item</th>';
    if($user->GodMode)
      echo '<th>user / ip</th>';
    echo "</tr></thead>\n";
    echo "        <tbody>\n";
    while($vote = $votes->NextRecord()) {
      echo '          <tr><td>';
      echo strtolower(auText::SmartTime($vote->time, $user));
      echo '</td><td>';
      echo showVote($vote->vote);
      echo '</td><td>';
      echo $vote->type . ':&nbsp; ';
      echo '<a href="';
      echo getItemUrl($vote->type, $vote->selector);
      echo '">';
      echo $vote->selector;
      echo '</a>';
      if($user->GodMode) {
        echo '</td><td>';
        if($vote->uid)
          echo '<a href="/user/' . $vote->login . '/">' . $vote->login . '</a>';
        else
          echo $vote->ip;
        echo '</td><td>';
        echo '<a href="?delete=' . $vote->id . '"><img src="/images/del.png" alt="del" /></a>';
      }
      echo "</td></tr>\n";
    }
    echo "        </tbody>\n";
    echo "      </table>\n";
    $page->SplitLinks();
  }
  $page->End();

  /**
   * Checks whether a vote type is valid.
   * @param string $type Vote type to check.
   * @param auDB $db Database object so vote types can be looked up.
   * @return boolean Whether the vote type is valid.
   */
  function validType($type, &$db) {
    if($types = $db->Get('show columns from ratings like \'type\'')) {
      $types = $types->NextRecord();
      $types = explode('\',\'', substr($types->Type, 6, -2));
      return in_array($type, $types);
    }
    return false;
  }

  /**
   * Create and return an auForm object to allow the user to vote.
   * @param string $type Type of item being voted on.  This should have already passed validType().
   * @param string $selector Unique identifier for this item within its type.
   * @param integer $vote Default vote to show in the form.
   * @return auForm Vote confirmation form.
   */
  function getVoteForm($type, $selector, $vote = 0) {
    $frm = new auForm('vote', '?type=' . $type . '&selector=' . $selector . '&vote=' . $vote);
    $frm->Add(new auFormText($type, $selector));
    $frm->Add(new auFormSelect('vote', 'rating', 'choose your rating of this item', true, array(-3 => 'three thumbs down — impossibly intolerable', -2 => 'two thumbs down — fully intolerable', -1 => 'one thumb down — partly intolerable', 0 => 'no thumbs — indifferent', 1 => 'one thumb up — partly amazing', 2 => 'two thumbs up — fully amazing', 3 => 'three thumbs up — impossibly amazing'), $vote));
    $frm->Add(new auFormButtons('save', 'save your rating'));
    return $frm;
  }

  /**
   * Return the URL for the item specified by the type and selector.
   * @param string $type Type of item.
   * @param string $selector Unique identifier for an item of this type.
   * @return string URL for the item.
   */
  function getItemUrl($type, $selector) {
    switch($type) {
      case 'lego':    return '/output/lego/#' . $selector;
      case 'sketch':  return '/output/gfx/sketch.php#' . $selector;
      case 'digital': return '/output/gfx/digital.php#' . $selector;
      case 'task':    return '/todo.php?id=' . $selector;
      case 'guide':   return '/geek/guides/' . $selector . '/';
    }
    return '';
  }

  /**
   * Finds the rating ID for a given type and selector.  Rating is created if it does not yet exist.
   * @param auDB $db Database connection.
   * @param string $type Type of rating to look up / create.
   * @param string $selector Which rating to look up / create.
   * @return integer Raating ID.
   */
  function getRating(&$db, $type, $selector) {
    $type = addslashes($type);
    $selector = addslashes($selector);
    $rating = $db->GetValue('select id from ratings where type=\'' . $type . '\' and selector=\'' . $selector . '\'', '', '');
    if(!$rating)
      $rating = $db->Put('insert into ratings (type, selector) values (\'' . $type . '\', \'' . $selector . '\')');
    return $rating;
  }

  /**
   * Shows a vote as a graphic with a number of thumbs.
   * @param integer $vote Vote to show (from -3 through 3).
   * @return string HTML showing the vote.
   */
  function showVote($vote) {
    if(!$vote)
      return '<div class="vote" title="no thumbs — indifferent"><img src="/images/vote/none.png" alt="none" /></div>';
    $ret = '<div class="vote" title="';
    $ret .= describeVote($vote);
    $ret .= '">';
    if($vote < 0)
      for($v = $vote; $v < 0; $v++)
        $ret .= '<img src="/images/vote/down.png" alt="down" />';
    else
      for($v = $vote; $v > 0; $v--)
        $ret .= '<img src="/images/vote/up.png" alt="up" />';
    $ret .= '</div>';
    return $ret;
  }

  /**
   * Shows a vote as a description of what the number means.
   * @param integer $vote Vote to describe.
   * @return string Description of the vote.
   */
  function describeVote($vote) {
    switch($vote) {
      case -3: return 'three thumbs down — impossibly intolerable';
      case -2: return 'two thumbs down — fully intolerable';
      case -1: return 'one thumb down — partly intolerable';
      case  0: return 'no thumbs — indifferent';
      case  1: return 'one thumb up — partly amazing';
      case  2: return 'two thumbs up — fully amazing';
      case  3: return 'three thumbs up — impossibly amazing';
    }
  }
?>
