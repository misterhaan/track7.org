<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if (isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch ($_GET['ajax']) {
		case 'match':
			DoMatch();
			break;
		case 'replace':
			Replace();
			break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['vue' => true]);
$html->Open('regular expression testing');
?>
<h1>regular expression testing</h1>

<div class=tabbed>
	<nav class=tabs>
		<a href=#match title="preg_match and preg_match_all">match</a>
		<a href=#replace title="preg_replace">replace</a>
	</nav>

	<section id=match class=tabcontent>
		<h2>match</h2>
		<p class=meta>
			using php function
			<a href="https://php.net/preg_match" v-if=!all>preg_match</a>
			<a href="https://php.net/preg_match_all" v-if=all>preg_match_all</a>
		</p>
		<form class=regextest v-on:submit.prevent=DoMatch>
			<label>
				<span class=label>pattern:</span>
				<span class=field><input v-model=pattern></span>
			</label>
			<label class=multiline>
				<span class=label>subject:</span>
				<span class=field><textarea v-model=subject></textarea></span>
			</label>
			<label class=checkbox>
				<span class=label></span>
				<span class="checkbox"><input type=checkbox v-model=all>find all matches</span>
			</label>
			<button>match</button>
		</form>
		<div v-if=checked>
			<p v-if="found && matches.length < 1">no matches found</p>
			<ol class=matches>
				<li v-for="match in matches">
					<pre><code>{{match}}</code></pre>
				</li>
			</ol>
		</div>
	</section>

	<section id=replace class=tabcontent>
		<h2>replace</h2>
		<p class=meta>
			using php function <a href="http://php.net/preg_replace">preg_replace</a>
		</p>
		<form class=regextest v-on:submit.prevent=Replace>
			<label>
				<span class=label>pattern:</span>
				<span class=field><input v-model=pattern></span>
			</label>
			<label>
				<span class=label>replace:</span>
				<span class=field><input v-model=replacement></span>
			</label>
			<label class=multiline>
				<span class=label>subject:</span>
				<span class=field><textarea v-model=subject></textarea></span>
			</label>
			<button>replace</button>
		</form>
		<pre v-if=replaced><code>{{result}}</code></pre>
	</section>
</div>

<?php
$html->Close();

function DoMatch() {
	global $ajax;
	if (isset($_GET['pattern']) && isset($_GET['subject'])) {
		if (isset($_GET['all']) && $_GET['all'])
			$ajax->Data->found = preg_match_all($_GET['pattern'], $_GET['subject'], $ajax->Data->matches);
		else
			$ajax->Data->found = preg_match($_GET['pattern'], $_GET['subject'], $ajax->Data->matches);
	} else
		$ajax->Fail('pattern and subject must be specified.');
}

function Replace() {
	global $ajax;
	if (isset($_GET['pattern']) && isset($_GET['replacement']) && isset($_GET['subject'])) {
		$ajax->Data->replacedResult = preg_replace($_GET['pattern'], $_GET['replacement'], $_GET['subject']);
	} else
		$ajax->Fail('pattern, replacement, and subject must be specified.');
}
