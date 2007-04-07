<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if(isset($_GET['book'])) {
    $book = 'select id,entry,notify from gbbooks where name=\'' . $_GET['book'] . '\'';
    $book = $db->Get($book);
    if($book->IsError())
      die('error reading database when trying to look up guestbook:<br>' . $book->GetMessage());
    if($book->NumRecords() < 1)
      die('could not find a guestbook named \'' . $_GET['book'] . '\' in the database -- nothing to do');
    $book = $book->NextRecord();
    $entry = $book->entry;
    $entry = str_replace(
      array('$$IP$$','$$TIME$$','$$BROWSER$$'),
      array($_SERVER['REMOTE_ADDR'],date('D M j g:i a',time()),$_SERVER['HTTP_USER_AGENT']),
      $entry);
    foreach($_POST as $search => $replace)
      $entry = str_replace('$$' . $search . '$$', eol2br(htmlspecialchars($replace), ''), $entry);
    if(strpos($entry, '$$') !== false) {
?>
  <p style="color: #000000; background-color: #ffffa0; border: 1px solid #808080; margin: 1.5em 3em; padding: 1em 2em; font-family: verdana, sans-serif;">
    Error!&nbsp; Either this guestbook is not configured correctly or you did
    not use the correct form to sign the guestbook.&nbsp; If you used the sign
    the guestbook form and got this error, please contact the administrator of
    that website to fix it.
  </p>
<?
      die;
    }
    $store = 'insert into gbentries (bookid, entry) values (' . $book->id . ', \'' . $entry . '\')';
    $store = $db->Get($store);
    if($store->IsError())
      die('error saving entry into guestbook:<br>' . $store->GetMessage());
    if(strlen($book->notify) > 0) {
      require_once 'mailhost.php';
      @mail($book->notify, 'someone has signed your guestbook!', 'check it out at http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/view.php?book=' . $_GET['book'] . "\n\n" . 'remember you can log in at http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/login.php?book=' . $_GET['book'] . ' to manage your guestbook.', 'From: guestbooks@' . MAIL_HOST . "\r\n" . 'X-Mailer: PHP/' . phpversion());
    }
    unset($book, $entry, $store);
?>
  <p style="color: #000000; background-color: #ffffa0; border: 1px solid #808080; margin: 1.5em 3em; padding: 1em 2em; font-family: verdana, sans-serif;">
    your entry has been saved successfully!&nbsp; you probably want to
    <a href="view.php?book=<?php echo $_GET['book']; ?>">view the guestbook</a> now.
  </p>
<?
  } else {
    echo 'no guestbook to sign!';
  }
?>
