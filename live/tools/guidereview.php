<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';
  require_once 'auText.php';
  
  $page->Start('guide review');
  if(isset($_GET['id'])) {
    if($_POST['submit'] == 'approve') {
      $update = 'update guidepages set guideid=\'' . $_POST['id'] . '\' where guideid=\'' . $_GET['id'] . '\'';
      if($db->Change($update, 'error changing guide id in page table')) {
        $update = 'update guides set id=\'' . $_POST['id'] . '\', dateadded=' . time() . ', skill=\'' . $_POST['skill'] . '\', status=\'approved\' where id=\'' . $_GET['id'] . '\'';
        if($db->Change($update, 'error approving guide')) {
          $email = 'select c.email from guides as g left join usercontact as c on g.author=c.uid where g.id=\'' . $_POST['id'] . '\'';
          if($email = $db->GetValue($email, 'error looking up author\'s e-mail address', 'author\'s e-mail address not found'))
            @mail($email, 'your guide has been approved!', 'congratulations, your guide has been approved and is now available to track7 visitors!  if you\'d like to look at it now, use this url:' . "\n\n" . 'http://' . $_SERVER['HTTP_HOST'] . '/geek/guides/' . $_POST['id'] . '/', 'From: track7 guides <guide@' . _HOST . '>');
          $page->Info('guide approved');
          listguides();
          $page->End();
          die;
        }
      }
    } elseif($_POST['submit'] == 'reject') {
      $update = 'update guides set status=\'rejected\' where id=\'' . $_GET['id'] . '\'';
      if($db->Change($update, 'error rejecting guide')) {
        $email = 'select c.email from guides as g left join usercontact as c on g.author=c.uid where g.id=\'' . $_POST['id'] . '\'';
        if($email = $db->GetValue($email, 'error looking up author\'s e-mail address', 'author\'s e-mail address not found'))
          @mail($email, 'your guide has been denied!', 'sorry, your guide has NOT been approved to be added to track7 at this time, for reasons listed below.  please try again either with a different guide or by improving this one.' . "\n\n" . $_POST['reason'], 'From: track7 guides <guide@' . _HOST . '>');
        $page->Info('guide rejected');
        listguides();
        $page->End();
        die;
      }
    } elseif(is_numeric($_GET['edit'])) {
      if($_POST['submit'] == 'save') {
        // DO: save changes to page / guide
      }
      // DO: show edit form for a page, or entire guide (page 0)
    }
    $guide = 'select g.title, g.description, u.login from guides as g left join users as u on g.author=u.uid where g.id=\'' . $_GET['id'] . '\' and g.status=\'pending\'';
    if($guide = $db->GetRecord($guide, 'error getting guide information', 'guide not found or no longer pending')) {
?>
      <h1><?=$guide->title; ?></h1>
      <hr class="major" />
      <p>author:&nbsp; <a href="/user/<?=$guide->login; ?>/"><?=$guide->login; ?></a></p>
      <p>
        <?=$guide->description; ?>

      </p>

<?
      // DO: show guide info edit link
      $pages = 'select heading, content from guidepages where guideid=\'' . $_GET['id'] . '\' order by pagenum';
      if($pages = $db->Get($pages, 'error getting pages for this guide', 'no pages found')) {
        while($p = $pages->NextRecord()) {
          $page->Heading($p->heading);
?>
      <p>
        <?=$p->content; ?>

      </p>

<?
          // DO: show page edit link
        }
      }
      $page->Heading('approval');
      $gaf = new auForm('?id=' . $_GET['id']);
      $gaf->AddField('id', 'id', 'enter an id for this guide, which will be part of its url', true, '', _AU_FORM_FIELD_NORMAL, 10, 32);
      $gaf->AddSelect('skill', 'skill', 'choose the skill level for this guide', auFormSelect::ArrayIndex(array('beginner', 'intermediate', 'advanced')));
      $gaf->AddButtons('approve', 'approve this guide');
      $gaf->WriteHTML(true);

      $page->Heading('rejection');
      $gdf = new auForm('?id=' . $_GET['id']);
      $gdf->AddField('reason', 'reason', 'describe why this guide is being rejected', true, '', _AU_FORM_FIELD_MULTILINE);
      $gdf->AddButtons('reject', 'reject this guide');
      $gdf->WriteHTML(true);

      $page->End();
      die;
    }
  }
  
  listguides();
  $page->End();

  function listguides() {
    global $db;
    $guides = 'select g.title, g.id, g.dateadded, g.pages, u.login from guides as g left join users as u on g.author=u.uid where g.status=\'pending\' order by dateadded desc';
    if($guides = $db->Get($guides, 'error looking up guides to review', 'no guides to review')) {
?>
      <table class="text" cellspacing="0">
        <thead><tr><th>date</th><th>title</th><th>pages</th><th>author</th></tr></thead>
        <tbody>
<?
      while($guide = $guides->NextRecord()) {
?>
          <tr><td><?=auText::SmartTime($guide->dateadded); ?></td><td><a href="?id=<?=$guide->id; ?>"><?=$guide->title; ?></a></td><td><?=$guide->pages; ?></td><td><a href="/user/<?=$guide->login; ?>/"><?=$guide->login; ?></a></td></tr>
<?
      }
?>
        </tbody>
      </table>
<?
    }
  }
?>
