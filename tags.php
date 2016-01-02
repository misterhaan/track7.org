<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  if(isset($_GET['ajax'])) {
    $ajax = new t7ajax();
    switch($_GET['ajax']) {
      case 'list':
        switch($_GET['type']) {
          case 'blog':
            $tags = $db->query('select name, count from blog_tags where count>1 order by lastused desc');
            if($tags) {
              $ajax->Data->tags = [];
              while($tag = $tags->fetch_object()) {
                $ajax->Data->tags[] = $tag;
              }
            } else {
              $ajax->Data->fail = true;
              $ajax->Data->message = 'error getting list of blog tags.';
            }
            break;
          default:
            $ajax->Data->fail = true;
            $ajax->Data->message = 'unknown tag type for list.  supported tag types are: blog.';
            break;
        }
        break;
      default:
        $ajax->Data->fail = true;
        $ajax->Data->message = 'unknown function name.  supported function names are: list.';
        break;
    }
    $ajax->Send();
    die;
  }
  // TODO:  some sort of html, probably listing tags with their descriptions and allowing admin to edit them
?>
