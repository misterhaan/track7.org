<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  // the following arrays are the values for the select elements on the form
  $sexvalues = array('it|its' => 'unknown / other', 'he|his' => 'male', 'she|her' => 'female');
  $fromvalues = auFormSelect::ArrayIndex(array('nobody', 'my own brute strength', 'ted nugent', 'a search warrant', 'the doorman'));
  $favoritevalues = auFormSelect::ArrayIndex(array('nothing', 'the guestbook', 'the analog underground', 'pen vs. sword', 'a/v department', 'the photo album', 'geek', 'the forums', 'the whole thing', 'cowboyneal'));
  $duckvalues = auFormSelect::ArrayIndex(array('nothing at all', '"go outside where you belong, you stupid ducks!"', '"i don\'t know what to say to a room full of ducks"', '"quack"', '"what do you mean--african or european ducks?"', '"sorry about that whole \'duck hunt\' thing a few years back"', '"i\'m a mute, you insensitive clod!"'));
  $doingvalues = auFormSelect::ArrayIndex(array('signing this guestbook', 'hiding from BRAD, the evil tape monster', 'trying to be funny', 'spamming', 'plotting the demise of THE MAN', 'eating something with LOTS of carbs', 'failing another class', 'rebelling for the sake of rebelling', 'laughing in the face of death (or was it seth?)', 'trying to take over the world', 'fumigating', 'chasing punk kids off\'a my lawn (shaking fist at them)'));

  $signform = new auForm('gbsign');
  $iqset = new auFormFieldSet('iq test');
  if($user->Valid)
    $iqset->AddText('name', $user->Name);
  else {
    $iqset->AddField('name', 'what is your name?', '', true, '', _AU_FORM_FIELD_NORMAL, 30, 45);
    $iqset->AddField('contact', 'what is your e-mail / website address?', '', false, '', _AU_FORM_FIELD_NORMAL, 30, 65);
  }
  $iqset->AddSelect('sex', 'what is your gender?', '', $sexvalues);
  $iqset->AddSelect('from', 'who let you in here?', '', $fromvalues);
  $iqset->AddSelect('favorite', 'what is your favorite part of track7?', '', $favoritevalues);
  $iqset->AddSelect('duck', 'what would you say to a room full of ducks?', '', $duckvalues);
  $iqset->AddSelect('doing', 'what are doing?', '', $doingvalues);
  $signform->AddFieldSet($iqset);
  $madlibset = new auFormFieldSet('mad-lib');
  $madlibset->AddField('noun1', 'noun', 'enter a noun to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('noun2', 'noun', 'enter a noun to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('noun3', 'noun', 'enter a noun to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('verb1', 'verb', 'enter a verb to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('verb2', 'verb', 'enter a verb to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('verb3', 'verb', 'enter a verb to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('adj1', 'adjective', 'enter an adjective to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('adj2', 'adjective', 'enter an adjective to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('pnoun1', 'noun (plural)', 'enter a plural noun to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $madlibset->AddField('pnoun2', 'noun (plural)', 'enter a plural noun to be used in the mad-lib', false, '', _AU_FORM_FIELD_NORMAL, 10);
  $signform->AddFieldSet($madlibset);
  $signform->AddField('comments', 'comments', 'enter your comments about track7 (t7code allowed)', false, '', _AU_FORM_FIELD_BBCODE);
  $signform->AddButtons(array('preview', 'save'), array('see what your entry will look like', 'save your entry in the track7 guestbook'));

  if($signform->Submitted()) {
    switch($signform->Submitted()) {
      case 'preview':
        $page->Start('sign the guestbook', 'guestbook entry preview');
        if($signform->CheckInput($user->Valid) && checkform()) {
          $page->Heading('preview');
?>
      <p>
        here is a preview of your entry into the guestbook.&nbsp; nothing has
        been saved yet--you will need to click the save button before that
        happens.&nbsp; if you included any links (like your e-mail address or
        personal website), it's a good idea to shift-click on them to make sure
        they go where you wanted them to.&nbsp; so look over your entry, make
        whatever changes you like below, or hit save if you're satisfied how it
        came out.&nbsp; you have to actually save your entry to see the results
        of the mad-lib section--here you will just see the words you entered.
      </p>

      <div class="gbintro">
<?=buildcomments(0); ?>

<?
          if(strlen($_POST['noun1']) > 0 && strlen($_POST['noun2']) > 0 && strlen($_POST['noun3']) > 0 && strlen($_POST['adj1']) > 0 && strlen($_POST['adj2']) > 0 && strlen($_POST['verb1']) > 0 && strlen($_POST['verb2']) > 0 && strlen($_POST['verb3']) > 0 && strlen($_POST['pnoun1']) > 0 && strlen($_POST['pnoun2']) > 0) {
?>
      <div class="madlib">
        nouns:&nbsp; <span class="response"><?=htmlspecialchars($_POST['noun1'] . ', '. $_POST['noun2'] . ', '. $_POST['noun3']); ?></span><br />
        adjectives:&nbsp; <span class="response"><?=htmlspecialchars($_POST['adj1'] . ', '. $_POST['adj2']); ?></span><br />
        verbs:&nbsp; <span class="response"><?=htmlspecialchars($_POST['verb1'] . ', '. $_POST['verb2'] . ', '. $_POST['verb3']); ?></span><br />
        nouns (plural):&nbsp; <span class="response"><?=htmlspecialchars($_POST['pnoun1'] . ', '. $_POST['pnoun2']); ?></span>
      </div>

<?
          }
        }
        break;
      case 'save':
        if($signform->CheckInput($user->Valid) && checkform()) {
          srand((double)microtime() * 1111111);
          $comments = buildcomments(rand(60, 140));
          $madlib = buildmadlib();
          if($id = $db->getid('guestbook', 'id', 'error getting next available guestbook entry id')) {
            $entry = 'insert into guestbook (id, site, instant, useragent, ip, version, name, comments) values (' . $id . ', \'track7\', ' . time() . ', \'' . addslashes($_SERVER['HTTP_USER_AGENT']) . '\', \'' . $_SERVER['REMOTE_ADDR'] . '\', 2, \'' . addslashes($_POST['name']) . '\', \'' . addslashes($comments . $madlib . "\n") . '\')';
            if($db->query($entry, 'unable to save your entry into the guestbook')) {
              auSend::EMail($_POST['name'] . ' has signed the guestbook', strip_tags($comments . $madlib), 'guestbook@' . _HOST, 'misterhaan@' . _HOST, 'track7 guestbook', 'misterhaan');
              header('Location: http://' . $_SERVER['HTTP_HOST'] . '/gb-view.php');
              die;
            }
          }
          $page->Start('sign the guestbook', 'save guestbook entry');
        }
        break;
      default:
        $page->Start('sign the guestbook', 'what did you do?!');
        $page->Error('it appears that you somehow clicked a button that doesn\'t exist!');
    }
  } else {
    // show the normal 'sign the guestbook' page
    $page->Start('sign the guestbook');
?>
      <p>
        signing the guestbook can be fun and entertaining!&nbsp; to maximize
        your fun and entertainment (as well as that of everyone who might end up
        reading the guestbook), you should try to change the responses to
        something other than the lame default responses, and also fill out the
        mad-lib section!&nbsp; the comments are required, so if you leave it
        blank you will disappoint your grandmother and a ninja master will come
        to your house and beat you with a bamboo pole until you actually have
        something to say.&nbsp; it will suffice if all you have to say is
        &quot;ow, that ninja's bamboo pole really hurt!&quot;
      </p>
      <p>
        if you're not feeling creative you could just
        <a href="gb-view.php">view the guestbook</a> (i suppose).
      </p>

<?
  }
  $signform->WriteHTML($user->Valid);

  $page->End();

  // ---------------------------------------------------------[ checkform ]-- //
  function checkform() {
    global $page, $signform;
    if(strlen($_POST['comments']) < 5) {
      // this will only do something if it hasn't already been started (if they clicked 'save')
      $page->Start('sign the guestbook', 'save guestbook entry');
      $page->Heading('say what?');
?>
      <p>
        it appears that you didn't have anything to say!&nbsp; you need to put
        at least 5 characters in the comments box, even if it's just &quot;i
        like pork&quot; or something.
      </p>

<?
      $_POST['comments'] = 'i like pork';
      return false;
    }
    elseif(strpos($_POST['noun1'] . $_POST['noun2'] . $_POST['noun3'] . $_POST['adj1'] . $_POST['adj2'] . $_POST['verb1'] . $_POST['verb2'] . $_POST['verb3'] . $_POST['pnoun1'] . $_POST['pnoun2'], 'http://') !== false ||
           strlen($_POST['noun1']) && $_POST['noun1'] == $_POST['noun2'] && $_POST['noun1'] == $_POST['noun3'] && $_POST['noun1'] == $_POST['adj1'] && $_POST['noun1'] == $_POST['adj2'] && $_POST['noun1'] == $_POST['verb1'] && $_POST['noun1'] == $_POST['verb2'] && $_POST['noun1'] == $_POST['verb3'] && $_POST['noun1'] == $_POST['pnoun1'] && $_POST['noun1'] == $_POST['pnoun2']
          ) {
        $page->Start('sign the guestbook', 'save guestbook entry');
        $page->Heading('no coming in, please');
?>
      <p>
        it appears that you are attempting to spam this website!&nbsp; that is a
        really crappy thing to do -- shame on you!&nbsp; if you are in fact not
        trying to spam this website and actually do want to sign the guestbook,
        please be sure to use the official form below.
      </p>

<?
      return false;
    }
    return true;
  }

  // -----------------------------------------------------[ buildcomments ]-- //
  function buildcomments($iq) {
    global $user;
    if($user->valid) {
      $url = '/user/' . $user->Name . '/';
      $tooltip = 'view ' . $user->Name . '\'s profile';
      $_POST['name'] = $user->Name;
    } else {
      $url = auText::FixLink($_POST['contact']);
      $tooltip = 'contact ' . $_POST['name'];
      $_POST['name'] = strlen($_POST['name']) > 0 ? htmlspecialchars($_POST['name']) : 'anonymous';
    }
    list($it, $its) = explode('|', $_POST['sex'], 2);
    return '        <span class="response">' . htmlspecialchars($_POST['from']) . '</span> let ' . (strlen($url) > 0 ? '<a href="' . $url . '" title="' . $tooltip . '">' . $_POST['name'] . '</a>' : '<span class="response">' . $_POST['name'] . '</span>') . ' into track7.&nbsp;' . "\n"
         . '        ' . $_POST['name'] . ' was <span class="response">' . htmlspecialchars($_POST['doing']) . '</span> while ' . $it . ' was here.&nbsp;' . "\n"
         . '        ' . $its . ' favorite part of track7 is <span class="response">' . htmlspecialchars($_POST['favorite']) . '</span>, and ' . $it . ' says <span class="response">' . htmlspecialchars($_POST['duck']) . '</span> when confronted with a room full of ducks.' . "\n"
         . ($iq > 0 ? '        ' . $it . ' scored a <span class="response">' . $iq . '</span> on the fake iq test.' . "\n" : '')
         . '      </div>' . "\n"
         . '      <p class="comments">' . "\n"
         . '        ' . auText::BB2HTML($_POST['comments']) . "\n"
         . '      </p>' . "\n";
  }

  // -------------------------------------------------------[ buildmadlib ]-- //
  function buildmadlib() {
    global $db;
    if(strlen($_POST['noun1']) <= 0 || strlen($_POST['noun2']) <= 0 || strlen($_POST['noun3']) <= 0 || strlen($_POST['adj1']) <= 0 || strlen($_POST['adj2']) <= 0 || strlen($_POST['verb1']) <= 0 || strlen($_POST['verb2']) <= 0 || strlen($_POST['verb3']) <= 0 || strlen($_POST['pnoun1']) <= 0 || strlen($_POST['pnoun2']) <= 0)
      return '';
    $madlib = 'select story, href, title from madlibs order by rand()';
    if($madlib = $db->GetRecord($madlib, 'error getting a random madlib', 'no madlibs found', true)) {
      $words = array(
        '$$NOUN1$$',
        '$$NOUN2$$',
        '$$NOUN3$$',
        '$$ADJ1$$',
        '$$ADJ2$$',
        '$$VERB1$$',
        '$$VERB2$$',
        '$$VERB3$$',
        '$$PNOUN1$$',
        '$$PNOUN2$$'
      );
      $replace = array(
        htmlspecialchars($_POST['noun1']),
        htmlspecialchars($_POST['noun2']),
        htmlspecialchars($_POST['noun3']),
        htmlspecialchars($_POST['adj1']),
        htmlspecialchars($_POST['adj2']),
        htmlspecialchars($_POST['verb1']),
        htmlspecialchars($_POST['verb2']),
        htmlspecialchars($_POST['verb3']),
        htmlspecialchars($_POST['pnoun1']),
        htmlspecialchars($_POST['pnoun2'])
      );
      return '      <div class="madlib">' . "\n"
           . str_replace($words, $replace, $madlib->story) . "\n"
           . '      </div>' . "\n"
           . '      <div class="excerpt">an excerpt from <a href="' . $madlib->href . '" title="read the regular version of this story">' . $madlib->title . '</a></div>' . "\n";
    } else
      return '';
  }
?>
