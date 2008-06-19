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

  if(is_numeric($_GET['page'])) {
    // DO:  show page edit form
  }
  // DO:  show guide edit form
  $guideform = new auForm('newguide', null, 'post', true);
  $guideform->Add(new auFormString('title', 'title', 'enter the title of your guide', true, '', 50, 100));
  $guideform->Add(new auFormMultiString('description', 'description', 'enter a short description of your guide', false, '', false, 0, 200));
  $guideform->Add(new auFormInteger('pages', 'pages', 'enter the number of pages in your guide (must be between 1 and 9)', true, '', 1, 1));
  $guideform->Add(new auFormButtons('save', 'save guide information'));

  if($guideform->CheckInput(true)) {
    // DO:  save guide information and jump to editing page 1
  }
  $page->Start('contribute a guide');
  $page->Info('enter general information about your guide here.&nbsp; each page will be entered individually after the general information is entered.');
  $guideform->WriteHTML(true);
  $page->End();
?>