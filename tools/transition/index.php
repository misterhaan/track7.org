<?php
  define('TR_USERS', 1);
  define('TR_BLOG', 2);
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
