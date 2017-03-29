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

$html = new t7html(['ko' => true]);
$html->Open('timestamp converter');
?>
			<h1>timestamp converter</h1>
			<p></p>

			<form id=timestamps data-bind="submit: Analyze">
				<fieldset class=selectafield>
					<div>
						<label class=label><input type=radio name=inputtype value=timestamp data-bind="checked: inputtype">timestamp:</label>
						<label class=field><input type=number data-bind="value: timestamp" maxlength=10 step=1 min=0 max=4294967295></label>
					</div>
					<div>
						<label class=label><input type=radio name=inputtype value=formatted data-bind="checked: inputtype">formatted:</label>
						<label class=field><input data-bind="value: formatted" maxlength=64></label>
					</div>
				</fieldset>
				<fieldset class=checkboxes>
					<legend>time zone:</legend>
					<span class=field>
						<label class=checkbox>
							<input type=radio name=zone value=local data-bind="checked: zone">
							local
						</label>
						<label class=checkbox>
							<input type=radio name=zone value=utc data-bind="checked: zone">
							utc
						</label>
					</span>
				</fieldset>
				<button>analyze</button>
			</form>

			<section data-bind="if: hasresults">
				<h2>results</h2>
				<dl id=timestampdata>
					<dt>timestamp</dt><dd data-bind="text: resulttimestamp"></dd>
					<dt>smart</dt><dd data-bind="html: smart"></dd>
					<dt>ago</dt><dd data-bind="text: ago"></dd>
					<dt>year</dt><dd data-bind="text: year"></dd>
					<dt>month</dt><dd data-bind="text: month"></dd>
					<dt>day</dt><dd data-bind="text: day"></dd>
					<dt>weekday</dt><dd data-bind="text: weekday"></dd>
					<dt>time</dt><dd data-bind="text: time"></dd>
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
