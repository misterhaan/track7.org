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
    $guide = 'select id, skill, status, title, description, pages, tags, author from guides where id=\'' . addslashes($_GET['id']) . '\'' . ($user->GodMode ? '' : ' and author=\'' . $user->ID . '\'');
    if($guide = $db->GetRecord($guide, 'error looking up guide information', 'guide not found or you are not the author')) {
      if($_GET['page'] == 'end') {
        $submitform = GetSubmitForm($guide->id);
        if($submitform->CheckInput(true)) {
          // important:  this should only be done for new guides!
          $update = 'update guides set status=\'pending\' where id=\'' . $guide->id . '\'';
          if(false !== $db->Change($update, 'error submitting guide for approval')) {
            if(!$user->GodMode)
              mail('misterhaan@' . _DOMAIN, 'new guide:  ' . $guide->title, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id . '/');
            header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id . '/');
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
            $content = $_POST['format'] == 'bbcode' ? auText::BB2HTML($_POST['content'], false, false) :  $_POST['content'];
            $ins = 'replace into guidepages (guideid, pagenum, version, entrytype, heading, content) values (\'' . $guide->id . '\', \'' . +$_GET['page'] . '\', -1, \'' . addslashes($_POST['format']) . '\', \'' . addslashes(htmlspecialchars($_POST['heading'])) . '\', \'' . addslashes($content) . '\')';
            if(false !== $db->Put($ins, 'error saving page')) {
              if(+$_GET['page'] >= $guide->pages)
                if($guide->status == 'new')
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/contribute?id=' . $guide->id . '&page=end');
                else {
                  if(!$user->GodMode)
                    mail('misterhaan@' . _DOMAIN, 'guide page updated:  ' . $guide->title . ' :: ' . $_POST['heading'], 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id . '/diff' . +$_GET['page']);
                  header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id . '/');
                }
              else {
                if($guide->status != 'new' && !$user->GodMode)
                  mail('misterhaan@' . _DOMAIN, 'guide page updated:  ' . $guide->title . ' :: ' . $_POST['heading'], 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id . '/diff' . +$_GET['page']);
                header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/contribute?id=' . $guide->id . '&page=' . (1 + $_GET['page']));
              }
              die;
            }
          }
          ShowForm($page, $pageform, $guide->status != null, 'enter the contents of page ' . +$_GET['page'] . ' of your guide.&nbsp; <a href="/hb/thread1/">t7code</a> can be used for formatting.&nbsp; there’s also the option of submitting your pages in html format, but that means it will take me longer to review, edit, and approve your guide.');
        }
      }
    }
  }
  $guideform = GetGuideForm($user, $guide);
  if($guideform->CheckInput(true)) {
    if($guide) {
      if($user->GodMode) {
        $newid = auFile::NiceName($_POST['id']);
        if($guide->id != $newid) {
          // make sure newid isn't used yet
          $count = $db->GetValue('select count(1) from guides where id=\'' . $newid . '\'', 'error checking if guide id is available');
          if($count === false)
            $error = true;
          elseif($count > 0) {
            $page->Error('guide id “' . $newid . '” already in use!&nbsp; please choose another.');
            $error = true;
          } else {
            // update pages to new guide id
            $error = false === $db->Change('update guidepages set guideid=\'' . $newid . '\' where guideid=\'' . $guide->id . '\'', 'error moving pages to new guide id');
          }
        }
        if(!$error) {
          // update tags
          if($_POST['status'] != 'rejected' && ($guide->tags || $_POST['tags'])) {
            $_POST['tags'] = str_replace(', ', ',', $_POST['tags']);
            $newtags = explode(',', $_POST['tags']);
            $oldtags = explode(',', $guide->tags);
            for($i = count($oldtags) - 1; $i >= 0; $i--)
              if(in_array($oldtags[$i], $newtags))
                unset($oldtags[$i], $newtags[array_search($oldtags[$i], $newtags)]);
            if(count($oldtags))  // tags were removed
              $db->Change('update taginfo set count=count-1 where type=\'guides\' and (name=\'' . implode('\' or name=\'', $oldtags) . '\')');
            if(count($newtags))  // tags were added
              $db->Put('insert into taginfo (type, name, count) values (\'guides\', \'' . implode('\', 1), (\'guides\', \'', $newtags) . '\', 1) on duplicate key update count=count+1');
          }

          // if status going from pending to approved, change page versions from -1 to 0
          if($guide->status == 'pending' && $_POST['status'] == 'approved')
            $db->Change('update guidepages set version=0 where version=-1 and guideid=\'' . $newid . '\'', 'error approving pages');

          // update guide info (including last updated / added date)
          $update = 'update guides set id=\'' . $newid . '\', status=\'' . addslashes($_POST['status']) . '\', dateupdated=\'' . ($guide->status == 'pending' && $_POST['status'] == 'approved' ? time() . '\', dateadded=\'' . time() : time()) . '\', tags=\'' . addslashes(str_replace(', ', ',', $_POST['tags'])) . '\', title=\'' . addslashes(htmlspecialchars($_POST['title'])) . '\', description=\'' . addslashes(auText::EOL2pbr($_POST['description'])) . '\', skill=\'' . addslashes($_POST['skill']) . '\', pages=\'' . +$_POST['pages'] . '\' where id=\'' . $guide->id . '\'';
          if(false !== $db->Change($update, 'error updating guide information')) {
            // send message to author if guide was just approved
            if($guide->status == 'pending' && $_POST['status'] == 'approved' && $guide->author != 1) {
              $_POST['formid'] = 'sendmessage';
              $_POST['to'] = $guide->author;
              $_POST['subject'] = 'guide approved!';
              $_POST['message'] = 'your guide “' . $guide->title . '” has been approved and is now visible to all track7 visitors.  thank you for your contribution!' . "\n\n" . '[url=/geek/guides/' . $newid . '/]your guide[/url]' . "\n\n" . 'this message is automated, but feel free to reply.';
              $_POST['submit'] = 'send';
              @include _ROOT . '/user/sendmessage.php';
            }
            header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $newid . '/');
            die;
          }
        }
      } else {
        $pages = +$_POST['pages'];
        if($pages < $guide->pages) {
          // DO:  remove ending pages (maybe?)
        }
        $update = 'update guides set status=\'pending\', dateupdated=\'' . time() . '\', title=\'' . addslashes(htmlspecialchars($_POST['title'])) . '\', description=\'' . addslashes(auText::EOL2pbr($_POST['description'])) . '\', pages=\'' . $pages . '\' where id=\'' . $guide->id . '\'';
        if(false !== $db->Change($update, 'error updating guide information')) {
          // DO:  send e-mail that a guide was edited
          header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $guide->id);
        }
      }
    } else {
      if($user->GodMode) {
        $id = auFile::NiceName($_POST['id']);
        $_POST['tags'] = str_replace(', ', ',', $_POST['tags']);
        $ins = 'insert into guides (id, tags, title, description, skill, status, pages, author) values (\'' . $id . '\', \'' . addslashes(htmlspecialchars($_POST['tags'])) . '\', \'' . addslashes(htmlspecialchars($_POST['title'])) . '\', \'' . addslashes(auText::EOL2pbr($_POST['description'])) . '\', \'' . addslashes($_POST['skill']) . '\', \'' . addslashes($_POST['status']) . '\', \'' . +$_POST['pages'] . '\', \'' . $user->ID . '\')';
        if(false !== $db->Put($ins, 'error saving guide information')) {
          $db->Put('insert into taginfo (type, name, count) values (\'guides\', \'' . implode('\', 1), (\'guides\', \'', $_POST['tags']) . '\', 1) on duplicate key update count=count+1');
          header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/contribute?id=' . $id . '&page=1');
          die;
        }
      } else {
        $id = date('YmdHis') . 'uid' . $user->ID;
        $ins = 'insert into guides (id, tags, title, description, pages, author) values (\'' . $id . '\', \'\', \'' . addslashes(htmlspecialchars($_POST['title'])) . '\', \'' . addslashes(auText::EOL2pbr($_POST['description'])) . '\', \'' . +$_POST['pages'] . '\', \'' . $user->ID . '\')';
        if(false !== $db->Put($ins, 'error saving guide information')) {
          header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/contribute?id=' . $id . '&page=1');
          die;
        }
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
    if($edit)
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
   * @param auUserTrack7 $user User object for the currently logged-in user
   * @param object $guide Guide to edit (optional -- will create new if null)
   * @return auForm Guide edit form
   */
  function GetGuideForm(&$user, $guide = false) {
    $f = new auForm($guide ? 'editguide' : 'newguide', $guide ? '?id=' . $guide->id : null);
    $f->Add(new auFormString('title', 'title', 'enter the title of your guide', true, html_entity_decode($guide->title), 50, 100));
    if($user->GodMode)
      $f->Add(new auFormString('id', 'id', 'enter the id for this guide; used in the url', true, $guide->id, 20, 32));
    $f->Add(new auFormMultiString('description', 'description', 'enter a short description of your guide', true, auText::pbr2EOL($guide->description), false, 0, 200));
    if($user->GodMode)
      $f->Add(new auFormString('tags', 'tags', 'enter comma-separated tags for this guide', false, $guide->tags, 50));
    $f->Add(new auFormInteger('pages', 'pages', 'enter the number of pages in your guide (must be between 1 and 9)', true, $guide->pages, 1, 1));
    if($user->GodMode) {
      $f->Add(new auFormSelect('skill', 'skill', 'choose the skill level for this guide', true, auFormSelect::ArrayIndex(array('beginner', 'intermediate', 'advanced')), $guide->skill));
      $f->Add(new auFormSelect('status', 'status', 'choose the status of this guide', true, auFormSelect::ArrayIndex(array('new', 'pending', 'approved', 'rejected')), $guide->status));
    }
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
    $f->Add(new auFormSelect('format', 'format', 'choose which format the content is entered in', true, array('bbcode' => 't7code')));
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
