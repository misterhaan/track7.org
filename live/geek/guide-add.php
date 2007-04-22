<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auText.php';
  require_once 'auForm.php';

  $page->ResetFlag(_FLAG_PAGES_COMMENTS);
  
  if(!$user->Valid) {
    $page->Start('contribute a guide - geek', 'contribute a guide');
    $page->Heading('login required');
?>
      <p>
        guides may only be contributed by registered users.&nbsp; if you already
        have an account, you will need to <a href="/user/login.php">log in</a>
        &mdash; if you do not have an account you will need to <a href="/user/register.php">register</a>.
      </p>
      
<?
    $page->End();
    die;
  }
  
  if($_POST['formid'] == 'newguide') {
    if(!strlen($_POST['title'] = trim($_POST['title'])))
      $page->Error('you must specify a title for your guide');
    elseif(!is_numeric($_POST['pages']) || $_POST['pages'] < 1 || $_POST['pages'] > 9)
      $page->Error('your guide must contain between 1 and 9 pages');
    else {
      $_POST['page'] = 1;
      $_SESSION['guide']['title'] = htmlspecialchars($_POST['title']);
      $_SESSION['guide']['description'] = auText::EOL2br(htmlspecialchars($_POST['description']));
      $_SESSION['guide']['pages'] = $_POST['pages'];
      $page->Info('guide information saved');
    }
  } elseif($_POST['formid'] == 'newpage') {
    if($_POST['submit'] == 'back') {
      $_SESSION['guide'][$_POST['page']]['heading'] = htmlspecialchars($_POST['heading']);
      $_SESSION['guide'][$_POST['page']]['content'] = auText::BB2HTML($_POST['content']);
      $_POST['page']--;
    } elseif(!is_numeric($_POST['page']) || $_POST['page'] < 1 || $_POST['page'] > $_SESSION['guide']['pages'])
      $page->Error('invalid page number');
    elseif(!strlen($_POST['heading'] = trim($_POST['heading'])))
      $page->Error('you must specify a title for each page of your guide');
    elseif(!strlen(trim($_POST['content'])))
      $page->Error('you must specify the contents for each page of your guide');
    else {
      $_SESSION['guide'][$_POST['page']]['heading'] = htmlspecialchars($_POST['heading']);
      $_SESSION['guide'][$_POST['page']]['content'] = auText::BB2HTML($_POST['content']);
      $page->Info('page ' . $_POST['page'] . ' saved');
      $_POST['page']++;
    }
  } elseif($_POST['formid'] == 'save') {
    if($_POST['submit'] == 'save') {
      $page->Start('contribute a guide - geek', 'contribute a guide');
      for($id = 1; $db->Get('select 1 from guides where id=\'new_' . $id . '\'', 'error looking for next available guide id', ''); $id++);
      $id = 'new_' . $id;
      $guide = 'insert into guides (id, title, description, dateadded, pages, author) values (\'' . $id . '\', \'' . addslashes($_SESSION['guide']['title']) . '\', \'' . addslashes($_SESSION['guide']['description']) . '\', ' . time() . ', ' . $_SESSION['guide']['pages'] . ', ' . $user->ID . ')';
      if(false !== $db->Put($guide, 'error saving guide information')) {
        for($p = 1; $p <= $_SESSION['guide']['pages']; $p++) {
          $ins = 'insert into guidepages (guideid, pagenum, heading, content) values (\'' . $id . '\', ' . $p . ', \'' . addslashes($_SESSION['guide'][$p]['heading']) . '\', \'' . addslashes($_SESSION['guide'][$p]['content']) . '\')';
          $db->Put($ins, 'error saving page ' . $p);
        }
        if($user->ID != 1)
          @mail('misterhaan@' . _HOST, 'guide submission', $user->Name . ' has submitted a guide titled ' . $_SESSION['guide']['title'] . "\n\n" . 'http://' . $_SERVER['HTTP_HOST'] . '/tools/guidereview.php', 'From: track7 guides <guide@' . _HOST . '>');
        unset($_SESSION['guide']);
?>
      <p>
        thanks, your guide has been submitted!&nbsp; it will probably get looked
        at soon, and will be added to track7 if approved.
      </p>
      
      <ul><li><a href="<?=$_SERVER['REQUEST_URI']; ?>">submit another guide</a></li></ul>

<?
        $page->End();
        die;
      }
    } else
      $_POST['page'] = $_SESSION['guide']['pages'];
  }
  if(is_numeric($_POST['page']) && $_POST['page'] > 0) {
    if($_POST['page'] > $_SESSION['guide']['pages']) {
      $page->start('contribute a guide - geek', 'contribute a guide');
?>
      <p>
        your guide has been entered and a preview is below.&nbsp; please look
        over the preview to make sure everything looks okay, then either go back
        and change something or submit the guide for review and eventual posting
        to track7's guide section.
      </p>
      <ul><li><a href="#frmsave">skip preview</a></li></ul>

<?
      showpreview();
      $sf = new auForm('save');
      $sf->AddButtons(array('save', 'back'), array('submit this guide for approval', 'go back and make some changes'));
      $sf->WriteHTML(true);
      $page->End();
      die;
    } else {
      $page->Start('page editor - contribute a guide - geek', 'page editor', 'contribute a guide');
      pageform();
      showpreview();
      $page->End();
      die;
    }
  }
  $page->Start('contribute a guide - geek', 'contribute a guide');
  guideform();
  $page->End();

  // -----------------------------------------------------[ showpreview ]-- //
  function showpreview() {
    global $page, $user;
?>
      <h2 class="guidepreview">preview</h2>
      <div class="guidepreview">
      <h1><?=$_SESSION['guide']['title']; ?></h1>
      <p>
        <?=$_SESSION['guide']['description']; ?>

      </p>
      <div class="guideinfo">
        <span class="guideauthor">author:&nbsp; <a href="/user/<?=$user->Name; ?>/"><?=$user->Name; ?></a></span>
        <span class="guidepages">pages:&nbsp; <?=$_SESSION['guide']['pages']; ?></span>
      </div>

<?
    for($p = 1; $p <= $_SESSION['guide']['pages'] && $_SESSION['guide'][$p]['content']; $p++) {
      $page->Heading($_SESSION['guide'][$p]['heading']);
?>
      <p>
        <?=$_SESSION['guide'][$p]['content']; ?>

      </p>

<?
    }
?>
      </div>

<?
  }

  // -------------------------------------------------------[ guideform ]-- //
  function guideform() {
?>
      <p>
        use the form below to enter general information about your guide.&nbsp;
        each page will be entered individually after the general information is
        entered.
      </p>
<?
    $gf = new auForm('newguide');
    $gf->AddField('title', 'title', 'enter the title of your guide', true, $_SESSION['guide']['title'], _AU_FORM_FIELD_NORMAL, 50, 100);
    $gf->AddField('description', 'description', 'enter a short description of your guide', false, auText::br2EOL($_SESSION['guide']['description']), _AU_FORM_FIELD_MULTILINE, 0, 200);
    $gf->AddField('pages', 'pages', 'enter the number of pages in your guide (must be between 1 and 9)', true, $_SESSION['guide']['pages'], _AU_FORM_FIELD_INTEGER, 1, 1);
    $gf->AddButtons('next', 'save guide information and start entering pages');
    $gf->WriteHTML();
  }

  // --------------------------------------------------------[ pageform ]-- //
  function pageform() {
?>
      <p>
        enter the contents of page <?=$_POST['page']; ?> of your guide in the form below.&nbsp;
        <a href="/oi/f3/t1/">t7code</a> can be used for formatting.&nbsp; if you
        would rather submit your guide in html format (which will probably
        require some edits and make it take longer to get approved),
        <a href="/user/sendmessage.php?to=misterhaan">contact me</a> and i'll
        consider adding an option for that.
      </p>
<?
    $pf = new auForm('newpage');
    $pf->AddData('page', $_POST['page']);
    $pf->AddField('heading', 'page ' . $_POST['page'] . ' title', 'enter the title for this page', true, $_SESSION['guide'][$_POST['page']]['heading'], _AU_FORM_FIELD_NORMAL, 50, 100);
    $pf->AddField('content', 'page ' . $_POST['page'] . ' contents', 'enter the content of this page (use t7code)', true, auText::HTML2BB($_SESSION['guide'][$_POST['page']]['content']), _AU_FORM_FIELD_BBCODE);
    $pf->AddButtons(array('next', 'back'), array('save this page and continue', 'go back to the previous page'));
    $pf->WriteHTML(true);
  }
?>
