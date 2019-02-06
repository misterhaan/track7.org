<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html(['vue' => true]);
$html->Open('messages');
?>
			<h1>track7 messages</h1>

<?php
if($user->IsLoggedIn()) {
?>
			<div id=messages>
				<div id=conversations>
					<form id=sendtouser>
						<label title="search for a user to send a message to"><span class=field>
							<input id=usermatch placeholder="find a person" autocomplete=off v-model=usermatch v-on:blur=HideUserSuggestions(250) v-on:keydown.esc=HideUserSuggestions v-on:keydown.down.prevent=NextUser v-on:keydown.up.prevent=PrevUser v-on:keydown.enter.prevent=SelectCursorUser v-on:keydown.tab=SelectCursorUser>
						</span></label>
					</form>
					<ol class=usersuggest v-if="usermatch.length >= 3">
						<li class=message v-if=findingusers>finding people...</li>
						<li class=suggesteduser v-for="user in matchingusers" :class="{highlight: user.id == cursor.id}" v-on:click.prevent=GetConversation(user)>
							<img class=avatar alt="" :src=user.avatar>
							<span class=username :class="{friend: user.isfriend == 1}" :title="user.isfriend == 1 ? (user.displayname || user.username) + ' is your friend' : null">{{user.displayname || user.username}}</span>
						</li>
						<li class=message v-if="!findingusers && matchingusers.length < 1">nobody here by that name</li>
					</ol>
					<ol class=conversations>
						<li v-for="conv in conversations" :class="{selected: selected.id == conv.id}">
							<header :class="{read: +conv.hasread, outgoing: +conv.issender, incoming: !+conv.issender}" v-on:click.prevent="Select(conv)"><img class=avatar :src=conv.avatar><span>{{conv.displayname}}</span><time :datetime=conv.sent.datetime :title=conv.sent.tooltip>{{conv.sent.display}}</time></header>
<?php ShowMessages(); ?>
						</li>
					</ol>
				</div>

				<div id=conversationpane v-if=selected>
<?php ShowMessages(true); ?>
				</div>
			</div>
<?php
} else {
?>
			<p>
				hello, mysterious stranger!  while we welcome your messages to track7
				users, we suggest you either sign in or leave a contact e-mail or url
				where you can receive a response.
			</p>
			<form id=sendmessage v-on:submit.prevent=Send>
				<label title="user who will receive this message">
					<span class=label>to:</span>
					<span class=field>
						<input id=usermatch autocomplete=off v-model=usermatch v-if=!chosenuser v-on:blur=HideUserSuggestions(250) v-on:keydown.esc=HideUserSuggestions v-on:keydown.down.prevent=NextUser v-on:keydown.up.prevent=PrevUser v-on:keydown.enter.prevent=SelectCursorUser v-on:keydown.tab.prevent=SelectCursorUser>
						<span v-if=chosenuser>
							<img class="avatar inline" :src=chosenuser.avatar>
							<a :href="'/user/' + chosenuser.username + '/'">{{chosenuser.displayname || chosenuser.username}}</a>
							<a class="action del" v-on:click.prevent=Clear :title="'remove ' + chosenuser.displayname + ' and choose someone else'"></a>
						</span>
					</span>
				</label>
				<ol class=usersuggest v-if="usermatch.length >= 3">
					<li class=message v-if=findingusers>finding people...</li>
					<li class=suggesteduser v-for="user in matchingusers" v-on:click=Select(user) :class="{highlight: user.id == cursor.id}">
						<img class=avatar alt="" :src=user.avatar>
						<span>{{user.displayname || user.username}}</span>
					</li>
					<li class=message v-if="!findingusers && matchingusers.length < 1">nobody here by that name</li>
				</ol>
				<label title="your name">
					<span class=label>from:</span>
					<span class=field><input id=fromname maxlength=48></span>
				</label>
				<label title="e-mail address or url where replies can be sent (optional)">
					<span class=label>contact:</span>
					<span class=field><input id=fromcontact maxlength=255></span>
				</label>
				<label class=multiline title="message text you would like to send (markdown allowed)">
					<span class=label>message:</span>
				<span class=field><textarea id=markdown></textarea></span>
				</label>
				<button :disabled=!chosenuser>send</button>
				<template v-for="msg in sentmessages">
				<h2>message sent {{msg.sent.display}}</h2>
				<div v-html=msg.html></div>
				</template>
			</form>
<?php
}
$html->Close();

function ShowMessages($pane = false) {
	global $user;
	$conv = $pane ? 'selected' : 'conv';
	// TODO:  add preview button or live preview feature
?>
							<ol class=messages<?php if(!$pane) echo ' v-if="selected.id == conv.id"'; ?>>
								<li class=loading v-if="<?=$conv; ?>.loading">
									loading messages...
								</li>
								<li class="showmore calltoaction" v-if="<?=$conv; ?>.hasmore">
									<a class="action get" v-on:click.prevent="LoadMessages(<?=$conv; ?>)" href="#!LoadMessages">load older messages</a>
								</li>
								<li v-for="msg in <?=$conv; ?>.messages" :class="{outgoing: msg.outgoing == 1, incoming: msg.outgoing != 1}" :id="'<?=$pane ? 'pm' : 'm'; ?>' + msg.id">
									<div class=userinfo>
										<div class=username v-if="msg.outgoing != 1 && !<?=$conv; ?>.username && !msg.contacturl">{{msg.name}}</div>
										<div class=username v-if="msg.outgoing != 1 && !<?=$conv; ?>.username && msg.contacturl"><a :href=msg.contacturl></a>{{msg.name}}</div>
										<div class=username v-if="msg.outgoing == 0 && <?=$conv; ?>.username"><a :href="'/user/' + <?=$conv; ?>.username + '/'"></a>{{<?=$conv; ?>.displayname}}</div>
										<div class=username v-if="msg.outgoing == 1"><a href="/user/<?=$user->Username; ?>/"><?=$user->DisplayName; ?></a></div>
										<img class=avatar v-if="msg.outgoing != 1 && <?=$conv; ?>.avatar" :src="<?=$conv; ?>.avatar">
										<img class=avatar v-if="msg.outgoing == 1" src="<?=$user->Avatar; ?>">
									</div>
									<div class=message>
										<header>sent <time :datetime=msg.sent.datetime>{{msg.sent.display}}</time></header>
										<div class=content v-html=msg.html></div>
									</div>
								</li>
								<li class="outgoing reply" v-if="<?=$conv; ?>.username">
									<div class=userinfo>
										<div class=username><a href="/user/<?=$user->Username; ?>/"><?=$user->DisplayName; ?></a></div>
										<img class=avatar src="<?=$user->Avatar; ?>">
									</div>
									<div class=message>
										<form class=reply v-on:submit.prevent="Reply(<?=$conv; ?>)">
											<label class=multiline title="message text you would like to send (markdown allowed)">
												<span class=label>reply:</span>
												<span class=field><textarea v-model="<?=$conv; ?>.response" :placeholder="'write to ' + <?=$conv; ?>.displayname"></textarea></span>
											</label>
											<button>send</button>
										</form>
									</div>
								</li>
							</ol>
<?php
}
