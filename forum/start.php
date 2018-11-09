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

$html = new t7html(['vue' => true]);
$html->Open('new discussion');
?>
			<h1>start new discussion</h1>
			<form id=editdiscussion v-on:submit.prevent=Save>
<?php
if($user->IsLoggedIn()) {
?>
				<label>
					<span class=label>name:</span>
					<span class=field><a href="/user/<?=$user->Username; ?>/"><img class="inline avatar" src="<?=$user->Avatar; ?>"> <?=htmlspecialchars($user->DisplayName); ?></a></span>
				</label>
<?php
} else {
?>
				<label title="tell us your name to make it easier to talk to you, or better yet:  sign in!">
					<span class=label>name:</span>
					<span class=field><input name=name v-model=name placeholder="random internet person" maxlength=48></span>
				</label>
				<label title="leave a contact url or e-mail address to give people another option of contacting you">
					<span class=label>contact:</span>
					<span class=field><input name=contact v-model=contact maxlength=255></span>
				</label>
<?php
}
?>
				<label>
					<span class=label>title:</span>
					<span class=field><input name=title v-model=title maxlength=255 required></span>
				</label>
				<fieldset class=checkboxes>
					<legend>tags:</legend>
					<span class=field>
<?php
if($tags = $db->query('select id, name from forum_tags order by name'))
	while($tag = $tags->fetch_object()) {
?>
						<label class=checkbox>
							<input type=checkbox name=tags[] value=<?=+$tag->id; ?> v-model=tags>
							<?=htmlspecialchars($tag->name); ?>
						</label>
<?php
	}
?>
					</span>
				</fieldset>
				<label class=multiline title="your message to start the discussion (you can use markdown here)">
					<span class=label>message:</span>
					<span class=field><textarea name=message rows="" cols="" v-model=message></textarea></span>
				</label>
				<button :disabled="saving || !hasRequiredFields" :class="{working: saving}">start discussion</button>
			</form>
<?php
$html->Close();
