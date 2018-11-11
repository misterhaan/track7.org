<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'analyze': Analyze(); break;
	}
	$ajax->Send();
	die;
}

$html = new t7html(['vue' => true]);
$html->Open('timestamp converter');
?>
			<h1>timestamp converter</h1>

			<form id=timestamps v-on:submit.prevent=Analyze>
				<fieldset class=selectafield>
					<div>
						<label class=label><input type=radio name=inputtype value=timestamp v-model=inputtype>timestamp:</label>
						<label class=field><input type=number v-model=timestamp maxlength=10 step=1 min=0 max=4294967295></label>
					</div>
					<div>
						<label class=label><input type=radio name=inputtype value=formatted v-model=inputtype>formatted:</label>
						<label class=field><input v-model=formatted maxlength=64></label>
					</div>
				</fieldset>
				<fieldset class=checkboxes>
					<legend>time zone:</legend>
					<span class=field>
						<label class=checkbox>
							<input type=radio name=zone value=local v-model=zone>
							local
						</label>
						<label class=checkbox>
							<input type=radio name=zone value=utc v-model=zone>
							utc
						</label>
					</span>
				</fieldset>
				<button>analyze</button>
			</form>

			<section v-if=hasresults>
				<h2>results</h2>
				<dl id=timestampdata>
					<dt>timestamp</dt><dd>{{resulttimestamp}}</dd>
					<dt>smart</dt><dd v-html=smart></dd>
					<dt>ago</dt><dd>{{ago}}</dd>
					<dt>year</dt><dd>{{year}}</dd>
					<dt>month</dt><dd>{{month}}</dd>
					<dt>day</dt><dd>{{day}}</dd>
					<dt>weekday</dt><dd>{{weekday}}</dd>
					<dt>time</dt><dd>{{time}}</dd>
				</dl>
			</section>
<?php
$html->Close();

function Analyze() {
	global $ajax;
	if(isset($_GET['type']) && ($_GET['type'] == 'timestamp' || $_GET['type'] == 'formatted'))
		if(isset($_GET[$_GET['type']]) && $timestamp = trim($_GET[$_GET['type']]))
			if(isset($_GET['zone']) && ($_GET['zone'] == 'local' || $_GET['zone'] == 'utc')) {
				if($_GET['type'] == 'formatted')
					if($_GET['zone'] == 'local')
						$timestamp = t7format::LocalStrtotime($timestamp);
					else {
						$zone = date_default_timezone_get();
						date_default_timezone_set('UTC');
						$timestamp = strtotime($timestamp);
						date_default_timezone_set($zone);
					}
				else
					$timestamp = +$timestamp;
				$ajax->Data->timestamp = $timestamp;
				$ajax->Data->smart = t7format::SmartDate($timestamp);
				$ajax->Data->ago = t7format::HowLongAgo($timestamp);
				$ajax->Data->year = t7format::LocalDate('Y', $timestamp);
				$ajax->Data->month = strtolower(t7format::LocalDate('F (n)', $timestamp));
				$ajax->Data->day = t7format::LocalDate('jS', $timestamp);
				$ajax->Data->weekday = strtolower(t7format::LocalDate('l', $timestamp));
				$ajax->Data->time = t7format::LocalDate('g:i:s a', $timestamp);
			} else
				$ajax->Fail('time zone missing or invalid.');
		else
			$ajax->Fail('input value missing or blank.');
	else
		$ajax->Fail('input type not specified or invalid.');
}
