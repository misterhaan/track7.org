<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auForm.php';

  $page->Start('timestamp converter');

  $date = explode(':', date('m:d:Y:H:i:s'));
  if(!isset($_POST['month']) || strlen($_POST['month']) < 1)
    $_POST['month'] = $date[0];
  else
    $_POST['month'] = sprintf('%02d', $_POST['month']);
  if(!isset($_POST['day']) || strlen($_POST['day']) < 1)
    $_POST['day'] = $date[1];
  else
    $_POST['day'] = sprintf('%02d', $_POST['day']);
  if(!isset($_POST['year']) || strlen($_POST['year']) < 1)
    $_POST['year'] = $date[2];
  elseif(strlen($_POST['year']) < 4)
    $_POST['year'] += $_POST['year'] <= 80 ? 2000 : 1900;
  if(!isset($_POST['hour']) || strlen($_POST['hour']) < 1)
    $_POST['hour'] = $date[3];
  else
    $_POST['hour'] = sprintf('%02d', $_POST['hour']);
  if(!isset($_POST['min']) || strlen($_POST['min']) < 1)
    $_POST['min'] = $date[4];
  else
    $_POST['min'] = sprintf('%02d', $_POST['min']);
  if(!isset($_POST['sec']) || strlen($_POST['sec']) < 1)
    $_POST['sec'] = $date[5];
  else
    $_POST['sec'] = sprintf('%02d', $_POST['sec']);
  if(isset($_POST['to']))
    switch($_POST['to']) {
      case 'date':
?>
      <p class="info">timestamp <?=$_POST['timestamp']; ?> translates to <?=strtolower(date('D F j, Y g:i:s a', $_POST['timestamp'])); ?></p>

      <hr class="minor" />

<?
        break;
      case 'timestamp':
?>
      <p class="info">date <?=implode('-', array($_POST['month'], $_POST['day'], $_POST['year'])); ?> <?=implode(':', array($_POST['hour'], $_POST['min'], $_POST['sec'])); ?> translates to <?=strtotime(implode('-', array($_POST['year'], $_POST['month'], $_POST['day'])) . ' ' . implode(':', array($_POST['hour'], $_POST['min'], $_POST['sec']))); ?></p>

      <hr class="minor" />

<?
        break;
    }

  $form = new auForm('todate');
  $form->AddField('timestamp', 'timestamp', 'enter a unix timestamp to convert to a human-readable date/time', false, time());
  $form->AddButtons('date', 'convert this unix timestamp to a human-readable date', 'to');
  $form->WriteHTML(true);
?>
      <hr class="minor" />

<?
  $form = new auForm('totimestamp');
  $form->AddField('month', 'month', 'enter the month (1 - 12)', false, $_POST['month']);
  $form->AddField('day', 'day', 'enter the day (1 - 31)', false, $_POST['day']);
  $form->AddField('year', 'year', 'enter the year (YYYY)', false, $_POST['year']);
  $form->AddField('hour', 'hour', 'enter the hour (0 - 23)', false, $_POST['hour']);
  $form->AddField('min', 'minute', 'enter the minute (0 - 59)', false, $_POST['min']);
  $form->AddField('sec', 'second', 'enter the seconds (0 - 59)', false, $_POST['sec']);
  $form->AddButtons('timestamp', 'convert this date into a unix timestamp', 'to');
  $form->WriteHTML(true);

  $page->End();
?>
