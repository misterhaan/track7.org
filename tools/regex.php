<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'match': Match(); break;
		case 'replace': Replace(); break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['ko' => true]);
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
						<a href="http://php.net/preg_match" data-bind="visible: !match.all()">preg_match</a>
						<a href="http://php.net/preg_match_all" data-bind="visible: match.all">preg_match_all</a>
					</p>
					<form class=regextest data-bind="submit: Match">
						<label>
							<span class=label>pattern:</span>
							<span class=field><input data-bind="value: match.pattern"></span>
						</label>
						<label class=multiline>
							<span class=label>subject:</span>
							<span class=field><textarea data-bind="value: match.subject"></textarea></span>
						</label>
						<label class=checkbox>
							<span class=label></span>
							<span class="checkbox"><input type=checkbox data-bind="checked: match.all">find all matches</span>
						</label>
						<button>match</button>
					</form>
					<div data-bind="if: match.checked">
						<p data-bind="visible: match.matches().length < 1">no matches found</p>
						<ol class=matches data-bind="foreach: match.matches">
							<li><pre><code data-bind="text: $data"></code></pre></li>
						</ol>
					</div>
				</section>

				<section id=replace class=tabcontent>
					<h2>replace</h2>
					<p class=meta>
						using php function <a href="http://php.net/preg_replace">preg_replace</a>
					</p>
					<form class=regextest data-bind="submit: Replace">
						<label>
							<span class=label>pattern:</span>
							<span class=field><input data-bind="value: replace.pattern"></span>
						</label>
						<label>
							<span class=label>replace:</span>
							<span class=field><input data-bind="value: replace.replacement"></span>
						</label>
						<label class=multiline>
							<span class=label>subject:</span>
							<span class=field><textarea data-bind="value: replace.subject"></textarea></span>
						</label>
						<button>replace</button>
					</form>
					<pre data-bind="visible: replace.replaced"><code data-bind="text: replace.result"></code></pre>
				</section>
			</div>

<?php
$html->Close();

function Match() {
	global $ajax;
	if(isset($_GET['pattern']) && isset($_GET['subject'])) {
		if(isset($_GET['all']) && $_GET['all'])
			$ajax->Data->found = preg_match_all($_GET['pattern'], $_GET['subject'], $ajax->Data->matches);
		else
			$ajax->Data->found = preg_match($_GET['pattern'], $_GET['subject'], $ajax->Data->matches);
	} else
		$ajax->Fail('pattern and subject must be specified.');
}

function Replace() {
	global $ajax;
	if(isset($_GET['pattern']) && isset($_GET['replacement']) && isset($_GET['subject'])) {
		$ajax->Data->replacedResult = preg_replace($_GET['pattern'], $_GET['replacement'], $_GET['subject']);
	} else
		$ajax->Fail('pattern, replacement, and subject must be specified.');
}
