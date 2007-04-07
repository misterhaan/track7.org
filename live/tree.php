<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  if($user->GodMode)
    require_once 'auForm.php';
  if($user->GodMode && $_POST['submit'] == 'save') {
    if(is_numeric($_POST['view'])) {
      $flags = $_POST['view'];
      if(!isset($_POST['loghits']))
        $flags |= _FLAG_PAGES_NOLOGHITS;
      if(isset($_POST['comments']))
        $flags |= _FLAG_PAGES_COMMENTS;
      if(isset($_POST['section']))
        $flags |= _FLAG_PAGES_ISSECTION;

      if(!is_numeric($_POST['parent'])) {
        $_POST['parent'] = 'null';
      } else {
        $chkparent = 'select 1 from pages where id=' . $_POST['parent'];
        if(!$db->Get($chkparent, '', ''))
          $_POST['parent'] = 'null';
      }

      if(is_numeric($_POST['sort'])) {
        $chksort = 'select max(sort) as sort from pages where parent=' . $_POST['parent'];
        if($chksort = $db->Get($chksort, ''))
          if($chksort = $chksort->NextRecord()) {
            $chksort->sort++;
            if($chksort->sort < $_POST['sort'])
              $_POST['sort'] = $chksort->sort;
          } else
            $_POST['sort'] = 1;
      } else {
        $_POST['sort'] = 'select max(sort) from pages where parent=' . $_POST['parent'];
        if($_POST['sort'] = $db->GetValue($_POST['sort'], '', ''))
          $_POST['sort']++;
        else
          $_POST['sort'] = 1;
      }

      if(strlen($_POST['urlin']) <= 0)
        $_POST['urlin'] = $_POST['urlout'];

      // keywords meta tag is supposed to have keywords separated by commas and NOT a space
      $_POST['keywords'] = str_replace(', ', ',', $_POST['keywords']);

      if(is_numeric($_POST['id'])) {
        $oldpage = 'select parent, sort, flags from pages where id=' . $_POST['id'];
        if($oldpage = $db->GetRecord($oldpage, 'error looking up old page values', 'old page not found', true)) {
          if($oldpage->flags & _FLAG_PAGES_HASCHILDREN) {
            $flags |= _FLAG_PAGES_HASCHILDREN;
          }
          if($oldpage->parent == null)
            $oldpage->parent = 'null';
          if($oldpage->sort != $_POST['sort'] || $oldpage->parent != $_POST['parent']) {
            $shift = 'update pages set sort=sort-1 where parent=' . $oldpage->parent . ' and sort>' . $oldpage->sort;
            $db->Change($shift, 'error adjusting sort order of other pages to fill the gap left by this page');
            $shift = 'update pages set sort=sort+1 where parent=' . $_POST['parent'] . ' and sort>=' . $_POST['sort'];
            $db->Change($shift, 'error adjusting sort order of other pages to make room for this page');
          }
          $update = 'update pages set parent=' . $_POST['parent'] . ', sort=' . $_POST['sort'] . ', urlin=\'' . addslashes($_POST['urlin']) . '\', urlout=\'' . addslashes($_POST['urlout']) . '\', name=\'' . addslashes($_POST['name']) . '\', tooltip=\'' . addslashes($_POST['tooltip']) . '\', description=\'' . addslashes(htmlspecialchars($_POST['description'])) . '\', keywords=\'' . addslashes(htmlspecialchars($_POST['keywords'])) . '\', flags=' . $flags . ' where id=' . $_POST['id'];
          if(false !== $db->Change($update, 'error updating page')) {
            if($oldpage->parent != $_POST['parent']) {
              $db->Change('update pages set flags=flags|' . _FLAG_PAGES_HASCHILDREN . ' where id=' . $_POST['parent'], 'error setting haschildren flag on new parent');
              if(false === $db->Get('select 1 from pages where parent=' . $oldpage->parent, 'error seeing if old parent has any children left', ''))
                $db->Change('update pages set flags=flags^' . _FLAG_PAGES_HASCHILDREN . ' where id=' . $oldpage->parent, 'error clearing haschildren flag on old parent');
            }
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?page=' . $_POST['id']);
            die;
          }
        }
      } else {
        $shift = 'update pages set sort=sort+1 where parent=' . $_POST['parent'] . ' and sort>=' . $_POST['sort'];
        $db->Change($shift, 'error adjusting sort order of existing pages to make room for this page');
          $add = 'insert into pages (parent, sort, urlin, urlout, name, tooltip, description, keywords, flags) values (' . $_POST['parent'] . ', ' . $_POST['sort'] . ', \'' . addslashes($_POST['urlin']) . '\', \'' . addslashes($_POST['urlout']) . '\', \'' . addslashes($_POST['name']) . '\', \'' . addslashes($_POST['tooltip']) .'\', \'' . addslashes(htmlspecialchars($_POST['description'])) . '\', \'' . addslashes(htmlspecialchars($_POST['keywords'])) . '\', ' . $flags . ')';
          if(false !== $db->Put($add, 'error adding new page')) {
            $db->Change('update pages set flags=flags|' . _FLAG_PAGES_HASCHILDREN . ' where id=' . $_POST['parent'], 'error setting haschildren flag on parent');
            header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?page=' . $id);
            die;
          }
      }
    } else {
      $page->Error('illegal value for \'visible to\' field');
    }
  }
  $page->Start('track7 tree');

  if($user->GodMode && isset($_GET['page']) && is_numeric($_GET['page'])) {
    $p = 'select * from pages where id=' . $_GET['page'];
    if($p = $db->GetRecord($p, 'error reading page information', 'page not found', true)) {
      if(isset($_GET['do'])) {
        switch($_GET['do']) {
          case 'add':
?>
      <h2>add new page</h2>
<?
            pageform();
            break;
          case 'edit':
?>
      <h2>edit page</h2>
<?
            pageform($p);
            break;
          case 'delete':
?>
      <h2>delete page</h2>
<?
            $del = 'delete from pages where id=' . $_GET['page'] . ' and flags&' . _FLAG_PAGES_HASCHILDREN . '=0';
            $del = $db->Change($del, 'error deleting page');
            if($del)
              $page->Info('page deleted successfully');
            else
              $page->Error('<b>unable to delete page</b>', 'page either does not exist or has children');
            break;
        }
      } else {
        $page->Heading('page details');
        $p->parentid = $p->parent;
        if($p->parent == null)
          $p->parent = '(none)';
        else {
          $p->parent = 'select name, urlout from pages where id=' . $p->parent;
          if($p->parent = $db->GetRecord($p->parent, 'error reading parent page information', 'parent page not found', true)) {
            $p->parent = $p->parent->name . ' (' . $p->parent->urlout . ')';
          } else
            $p->parent = '(error)';
        }
        require_once 'auText.php';
?>
      <table class="columns" cellspacing="0">
        <tr class="firstchild"><th>id</th><td><?=$p->id; ?></td></tr>
        <tr><th>name</th><td><?=$p->name; ?></td></tr>
        <tr><th>tooltip</th><td><?=$p->tooltip; ?></td></tr>
        <tr><th>parent</th><td><?=$p->parent; ?><?=$p->parentid ? ' <a href="?page=' . $p->parentid . '"><img src="/style/details.png" alt="details" /></a>' : ''; ?></td></tr>
        <tr><th>sort</th><td><?=$p->sort; ?></td></tr>
        <tr><th>external url</th><td><?=$p->urlout; ?></td></tr>
        <tr><th>internal url</th><td><?=$p->urlin; ?></td></tr>
        <tr><th>visible to</th><td><?=xlatvisible($p->flags); ?></td></tr>
        <tr><th>log hits</th><td><?=auText::YesNo(($p->flags & _FLAG_PAGES_NOLOGHITS) == 0); ?></td></tr>
        <tr><th>allow&nbsp;comments</th><td><?=auText::YesNo($p->flags & _FLAG_PAGES_COMMENTS); ?></td></tr>
        <tr><th>section</th><td><?=auText::YesNo($p->flags & _FLAG_PAGES_ISSECTION); ?></td></tr>
        <tr><th>description</th><td><?=$p->description; ?></td></tr>
        <tr><th>keywords</th><td><?=$p->keywords; ?></td></tr>
      </table>

<?
        $actions = new auForm('', '', 'get');
        $actions->AddData('page', $_GET['page']);
        $buttons = array('add', 'edit');
        $btntip = array('add a new page under this page', 'edit this page');
        if(!($p->flags & _FLAG_PAGES_HASCHILDREN)) {
          $buttons[] = 'delete';
          $btntip[] = 'delete this page';
        }
        $actions->AddButtons($buttons, $btntip, 'do');
        $actions->WriteHTML();
      }
    }
    $page->Heading('site map');
  }

  $root = 'select id, urlout, name, tooltip, flags & ' . _FLAG_PAGES_HASCHILDREN . ' as isparent from pages where id=1';
  if(!$user->GodMode)
    if($user->Valid)
      $root .= ' and flags & ' . _FLAG_PAGES_HIDELOGIN . '=0';
    else
      $root .= ' and flags & ' . _FLAG_PAGES_HIDENOLOGIN . '=0';
  if($root = $db->Get($root, 'error reading home page information')) {
    if($root = $root->NextRecord()) {
?>
      <ul id="treelinks">
<?
      echo '        <li class="folder"><a href="' . $root->urlout . '">' . $root->name . '</a>';
      if($user->GodMode)
        echo ' <a class="img" href="' . $_SERVER['PHP_SELF'] . '?page=' . $root->id . '" title="view details for this page"><img src="/style/details.png" alt="details" /></a>';
      echo ' - <span class="detail">' . $root->tooltip . '</span>';
      if($root->isparent)
        writesubpages($root->id);
      echo "</li>\n";
?>
      </ul>
<?
    } else {
      $_GET['page'] = 'null';
      pageform();
    }
  }

  $page->End();

/*-----------------------------------------------------------[ writesubpages ]--
  Writes links to the pages below the current page.
  $pageid = ID of the page to write sub pages of.
  $indent = Current indentation level.
*/
  function writesubpages($pageid, $indent = '        ') {
    global $db, $user;

    $subs = 'select id, urlout, urlin, name, tooltip, flags & ' . _FLAG_PAGES_HASCHILDREN . ' as isparent from pages where parent=' . $pageid;
    if(!$user->GodMode)
      if($user->Valid)
        $subs .= ' and flags & ' . _FLAG_PAGES_HIDELOGIN . '=0';
      else
        $subs .= ' and flags & ' . _FLAG_PAGES_HIDENOLOGIN . '=0';
    $subs .= ' order by sort';
    $subs = $db->Get($subs);
    if($subs->IsError())
      echo "<ul>\n          <li><b>error looking up subpages</b>:&nbsp; " . $subs->GetMessage() . "</li>\n        </ul>";
    elseif($subs->NumRecords()) {
      echo "<ul>\n";
      while($sub = $subs->NextRecord()) {
        echo $indent . '  <li class="' . (strpos($sub->urlout, '#') ? 'anchor' : (substr($sub->urlin, -9) == 'index.php' ? 'folder' : 'page')) . '"><a href="' . $sub->urlout . '">' . $sub->name . '</a>';
        if($user->GodMode)
          echo ' <a class="img" href="' . $_SERVER['PHP_SELF'] . '?page=' . $sub->id . '" title="view details for this page"><img src="/style/details.png" alt="details" /></a>';
        echo ' - <span class="detail">' . $sub->tooltip . '</span>';
        if($sub->isparent)
          writesubpages($sub->id, '  ' . $indent);
        echo "</li>\n";
      }
      echo $indent . '</ul>';
    }
  }

/*-------------------------------------------------------------[ xlatvisible ]--
  Translates visibility flags into something readable.
  $flags = Flags value for a page.
  @return = Readable form of page visibility flags.
*/
  function xlatvisible($flags) {
    $flags &= _FLAG_PAGES_HIDELOGIN | _FLAG_PAGES_HIDENOLOGIN;  // strip off other flags
    switch($flags) {
      case _FLAG_PAGES_HIDELOGIN | _FLAG_PAGES_HIDENOLOGIN:
        return 'admin only';
        break;
      case _FLAG_PAGES_HIDENOLOGIN:
        return 'logged-in users only';
        break;
      case _FLAG_PAGES_HIDELOGIN:
        return 'non-logged-in users only';
        break;
      default:
        return 'everyone';
        break;
    }
  }

/*----------------------------------------------------------------[ pageform ]--
  Writes out the page enter/edit form.
  $p = Page being edited.  Defaults to adding a new page.
*/
  function pageform($p = false) {
    $editpage = new auForm('editpage');
    if($p)
      $editpage->AddData('id', $_GET['page']);
    $editpage->AddField('name', 'name', 'text to display for links to this page', true, ($p ? $p->name : ''), _AU_FORM_FIELD_NORMAL, 30, 55);
    $editpage->AddField('tooltip', 'tooltip', 'text to pop up when the mouse is over a link to this page', false, ($p ? $p->tooltip : ''), _AU_FORM_FIELD_NORMAL, 60, 255);
    $editpage->AddField('parent', 'parent', 'id of the page this page belongs under', false, ($p ? $p->parent : $_GET['page']), _AU_FORM_FIELD_NORMAL, 1, 5);
    $editpage->AddField('sort', 'sort', 'numeric sorting field', false, ($p ? $p->sort : ''), _AU_FORM_FIELD_NORMAL, 1, 2);
    $editpage->AddField('urlout', 'external url', 'url to use when linking to this page', true, ($p ? $p->urlout : ''), _AU_FORM_FIELD_NORMAL, 60, 255);
    $editpage->AddField('urlin', 'internal url', 'url to actual script, along with any important querystring variables', false, ($p ? $p->urlin : ''), _AU_FORM_FIELD_NORMAL, 60, 255);
    $editpage->AddSelect('view', 'visible to', 'select who will get links to this page', array(0 => 'everyone', _FLAG_PAGES_HIDELOGIN => 'non-logged-in users only', _FLAG_PAGES_HIDENOLOGIN => 'logged-in users only', _FLAG_PAGES_HIDELOGIN | _FLAG_PAGES_HIDENOLOGIN => 'admin only'), ($p ? $p->flags & (_FLAG_PAGES_HIDELOGIN | _FLAG_PAGES_HIDENOLOGIN) : 0));
    $editpage->AddField('loghits', 'log hits', 'hits to this page should be logged for statistical purposes', false, ($p ? ($p->flags & _FLAG_PAGES_NOLOGHITS) == 0 : true), _AU_FORM_FIELD_CHECKBOX);
    $editpage->AddField('comments', 'allow comments', 'show user comments with a form to enter comments', false, ($p ? ($p->flags & _FLAG_PAGES_COMMENTS) > 0 : false), _AU_FORM_FIELD_CHECKBOX);
    $editpage->AddField('section', 'section', 'this page is the index of a section', false, ($p ? $p->flags & _FLAG_PAGES_ISSECTION : false), _AU_FORM_FIELD_CHECKBOX);
    $editpage->AddField('description', 'description', 'description of this page (for meta tag)', false, $p->description, _AU_FORM_FIELD_MULTILINE, 50);
    $editpage->AddField('keywords', 'keywords', 'keywords for this page (for meta tag)', false, $p->keywords, _AU_FORM_FIELD_NORMAL, 60, 255);
    $editpage->AddButtons('save', $p ? 'update this page' : 'add this page');
    $editpage->WriteHTML();
  }
?>
