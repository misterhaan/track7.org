<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	$html = new t7html([]);
	$html->Open('entry not found - blog');
?>
			<h1>404 blog entry not found</h1>

			<p>
				sorry, we don’t seem to have a blog entry by that name.  try the list of
				<a href="<?php echo dirname($_SERVER['SCRIPT_NAME']); ?>/">all blog entries</a>.
			</p>
<?php
	$html->Close();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' entry - blog');
?>
			<h1><?php echo $id ? 'edit' : 'add'; ?> entry</h1>
			<form id=editentry data-entryid="<?php echo $id; ?>" v-on:submit.prevent=Save>
				<label>
					<span class=label>title:</span>
					<span class=field><input id=title maxlength=128 required v-model=title v-on:change=ValidateDefaultUrl></span>
				</label>
				<label>
					<span class=label>url:</span>
					<span class=field><input id=url maxlength=32 pattern="[a-z0-9\.\-_]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
					<span class=validation></span>
				</label>
				<label class=multiline>
					<span class=label>entry:</span>
					<span class=field><textarea id=content required rows="" cols="" v-model=content></textarea></span>
				</label>
				<label>
					<span class=label>tags:</span>
					<span class="field list" data-tagtype=blog>
						<span class=chosen v-for="(tag, index) in tags"><span>{{tag}}</span><a class="action del" href="#deltag" v-on:click.prevent=DelTag(index) :title="'remove the ' + tag + ' tag from this blog entry'"></a></span>
						<span class=suggestinput>
							<input id=tags autocomplete=off v-model=tagSearch  v-on:keydown.down.prevent=NextTag v-on:keydown.up.prevent=PrevTag v-on:dblclick=ShowTagSuggestions v-on:blur=HideTagSuggestions(250) v-on:keydown.esc=HideTagSuggestions v-on:keydown.enter.prevent=AddCursorTag v-on:keydown.comma.prevent=AddTypedTag v-on:keydown.tab=AddCursorTag v-on:keydown.backspace=DelLastTag v-on:keydown=TagSearchKeyPress>
							<span class=suggestions v-if=showTagSuggestions>
								<span v-for="tag in tagChoices" v-html=tag :class="{selected: tag.replace(/<[^>]>/g, '') == tagCursor}" v-on:click=AddTag(tag)></span>
							</span>
						</span>
					</span>
				</label>
				<button id=save :disabled=saving :class="{working: saving}">save</button>
			</form>
<?php
$html->Close();
