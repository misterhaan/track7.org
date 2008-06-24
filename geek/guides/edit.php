<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->ResetFlag(_FLAG_PAGES_COMMENTS);

  // tell guests to log in or register before contributing a guide
  if(!$user->Valid) {
    $page->Start('contribute a guide - geek', 'contribute a guide');
    $page->Heading('login required');
?>
      <p>
        guides may only be contributed by registered users.&nbsp; if you already
        have an account, you will need to <a href="/user/login.php">log in</a>
        — if you do not have an account you will need to <a href="/user/register.php">register</a>.
      </p>
      
<?
    $page->End();
    die;
  }

  if(isset($_GET['id'])) {
    $guide = 'select id, status, title, description, pages from guides where id=\'' . addslashes($_GET['id']) . '\'' . ($user->GodMode ? '' : ' and author=\'' . $user->ID . '\'');
    if($guide = $db->GetRecord($guide, 'error looking up guide information', 'guide not found or you are not the author')) {
      if($_GET['page'] == 'end') {
        $submitform = GetSubmitForm($guide->id);
        if($submitform->CheckInput(true)) {
          // important:  this should only be done for new guides!
          $update = 'update guides set status=\'pending\' where id=\'' . $guide->id . '\'';
          if(false !== $db->Change($update, 'error submitting guide for approval')) {
            header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SEVRER['PHP_SELF']) . '/' . $guide->id);
            die;
          }
        }
        ShowForm($page, $submitform, false, 'if you are satisfied with the current state of your guide, you may submit it for approval.');
      } elseif(is_numeric($_GET['page'])) {
        $pg = 'select pagenum, entrytype, heading, content from guidepages where guideid=\'' . $guide->id . '\' and pagenum=\'' . +$_GET['page'] . '\' order by version';
        if($pg = $db->Get($pg, 'error looking up page')) {
          $pg = $pg->NextRecord();
          $pageform = GetPageForm($guide, $pg);
          if($pageform->CheckInput(true)) {
            $content = $_POST['format'] == 't7code' ? auText::BB2HTML($_POST['content']) :  $_POST['content'];
            $ins = 'replace into guidepages (guideid, pagenum, version, entrytype, heading, content) values (\'' . $guide->id . '\', \'' . +$_GET['page'] . '\', -1, \'' . addslashes($_POST['format']) . '\', \'' . addslashes(htmlspecialchars($_POST['heading'])) . '\', \'' . addslashes($content) . '\')';
            if(false !== $db->Put($ins, 'error saving page')) {
              if($guide->pages >= +$_GET['page'])
                if($guide->status == 'new')
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SEVRER['PHP_SELF']) . '/contribute?id=' . $guide->id . '&page=end');
                else
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SEVRER['PHP_SELF']) . '/' . $guide->id);
              else
                header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SEVRER['PHP_SELF']) . '/contribute?id=' . $guides>id . '&page=' . (1 + $_GET['page']));
              die;
            }
          }
          ShowForm($page, $pageform, $guide->status != null, 'enter the contents of page ' . +$_GET['page'] . ' of your guide.&nbsp; <a href="/hb/thread1/">t7code</a> can be used for formatting.&nbsp; there’s also the option of submitting your pages in html format, but that means it will take me longer to review, edit, and approve your guide.');
        }
      }
    }
  }
  $guideform = GetGuideForm($guide);
  if($guideform->CheckInput(true)) {
    if($guide) {
      // DO:  save changes to existing guide
    } else {
      $id = date('Ymd') . 'uid' . $user->ID;
      $ins = 'insert into guides (id, tags, title, description, dateupdated, pages, author) values (\'' . $id . '\', \'\', \'' . addslashes(htmlspecialchars($_POST['title'])) . '\', \'' . addslashes(htmlspecialchars($_POST['description'])) . '\', \'' . time() . '\', \'' . +$_POST['pages'] . '\', \'' . $user->ID . '\')';
      if(false !== $db->Put($ins, 'error saving guide information')) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SEVRER['PHP_SELF']) . '/contribute?id=' . $id . '&page=1');
        die;
      }
    }
  }
  ShowForm($page, $guideform, $guide, 'enter general information about your guide here.&nbsp; each page will be entered individually after the general information is entered.');

  /**
   * Displays a form with links to jump to other forms for the guide.
   *
   * @param auPageTrack7 $page Current page
   * @param auForm $form Form to display
   * @param bool $edit Whether an existing guide is being edited
   * @param string $info Text to display in an information box above the form
   */
  function ShowForm(&$page, &$form, $edit = false, $info = false) {
    if($guide)
      $page->Start('edit guide');
    else
      $page->Start('contribute a guide');
    if($info)
      $page->Info($info);
    $form->WriteHTML(true);
    // DO:  add links to jump to pages if editing; show preview
    $page->End();
    die;
  }

  /**
   * Gets the edit guide form.
   *
   * @param object $guide Guide to edit (optional -- will create new if null)
   * @return auForm Guide edit form
   */
  function GetGuideForm($guide = false) {
    $f = new auForm($guide ? 'editguide' : 'newguide', $guide ? '?id=' . $guide->id : null);
    $f->Add(new auFormString('title', 'title', 'enter the title of your guide', true, html_entity_decode($guide->title), 50, 100));
    $f->Add(new auFormMultiString('description', 'description', 'enter a short description of your guide', true, html_entity_decode($guide->description), false, 0, 200));
    $f->Add(new auFormInteger('pages', 'pages', 'enter the number of pages in your guide (must be between 1 and 9)', true, $guide->pages, 1, 1));
    $f->Add(new auFormButtons('save', 'save guide information'));
    return $f;
  }
  
  /**
   * Gets the edit page form.
   *
   * @param object $guide Guide the page to edit belongs to
   * @param object $pg Page to edit (optional -- will create new if null)
   * @return auForm Page edit form
   */
  function GetPageForm($guide, $pg = false) {
    $f = new auForm($pg ? 'editpage' : 'newpage', '?id=' . $guide->id . '&page=' . +$_GET['page']);
    $f->Add(new auFormString('heading', 'page ' . +$_GET['page'] . ' title', 'enter the title for this page', true, html_entity_decode($pg->heading), 50, 100));
    $f->Add(new auFormMultiString('content', 'page ' . +$_GET['page'] . ' contents', 'enter the content of this page', true, auText::HTML2BB($pg->content), true));
    $f->Add(new auFormSelect('format', 'format', 'choose which format the content is entered in', true, array('t7code' => null)));
    $f->Add(new auFormButtons('save', 'save this page and move on'));
    return $f;
  }

  /**
   * Gets the submit guide form.
   *
   * @param string $id ID of the guide to save
   * @return auForm Submit guide form
   */
  function GetSubmitForm($id) {
    $f = new auForm('saveguide', '?id=' . $id . '&page=end');
    $f->Add(new auFormButtons('submit for approval', 'submit this guide for approval'));
    return $f;
  }
?>
