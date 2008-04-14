<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  if(!$user->GodMode) {
    header('HTTP/1.1 401 Unauthorized');
    include('../401.php');
    die;
  }
  $page->Start('related links manager');
  if(isset($_POST['formid']))
    switch($_POST['formid']) {
      case 'editlink':
        if(!is_numeric($_POST['id']))
          $page->Error('cannot update link with non-numeric id!');
        elseif(!is_numeric($_POST['catid'])) {
          $page->Error('cannot update link to non-numeric category id!');
        } else {
          $catchk = 'select 1 from linkcats where id=' . $_POST['catid'];
          if($db->Get($catchk, 'error verifying that category exists', 'could not find category for that id', true)) {
            $update = 'update links set url=\'' . addslashes($_POST['url']) . '\', title=\'' . addslashes($_POST['title']) . '\', tooltip=\'' . addslashes($_POST['tooltip']) . '\', description=\'' . addslashes(auText::BB2HTML($_POST['description'])) . '\', catid=' . $_POST['catid'] . ' where id=' . $_POST['id'];
            if($db->Change($update, 'error updating link'))
              $page->Info('link updated successfully');
            }
        }
        break;
      case 'addlink':
        if(!is_numeric($_POST['catid']))
          $page->Error('cannot add link to non-numeric category id');
        else {
          $ins = 'insert into links (catid, url, title, tooltip, description) values (' . $_POST['catid'] . ', \'' . addslashes($_POST['url']) . '\', \'' . addslashes($_POST['title']) . '\', \'' . addslashes($_POST['tooltip']) . '\', \'' . addslashes(auText::BB2HTML($_POST['description'])) . '\')';
          if(false !== ($db->Put($ins, 'error adding new link')))
            $page->Info('new link added successfully');
        }
        break;
      case 'dellink':
        $del = 'delete from links where id=' . addslashes($_POST['id']);
        if($db->Change($del, 'error deleting link'))
          $page->Info('link deleted successfully');
        break;
      case 'editcat':
        if(strlen($_POST['page']) < 1)
          $_POST['page'] = '/neighbors.php';
        if(is_numeric($_POST['page'])) {
          if(!$engine->Get('select 1 from pages where id=' . $_POST['page'], 'error looking up page by id', 'could not find page with that id'))
            $_POST['page'] = 'null';
        } else {
          $_POST['page'] = 'select id from pages where urlout=\'' . $_POST['page'] . '\'';
          if($_POST['page'] = $db->GetValue($_POST['page'], 'error looking up page by url')) {
            if($_POST['page'] < 1)
              $_POST['page'] = 'null';
          } else
            $_POST['page'] = 'null';
        }
        $update = 'update linkcats set pageid=' . $_POST['page'] . ', title=\'' . addslashes($_POST['title']) . '\' where id=' . $_POST['id'];
        if($db->Change($update, 'error updating category'))
          $page->Info('category updated successfully');
        break;
      case 'addcat':
        if(strlen($_POST['page']) < 1)
          $_POST['page'] = '/neighbors.php';
        if(is_numeric($_POST['page'])) {
          if(!$db->Get('select 1 from pages where id=' . $_POST['page'], 'error looking up page by id', 'could not find page with that id'))
            $_POST['page'] = 'null';
        } else {
          $_POST['page'] = 'select id from pages where urlout=\'' . $_POST['page'] . '\'';
          if($_POST['page'] = $db->GetValue($_POST['page'], 'error looking up page by url')) {
            if($_POST['page'] < 1)
              $_POST['page'] = 'null'; 
          } else
            $_POST['page'] = 'null';
        }
        $ins = 'insert into linkcats (pageid, title) values (' . $_POST['page'] . ', \'' . addslashes($_POST['title']) . '\')';
        if(false !== ($db->Put($ins, 'error saving new category')))
          $page->Info('category added successfully');
        break;
      case 'delcat':
        $del = 'delete from linkcats where id=' . addslashes($_POST['id']);
        if($db->Change($del, 'error deleting category'))
          $page->Info('category deleted successfully');
        break;
      default:
        $page->Error('unknown form submitted:&nbsp; ' . htmlspecialchars($_POST['formid']));
    }
  $cats = 'select c.id, c.title, c.pageid, p.urlout from linkcats as c, pages as p where c.pageid is null or c.pageid=p.id group by c.id';
  if($cats = $db->Get($cats, 'error reading link categories')) {
    if($cats->NumRecords() < 1)
      $_GET['addcat'] = true;  // if there are no categories, show the form to add one
    else {
      $page->Heading('link categories' . (isset($_GET['addcat']) ? '' : '&nbsp; <a class="img" href="?addcat#frmaddcat" title="add new category"><img src="/style/new.png" alt="add" /></a>'));
    }
    while($cat = $cats->NextRecord()) {
      $links = 'select id, url, title, tooltip, description from links where catid=' . $cat->id;
      if($links = $db->Get($links, 'error reading links for this category')) {
        if(($numlinks = $links->NumRecords()) < 1)
          $_GET['addlink'] == $cat->id;
      }
      if($_GET['delcat'] == $cat->id) {
        $delform = new auForm('delcat');
        $delform->AddData('id', $cat->id);
        $delformset = new auFormFieldSet('confirm category deletion');
        $delformset->AddText('confirm', 'really delete category ' . $cat->title . '?');
        $delformset->AddButtons('yes', 'really delete this category');
        $delform->AddFieldSet($delformset);
        $delform->WriteHTML(true);
      }
      elseif($_GET['editcat'] == $cat->id)
        catform($cat);
      elseif($links) {
?>
      <h3>
        <?=$cat->title . ($cat->pageid > 0 ? ' - ' . $cat->urlout : ''); ?> &nbsp;
<?=($_GET['addlink'] == $cat->id ? '' : '        <a class="img" href="?addlink=' . $cat->id . '#frmaddlink" title="add a link under this category"><img src="/style/new.png" alt="add" /></a>' . "\n"); ?>
<?=($_GET['editcat'] == $cat->id ? '' : '        <a class="img" href="?editcat=' . $cat->id . '#frmeditcat" title="edit this category"><img src="/style/edit.png" alt="edit" /></a>' . "\n"); ?>
<?=(($numlinks || $_GET['delcat'] == $cat->id) ? '' : '        <a class="img" href="?delcat=' . $cat->id . '#frmdelcat" title="delete this category"><img src="/style/del.png" alt="del" /></a>' . "\n"); ?>
      </h3>
      <dl class="relatedlinks">
<?
        while($link = $links->NextRecord()) {
          if($link->id == $_GET['dellink']) {
            $delform = new auForm('dellink');
            $delform->AddData('id', $link->id);
            $delformset = new auFormFieldSet('confirm link deletion');
            $delformset->AddText('confirm', 'really delete link to ' . $link->title . '?');
            $delformset->AddButtons('yes', 'really delete this link');
            $delform->AddFieldSet($delformset);
            $delform->WriteHTML(true);
          } elseif($link->id == $_GET['editlink'])
            linkform($link, $cat->id);
          else {
?>
        <dt><a href="<?=$link->url; ?>" title="<?=$link->tooltip; ?>"><?=$link->title; ?></a></dt>
        <dd>
          <?=$link->description; ?>
          
          <a class="img" href="?editlink=<?=$link->id; ?>#frmeditlink" title="edit this link"><img src="/style/edit.png" alt="edit" /></a>
          <a class="img" href="?dellink=<?=$link->id; ?>#frmdellink" title="remove this link"><img src="/style/del.png" alt="del" /></a>
        </dd>
<?
          }
        }
?>
      </dl>
<?
      }
      if($_GET['addlink'] == $cat->id)
        linkform(null, $cat->id);
    }
    if(isset($_GET['addcat']))
      catform(null);
  }
  $page->End();

  function catform($cat) {
    $action = $cat === null ? 'add' : 'edit';
    $catform = new auForm($action . 'cat');
    if($cat !== null)
      $catform->AddData('id', $cat->id);
    $catformset = new auFormFieldSet($action . ($cat === null ? ' new' : '' ) . ' category');
    $catformset->AddField('title', 'title', 'enter the title for this category', true, $cat->title, _AU_FORM_FIELD_NORMAL, 30, 64);
    $catformset->AddField('page', 'page', 'url or id -- leave blank to show on neighborhood page)', false, $cat->pageid > 1 ? $cat->urlout : '', _AU_FORM_FIELD_NORMAL, 30, 64);
    if($action == 'edit')
      $action = 'update';
    $catformset->AddButtons($action, $action . ' this category');
    $catform->AddFieldSet($catformset);
    $catform->WriteHTML(true);
  }

  function linkform($link, $catid = null) {
    $action = $link === null ? 'add' : 'edit';
    $linkform = new auForm($action . 'link');
    if($link !== null)
      $linkform->data('id', $link->id);
    $linkformset = new auFormFieldSet($action . ($link === null ? ' new' : '') . ' link');
    $linkformset->AddField('title', 'title', 'enter the title of this link', true, $link->title, _AU_FORM_FIELD_NORMAL, 30, 64);
    $linkformset->AddField('url', 'url', 'enter the url for this link', true, $link->url, _AU_FORM_FIELD_NORMAL, 50, 255);
    $linkformset->AddField('tooltip', 'tooltip', 'enter the tooltip for this link', false, $link->tooltip, _AU_FORM_FIELD_NORMAL, 50, 255);
    $linkformset->AddField('description', 'description', 'enter the description for this link', true, strlen($link->description) ? auText::HTML2BB($link->description) : '', _AU_FORM_FIELD_BBCODE);
    $linkformset->AddField('catid', 'category', 'enter the id of the category this link belongs to', true, $catid, _AU_FORM_FIELD_NORMAL, 2, 3);
    if($action == 'edit')
      $action = 'update';
    $linkformset->AddButtons($action, $action . ' this link');
    $linkform->AddFieldSet($linkformset);
    $linkform->WriteHTML(true);
  }
?>
