<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
if (!$user->IsAdmin()) {
	header('HTTP/1.0 404 Not Found');
	require_once $_SERVER['DOCUMENT_ROOT'] . '/404.php';
	die;
}

if (isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch ($_GET['ajax']) {
		case 'pull':
			chdir($_SERVER['DOCUMENT_ROOT']);
			exec('git pull', $output, $retcode);
			$ajax->Data->output = implode("\n", $output);
			$ajax->Data->retcode = $retcode;
			$ajax->Data->cachedel = [];
			foreach ($output as $line) {
				$parts = explode('|', $line);
				if (count($parts) == 2) {
					$file = trim($parts[0]);
					if (substr($file, -3) == '.js' || in_array(substr($file, -4), ['.css', '.png', '.gif', '.jpg', '.xml', '.txt']) || substr($file, -5) == '.woff')
						$ajax->Data->cachedel[] = t7format::FullUrl('/' . $file);
				}
			}
			if (count($ajax->Data->cachedel)) {
				$data = new stdClass();
				$data->files = $ajax->Data->cachedel;
				$c = curl_init();
				curl_setopt($c, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/zones/' . t7keysCloudflare::ID . '/purge_cache');
				curl_setopt($c, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($c, CURLOPT_USERAGENT, 'track7.org git pull');
				curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($c, CURLOPT_TIMEOUT, 30);
				curl_setopt($c, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . t7keysCloudflare::TOKEN, 'Content-Type: application/json']);
				curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));
				$ajax->Data->cloudflare = new stdClass();
				$ajax->Data->cloudflare->text = curl_exec($c);
				$ajax->Data->cloudflare->code = curl_getinfo($c, CURLINFO_HTTP_CODE);
				curl_close($c);
			}
			break;
		default:
			$ajax->Fail('unknown function name.  supported function names are: pull.');
			break;
	}
	$ajax->Send();
	die;
}

$html = new t7html([]);
$html->Open('update from github');
?>
<h1>update track7 from github</h1>

<nav class=actions><a class=get href="#pull">update</a></nav>
<?php
$html->Close();
