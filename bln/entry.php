<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  $tag = false;
  if(isset($_GET['tag']))
    if($tag = $db->query('select name from blog_tags where name=\'' . $db->escape_string($_GET['tag']) . '\' limit 1'))
      if($tag = $tag->fetch_object())
        $tag = $tag->name;
    else {  // tag not found, so try getting to the entry without the tag
      header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $_GET['name']);
      die;
    }

  $entry = false;

  if(isset($_GET['name']) && $entry = $db->query('select id, url, title, status, posted, content from blog_entries where url=\'' . $db->escape_string($_GET['name']) . '\' limit 1'))
    $entry = $entry->fetch_object();
  if(!$entry || $entry->status != 'published' && !$user->IsAdmin()) {
    header('HTTP/1.0 404 Not Found');
    $html = new t7html([]);
    $html->Open($tag ? 'entry not found - ' . $tag . ' - blog' : 'entry not found - blog');
?>
      <h1>404 blog entry not found</h1>

      <p>
        sorry, we don’t seem to have a blog entry by that name.  try the list of
        <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all blog entries</a>.
      </p>
<?php
    $html->Close();
    die;
  }

  $html = new t7html(['ko' => true]);
  $html->Open(htmlspecialchars($entry->title) . ($tag ? ' - ' . $tag . ' - blog' : ' - blog'));
?>
      <h1><?php echo htmlspecialchars($entry->title); ?></h1>
<?php
  if($tags = $db->query('select t.name from blog_entrytags as et left join blog_tags as t on t.id=et.tag where et.entry=\'' . $entry->id . '\'')) {
    $entry->tags = [];
    while($t = $tags->fetch_object())
      $entry->tags[] = '<a href="' . ($tag ? '../' : '') . $t->name . '/" title="entries tagged ' . $t->name . '">' . $t->name . '</a>';
  }
?>
      <p class=postmeta>
        posted by <a href="/user/misterhaan/" title="view misterhaan’s profile">misterhaan</a>
<?php
  if(count($entry->tags)) {
?>
        in <?php echo join(', ', $entry->tags); ?>
<?php
  }
  if($entry->posted) {
?>
        on <time datetime="<?php echo date('c', $entry->posted); ?>" title="<?php echo t7format::LocalDate('g:i a \o\n l F jS Y', $entry->posted); ?>"><?php echo strtolower(t7format::LocalDate('M j, Y', $entry->posted)); ?></time>
<?php
  }
?>
      </p>
<?php
  if($user->IsAdmin()) {
?>
      <nav class=actions data-id="<?php echo $entry->id; ?>">
        <a class=edit href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?id=<?php echo $entry->id; ?>">edit this entry</a>
<?php
    if($entry->status == 'draft') {
?>
        <a id=publishentry class=publish href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?ajax=publish">publish this entry</a>
        <a id=delentry class=del href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/edit.php?ajax=delete">delete this entry</a>
<?php
    }
?>
      </nav>
<?php
  }
  echo $entry->content;
  // TODO:  show previous / next entry (tag or all), link back to list
?>
      <section id=comments>
        <h2>comments</h2>
        <p data-bind="visible: error(), text: error"></p>
        <p data-bind="visible: !loadingComments() && comments().length == 0">
          there are no comments on this entry so far.  you could be the first!
        </p>
        <!-- ko foreach: comments -->
        <section class=comment>
          <div class=userinfo>
            <div class=username data-bind="visible: !username && !contacturl, text: name"></div>
            <div class=username data-bind="visible: !username && contacturl"><a data-bind="text: name, attr: {href: contacturl}"></a></div>
            <div class=username data-bind="visible: username">
              <a data-bind="text: displayname || username, attr: {href: '/user/' + username + '/'}"></a>
              <img data-bind="visible: friend, attr: {title: (displayname || username) + ' is your friend'}" alt="*" src="/images/friend.png">
            </div>
            <a data-bind="visible: avatar"><img class=avatar alt="" data-bind="attr: {src: avatar}"></a>
            <div class=userlevel data-bind="visible: level, text:level"></div>
          </div>
          <div class=comment>
            <header>posted <time data-bind="text: posted.display, attr: {datetime: posted.datetime}"></time></header>
            <div class=content data-bind="visible: !editing(), html: html"></div>
            <div class="content edit" data-bind="visible: editing">
              <textarea data-bind="value: markdown"></textarea>
            </div>
            <footer data-bind="visible: canchange">
              <a class="okay action" data-bind="visible: editing(), click: $parent.SaveComment" href="/comments.php?ajax=save">save</a>
              <a class="cancel action" data-bind="visible: editing(), click: $parent.UneditComment" href="#">cancel</a>
              <a class="edit action" data-bind="visible: !editing(), click: $parent.EditComment" href="/comments.php?ajax=edit">edit</a>
              <a class="del action" data-bind="visible: !editing(), click: $parent.DeleteComment" href="/comments.php?ajax=delete">delete</a>
            </footer>
          </div>
        </section>

        <!-- /ko -->

        <form id=addcomment data-type=blog data-key=<?php echo $entry->id; ?>>
<?php
  if($user->IsLoggedIn()) {
?>
          <label title="you are signed in, so your comment will post with your avatar and a link to your profile">
            <span class=label>name:</span>
            <span class=field><a href="/user/<?php echo $user->Username; ?>/"><?php echo htmlspecialchars($user->DisplayName); ?></a></span>
          </label>
<?php
  } else {
?>
          <label title="please sign in or enter a name so we know what to call you">
            <span class=label>name:</span>
            <span class=field><input id=authorname></span>
          </label>
          <label title="enter a website, web page, or e-mail address if you want people to be able to find you">
            <span class=label>contact:</span>
            <span class=field><input id=authorcontact></span>
          </label>
<?php
  }
?>
          <label title="enter your comments using markdown">
            <span class=label>comment:</span>
            <span class=field><textarea id=newcomment></textarea></span>
          </label>
          <button id=postcomment>post comment</button>
        </form>
      </section>
<?php
  // TODO:  move comment stuff to t7html
  $html->Close();
?>
