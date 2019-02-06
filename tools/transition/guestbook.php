<?php
define('TR_GUESTBOOK', 13);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'entry':          GetEntry();       break;
		case 'savemessage':    SaveMessage();    break;
		case 'saveartcomment': SaveArtComment(); break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['vue' => true]);
$html->Open('guestbook');
?>
			<h1>guestbook migration</h1>

			<p>
				decide what to do with the guestbook entry shown below.  note that
				nothing tracks the choice made for each entry, so be careful not to copy
				the same entry twice.
			</p>

			<h2>guestbook entry #{{num}}</h2>
			<div class=meta><span class=posted>{{instant.display}}</span></div>
			<p class=fullentry v-html=fullentry></p>

			<dl>
				<dt>name</dt>
				<dd>{{name}}</dd>
				<dt>e-mail</dt>
				<dd>{{email}}</dd>
				<dt>website</dt>
				<dd>{{website}}</dd>
				<dt>comment</dt>
				<dd>{{comment}}</dd>
			</dl>

			<form id=gbaction>
				<fieldset>
					<legend>message to misterhaan</legend>
					<label><input type=radio name=msgfrom value=email checked> <span>from {{name}} ({{email}})</span></label>
					<label><input type=radio name=msgfrom value=website> <span>from {{name}} ({{website}})</span></label>
					<label><input type=radio name=msgfrom value=username> from user <input name=msgusername></label>
					<button v-on:click=SaveMessage>save as message</button>
				</fieldset>
				<fieldset>
					<legend>comment on art</legend>
					<label>art url: <?=t7format::FullUrl('/art/'); ?><input id=arturl></label>
					<label><input type=radio name=commentfrom value=email checked> <span>from {{name}} ({{email}})</span></label>
					<label><input type=radio name=commentfrom value=website> <span>from {{name}} ({{website}})</span></label>
					<label><input type=radio name=commentfrom value=username> from user <input name=commentusername></label>
					<button v-on:click=SaveComment>save as comment</button>
				</fieldset>
				<label><button v-on:click=Skip>skip</button> to entry #<input id=nextentry type=number step=1></label>
			</form>

			<ul>
				<li v-for="result in results">{{result}}</li>
			</ul>
<?php
$html->Close();

function GetEntry() {
	global $ajax, $db;
	if($entry = $db->query('select id, instant, version, name, comments from track7_t7data.guestbook where id>=\'' . +$_GET['id'] . '\' order by id limit 1'))
		if($entry = $entry->fetch_object()) {
			ParseComment($entry);
			$entry->instant = ['display' => t7format::LocalDate(t7format::DATE_LONG, $entry->instant), 'timestamp' => $entry->instant];
			$ajax->Data->entry = $entry;
		} else
			$ajax->Data->entry = false;
	else
		$ajax->Fail('error looking up guestbook entry:  ' . $db->error);
}

/**
 * Parse out the email, website, and comment from a guestbook entry.  Parsed
 * values are added as new properties.
 * @param object $entry guestbook entry row object
 */
function ParseComment($entry) {
	if(preg_match('/<a href="mailto:([^"]+)"/', $entry->comments, $matches))
		$entry->email = $matches[1];
	else
		$entry->email = '';
	if(preg_match('/<div class="comments">(.+?)<\/div>/s', $entry->comments, $matches))
		$entry->comment = $matches[1];
	elseif(preg_match('/<p class="comments">(.+)<\/p>/s', $entry->comments, $matches))
		$entry->comment = $matches[1];
	else
		$entry->comment = '';
	if(preg_match('/resides at <a href="([^"]+)"/', $entry->comments, $matches))
		$entry->website = $matches[1];
	elseif(preg_match('/castle known as <a href="([^"]+)"/', $entry->comments, $matches))
		$entry->website = $matches[1];
	elseif(preg_match('/let <a href="(http[^"]+)"/', $entry->comments, $matches))
		$entry->website = $matches[1];
	else
		$entry->website = '';
	$entry->comments = '<div class="gbintro">' . "\n" . $entry->comments;
}

function SaveMessage() {
	global $ajax, $db;
	if(ParseInfo($fromuser, $name, $contact, $instant, $comment))
		if($insmsg = $db->prepare('insert into users_messages set sent=?, conversation=GetConversationID(1, ?), author=?, name=?, contacturl=?, html=?, hasread=1'))
			if($insmsg->bind_param('iiisss', $instant, $fromuser, $fromuser, $name, $contact, $comment))
				if($insmsg->execute()) {
					$insmsg->close();
					if($db->query('update users_conversations set latestmessage=(select id from users_messages where conversation=GetConversationID(1, ' . +$fromuser . ') order by sent desc limit 1) where id=GetConversationID(1, ' . +$fromuser . ') limit 2'))
						;  // all done here, everything worked!
					else
						$ajax->Fail('error updating latest message for conversation:  ' . $db->error);
				} else
					$ajax->Fail('error executing message save:  ' . $insmsg->error);
			else
				$ajax->Fail('error binding parameters to save message:  ' . $insmsg->error);
		else
			$ajax->Fail('error preparing to save message:  ' . $db->error);
}

function SaveArtComment() {
	global $ajax, $db;
	if(ParseInfo($userid, $name, $contact, $instant, $comment))
		if($artid = $db->query('select id from art where url=\'' . $db->escape_string($_POST['arturl']) . '\' limit 1'))
			if($artid = $artid->fetch_object()) {
				$artid = +$artid->id;
				if($inscomment = $db->prepare('insert into art_comments set art=?, posted=?, user=?, name=?, contacturl=?, html=?'))
					if($inscomment->bind_param('iiisss', $artid, $instant, $userid, $name, $contact, $comment))
						if($inscomment->execute()) {
							$inscomment->close();
							if($userid)
								$db->real_query('update users_stats set comments=comments+1 where id=\'' . +$userid . '\' limit 1');
						} else
							$ajax->Fail('error executing comment save:  ' . $inscomment->error);
					else
						$ajax->Fail('error binding parameters to save comment:  ' . $inscomment->error);
				else
					$ajax->Fail('error preparing to save comment:  ' . $db->error);
			} else
				$ajax->Fail('art not found');
		else
			$ajax->Fail('error looking up art:  ' . $db->error);
}

/**
 * Parse POST info needed to save a message or comment.
 * @param integer $userid author's user id, or null if anonymous
 * @param string $name author's name if anonymous
 * @param string $contact author's contact url if anonymous
 * @param integer $instant timestamp when the guestbook was signed
 * @param string $comment html text of guestbook message
 */
function ParseInfo(&$userid, &$name, &$contact, &$instant, &$comment) {
	global $ajax, $db;
	if($_POST['from'] == 'username')
		if($userid = $db->query('select id from users where username=\'' . $db->escape_string($_POST['username']) . '\' limit 1'))
			if($userid = $userid->fetch_object()) {
				$userid = $userid->id;
				$name = '';
				$contact = '';
			} else {
				$ajax->Fail('username not found');
				return false;
			}
		else {
			$ajax->Fail('error looking up username:  ' . $db->error);
			return false;
		}
	else {
		$userid = null;
		$name = $_POST['name'];
		$contact = $_POST[$_POST['from']];
	}
	$instant = +$_POST['instant'];
	$comment = '<p>' . $_POST['comment'] . '</p>';
	return true;
}
