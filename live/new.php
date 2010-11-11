<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->AddFeed('track7 site updates', '/feeds/updates.rss');
  $page->Start('new + exciting stuff', 'track7 update history<a class="feed" href="/feeds/updates.rss" title="rss feed of site update history"><img src="/style/feed.png" alt="feed" /></a>');

  if($user->GodMode) {
    require_once 'auForm.php';
    require_once 'auText.php';
    if(isset($_POST['change']) && strlen($_POST['change']) > 5) {
      if(!isset($_POST['date']) || strlen($_POST['date']) <= 0)
        $_POST['date'] = time();
      else
        $_POST['date'] = strtotime($_POST['date']);
      $ins = 'insert into updates (instant, `change`) values (\'' . $_POST['date'] . '\', \'' . addslashes($_POST['change']) . '\')';
      if($db->Change($ins, 'error saving update')) {
        // tweet this update
        $twurl = auSend::Bitly('http://' . str_replace('m.', 'www.', $_SERVER['HTTP_HOST']) . $_SERVER['PHP_SELF']);
        $len = 139 - strlen($twurl);
        $tweet = strip_tags($_POST['change']);
        if(mb_strlen($tweet, _CHARSET) > $len)
          $tweet = mb_substr($tweet, 0, $len - 1, _CHARSET) . '…';
        auSend::Tweet($tweet . ' ' . $twurl);
        // send e-mail to everybody who wants to know
        if(isset($_POST['content']))
          $mail = _FLAG_USERCONTACT_NOTIFYNEWCONTENT | _FLAG_USERCONTACT_NOTIFYNEWANYTHING;
        else
          $mail = _FLAG_USERCONTACT_NOTIFYNEWANYTHING;
        $mail = 'select c.email, u.login from usercontact as c, users as u where c.uid=u.uid and c.flags & ' . $mail;
        if($mail = $db->Get($mail, 'error reading list of people who want to know about this update', '')) {
          $page->Info('found ' . $mail->NumRecords() . ' users who want to know about this update');
?>
      <ul>
<?
          while($address = $mail->NextRecord()) {
            $addresses[] = $address->email;
?>
        <li><?=$address->login; ?></li>
<?
          }
?>
      </ul>
<?
          if(count($addresses))
            $addresses = implode(', ', $addresses);
          else
            $addresses = '';
          $_POST['change'] = 'the following change has been made at track7:' . "\r\n\r\n" . $_POST['change'] . "\r\n\r\n" . 'visit http://' . $_SERVER['HTTP_HOST'] . '/new.php for more updates, or http://' . $_SERVER['HTTP_HOST'] . '/user/profile.php' . ' to change your e-mail preferences.';
          auSend::EMail('track7 update', strip_tags($_POST['change']), 'updates@' . _HOST, 'updates@' . _HOST, 'track7 updates', 'track7 updates', false, $addresses);
        }
      }
    }
    if(isset($_GET['add'])) {
?>
      <h2>enter an update</h2>
<?
      $updateform = new auForm('siteupdate');
      $updateform->AddField('change', 'change', 'enter the description of what changed', true, '', _AU_FORM_FIELD_MULTILINE);
      $updateform->AddField('content', '', 'this update adds new content', true, false, _AU_FORM_FIELD_CHECKBOX);
      $updateform->AddField('date', 'date', 'the date on which this change was made');
      $updateform->AddButtons('save', 'add this update');
      $updateform->WriteHTML(true);
    } else {
?>
      <ul><li><a href="new.php?add">add an update</a></li></ul>

<?
    }
  }

  if(!is_numeric($_GET['show']))
    $_GET['show'] = 90;
  if(!is_numeric($_GET['skip']))
    $_GET['skip'] = 0;
  $updates = 'select instant, `change` from updates where instant>' . (time() - 86400 * ($_GET['skip'] + $_GET['show']));
  if($_GET['skip'])
    $updates .= ' and instant<=' . (time() - 86400 * $_GET['skip']);
  $updates .= ' order by instant desc';
  if($updates = $db->Get($updates, 'error reading updates')) {
    $lastdate = '';
    while($update = $updates->NextRecord()) {
      if(date('Ymd', $update->instant) != $lastdate) {
        if(strlen($lastdate) > 1)
          echo "      </ul>\n\n";
        $lastdate = date('Ymd', $update->instant);
?>
      <h2><?=strtolower(date('j F Y', strtotime($lastdate))); ?></h2>
      <ul>
<?
      }
?>
        <li><?=$update->change; ?></li>
<?
    }
    if($updates->NumRecords())
      echo "      </ul>\n\n";
?>
      <p>
        * found <?=0 + $updates->NumRecords(); ?> update<?=($updates->NumRecords() != 1 ? 's' : ''); ?> between <?=strtolower(date('j M Y', time() - 86400 * ($_GET['skip'] + $_GET['show']))); ?> and <?=strtolower(date('j M Y', time() - 86400 * $_GET['skip'])); ?>
      </p>

<?
    $lastdate = 'select instant from updates order by instant';
    if($lastdate = $db->GetRecord($lastdate, 'unable to find date of oldest update', '')) {
      $lastdate = $lastdate->instant;
?>
      <div class="pagelinks">
        page:&nbsp;
<?
      $lastpage = (time() - $lastdate) / 86400 / $_GET['show'];
      $thispage = round($_GET['skip'] / $_GET['show']);
      if($_GET['show'] != 90)
        $show = 'show=' . $_GET['show'];
      for($p = 0; $p <= $lastpage; $p++) {
        if($p == $thispage)
          echo '        <span class="active">' . ($p + 1) . "</span>\n";
        elseif($p > 0)
          echo '        <a href="new.php?skip=' . ($p * $_GET['show']) . ($show ? '&amp;' . $show : '') . '">' . ($p + 1) . "</a>\n";
        else
          echo '        <a href="new.php' . ($show ? '?' . $show : '') . '">1</a>' . "\n";
      }
?>
      </div>
<?
    }
  }

  $page->End();
?>
