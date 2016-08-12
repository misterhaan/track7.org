<?php
  define('TR_USERS', 1);
  define('TR_BLOG', 2);
  define('TR_GUIDES', 3);
  define('TR_MESSAGES', 4);
  define('TR_PHOTOS', 5);
  define('TR_ART', 6);
  require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

  $db->real_query('create table if not exists transition_status ('
      . 'id tinyint unsigned primary key not null, '
      . 'stepnum tinyint not null default 0, '
      . 'status varchar(64) not null default \'not started\')');

  $status = [];
  if($ss = $db->query('select id, stepnum, status from transition_status'))
    while($s = $ss->fetch_object())
      $status[$s->id] = $s;

  initStatus(TR_USERS);
  initStatus(TR_BLOG);
  initStatus(TR_GUIDES);
  initStatus(TR_MESSAGES);
  initStatus(TR_PHOTOS);
  initStatus(TR_ART);

  $html = new t7html([]);
  $html->Open('database transitions');
?>
      <h1>database transitions</h1>

      <table>
        <thead><tr>
          <th>subject</th>
          <th class=number>step</th>
          <th>status</th>
        </tr></thead>
        <tbody>
          <tr><td><a href="users.php">users</a></td><td><?php echo $status[TR_USERS]->stepnum; ?></td><td><?php echo $status[TR_USERS]->status; ?></td></tr>
          <tr><td><a href="blog.php">blog</a></td><td><?php echo $status[TR_BLOG]->stepnum; ?></td><td><?php echo $status[TR_BLOG]->status; ?></td></tr>
          <tr><td><a href="guides.php">guides</a></td><td><?php echo $status[TR_GUIDES]->stepnum; ?></td><td><?php echo $status[TR_GUIDES]->status; ?></td></tr>
          <tr><td><a href="messages.php">messages</a></td><td><?php echo $status[TR_MESSAGES]->stepnum; ?></td><td><?php echo $status[TR_MESSAGES]->status; ?></td></tr>
          <tr><td><a href="photos.php">photos</a></td><td><?php echo $status[TR_PHOTOS]->stepnum; ?></td><td><?php echo $status[TR_PHOTOS]->status; ?></td></tr>
          <tr><td><a href="art.php">art</a></td><td><?php echo $status[TR_ART]->stepnum; ?></td><td><?php echo $status[TR_ART]->status; ?></td></tr>
        </tbody>
      </table>
<?php
  $html->Close();

  function initStatus($id) {
    global $status, $db;
    if(!isset($status[$id])) {
      $status[$id] = (object)['stepnum' => 0, 'status' => 'not started'];
      $db->real_query('insert into transition_status set id=\'' . $id . '\', stepnum=0, status=\'not started\'');
    }
  }
?>
