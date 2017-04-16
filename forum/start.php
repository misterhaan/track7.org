<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'post': PostDiscussion(); break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true]);
$html->Open('new discussion');
?>
			<h1>start new discussion</h1>
			<form id=editdiscussion data-bind="submit: Post">
<?php
if($user->IsLoggedIn()) {
?>
				<label>
					<span class=label>name:</span>
					<span class=field><a href="/user/<?php echo $user->Username; ?>/"><img class="inline avatar" src="<?php echo $user->Avatar; ?>"> <?php echo htmlspecialchars($user->DisplayName); ?></a></span>
				</label>
<?php
} else {
?>
				<label title="tell us your name to make it easier to talk to you, or better yet:  sign in!">
					<span class=label>name:</span>
					<span class=field><input data-bind="value: name;" placeholder="random internet person" maxlength=48></span>
				</label>
				<label title="leave a contact url or e-mail address to give people another option of contacting you">
					<span class=label>contact:</span>
					<span class=field><input data-bind="value: contact;" maxlength=255></span>
				</label>
<?php
}
?>
				<label>
					<span class=label>title:</span>
					<span class=field><input data-bind="textInput: title" maxlength=255></span>
				</label>
				<fieldset class=checkboxes>
					<legend>tags:</legend>
					<span class=field>
<?php
if($tags = $db->query('select id, name from forum_tags order by name'))
	while($tag = $tags->fetch_object()) {
?>
						<label class=checkbox>
							<input type=checkbox value=<?php echo +$tag->id; ?> data-bind="checked: tags">
							<?php echo htmlspecialchars($tag->name); ?>
						</label>
<?php
	}
?>
					</span>
				</fieldset>
				<label class=multiline title="your message to start the discussion (you can use markdown here)">
					<span class=label>message:</span>
					<span class=field><textarea rows="" cols="" data-bind="textInput: message"></textarea></span>
				</label>
				<button data-bind="enable: !saving() && title().trim() && message().trim(), css: {working: saving()}">start discussion</button>
			</form>
<?php
$html->Close();

function PostDiscussion() {
	global $ajax, $db, $user;
	$intrans = false;
	if(isset($_POST['name']) && isset($_POST['contact']) && isset($_POST['title']) && isset($_POST['taglist']) && isset($_POST['markdown'])) {
		$title = trim($_POST['title']);
		$markdown = trim($_POST['markdown']);
		if($title && $markdown) {
			$userid = $user->IsLoggedIn() ? +$user->ID : null;
			$name = $user->IsLoggedIn() ? '' : trim($_POST['name']) ? trim($_POST['name']) : 'random internet person';
			$contact = $user->IsLoggedIn() ? '' : trim($_POST['contact']) ? t7format::Link(trim($_POST['contact'])) : '';
			$tags = $_POST['taglist'] ? explode(',', $_POST['taglist']) : [];
			$html = t7format::Markdown($markdown);
			$posted = +time();
			$intrans = $db->begin_transaction();
			if($ins = $db->prepare('insert into forum_discussions (title) values (?)'))
				if($ins->bind_param('s', $title))
					if($ins->execute()) {
						$discussion = $ins->insert_id;
						$ins->close();
						if($ins = $db->prepare('insert into forum_replies (discussion, posted, user, name, contacturl, html, markdown) values (?, ?, ?, ?, ?, ?, ?)'))
							if($ins->bind_param('iiissss', $discussion, $posted, $userid, $name, $contact, $html, $markdown))
								if($ins->execute()) {
									$ins->close();
									if($ins = $db->prepare('insert into forum_discussion_tags (discussion, tag) values (?, ?)'))
										if($ins->bind_param('ii', $discussion, $tag)) {
											foreach($tags as $tag)
												if(!$ins->execute())
													$ajax->Fail('error saving tag ' . $tag . ':  ' . $ins->error);
											if(!$ajax->Data->fail) {
												$ins->close();
												if($db->real_query('update forum_tags set lastused=\'' . $posted . '\', count=(select count(1) from forum_discussion_tags where tag=forum_tags.id) where \',' . $db->escape_string($_POST['taglist']) . ',\' like concat(\'%,\', id, \',%\')')) {
													if($user->IsLoggedIn())
														if(!$db->real_query('update users_stats as u set u.replies=(select count(1) from forum_replies where user=u.id) where u.id=\'' . +$user->ID . '\''))
															$ajax->Fail('error updating user statistics:  ' . $db->error);
													if(!$ajax->Data->fail) {
														$db->commit();
														$intrans = false;
														$ajax->Data->url = dirname($_SERVER['PHP_SELF']) . '/' . $discussion;
													}
												} else
													$ajax->Fail('error updating tag statistics:  ' . $db->error);
											}
										} else
											$ajax->Fail('error binding tag parameters:  ' . $ins->error);
									else
										$ajax->Fail('error preparing to save tags:  ' . $db->error);
								} else
									$ajax->Fail('error executing save message:  ' . $ins->error);
							else
								$ajax->Fail('error binding message parameters:  ' . $ins->error);
						else
							$ajax->Fail('error preparing to save message:  ' . $db->error);
					} else
						$ajax->Fail('error executing create discussion:  ' . $ins->error);
				else
					$ajax->Fail('error binding discussion title:  ' . $ins->error);
			else
				$ajax->Fail('error preparing to create discussion:  ' . $db->error);
			if($intrans)
				$db->rollback();
		} else
			$ajax->Fail('you must provide a discussion title and a message to start things off');
	} else
		$ajax->Fail('field list not correct.  did you use the official form?');
}
