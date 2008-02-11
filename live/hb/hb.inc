<?
/******************************************************************************\
 * Title:    holler back shared functions
 * Purpose:  Provides functions used by multiple pages for holla back forums
\******************************************************************************/

  class HB {
    function TagLinks($tags) {
      if(strlen($tags)) {
      	foreach(explode(',', $tags) as $tag)
      	  $ret[] = '<a href="/hb/?tag=' . $tag . '" title="view threads tagged with &ldquo;' . $tag . '&rdquo;">' . $tag . '</a>';
      	return implode(', ', $ret);
      } else
        return '<em>(none)</em>';
    }

    function GetPostForm(&$db, &$user, $formid, $post = null, $thread = null, $quote = null) {
      require_once 'auForm.php';
      require_once 'auText.php';
      $f = new auForm($formid, $_SERVER['REQUEST_URI']);
      if($post)
        $f->AddText('posted by', $post->uid ? $post->login : 'anonymous');
      elseif($user->Valid)
        $f->AddText('posting as', $user->Name);
      else
        $f->AddHTML('posting as', 'anonymous (<a href="/user/login.php">log in</a> or <a href="/user/register.php">register</a>)');
      if(!$thread || $post->number == 1)
        $f->AddField('title', 'thread title', 'enter a title for this thread', true, $thread->title, _AU_FORM_FIELD_NORMAL, 40, 255);
      $f->AddField('subject', 'post subject', 'enter a subject for this post', false, $post ? $post->subject : ($quote ? HB::AddRe($quote->subject) : ($thread ? AddRe($thread->title) : '')), _AU_FORM_FIELD_NORMAL, 40, 255);
      if(!$thread || $post->number == 1) {
        $tags = 'select name from taginfo where type=\'threads\' order by name';
        if($tags = $db->Get($tags, '', '')) {
          while($tag = $tags->NextRecord())
            $tagvalues[$tag->name] = null;
          $f->AddMultiSelect('tags', 'tags', 'choose any categories this thread belongs in', $tagvalues, explode(',', $thread->tags));
        }
        if($user->GodMode)
          $f->AddField('taglist', 'tags', 'enter any tags at all, even if they don&rsquo;t exist yet', false, $thread->tags, _AU_FORM_FIELD_NORMAL, 40, 250);
      }
      $f->AddField('post', 'message', 'enter your post (you may use t7code)', true, $quote ? '[q=' . $quote->login . ']' . auText::HTML2BB($quote->post) . '[/q]' : auText::HTML2BB($post->post), _AU_FORM_FIELD_BBCODE);
      $f->AddButtons(array('preview', $user->Valid ? ($post ? 'edit' : 'post') . ' as ' . $user->Name : 'post anonymously'), array('preview your message before actually posting it', 'add your message'));
      return $f;
    }
  
    function AddRe($subject) {
      if(substr(strtolower($subject), 0, 4) == 're: ')
        return $subject;
      return 're: ' . $subject;
    }

    function ShowHistory(&$user, $history) {
      if($history) {
        foreach(explode('/', $history) as $edit)
          if($edit) {
            list($login, $time) = explode('|', $edit);
            $edits[] = 'edited by ' . $login . ', ' . strtolower($user->tzdate('g:i:s a, M d, Y', $time));
          }
        return '<p class="history">' . implode('<br />', $edits) . '</p>';
      }
      return false;
    }
  }
?>
