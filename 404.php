<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

$html = new t7html([]);
$html->Open('404 not found');
?>
			<h1>404 bad guess</h1>

			<p>
				sorry, that’s not a thing.  if you followed a link and expected to find
				a thing, you should tell the owner of the link there’s nothing here so
				they can fix it.  if the link was from track7,
				<a href="/user/messages.php#!to=misterhaan">tell misterhaan</a>.  if
				you were just making stuff up, you might do better with this google
				search of everything on track7:
			</p>

			<script>
				(function() {
					$("main").append("<gcse:searchbox-only></gcse:searchbox-only>");
					var cx = '009301861402372195375:8j9q7yqytle';
					var gcse = document.createElement('script');
					gcse.type = 'text/javascript';
					gcse.async = true;
					gcse.src = (document.location.protocol == 'https:' ? 'https:' : 'http:') + '//cse.google.com/cse.js?cx=' + cx;
					var s = document.getElementsByTagName('script')[0];
					s.parentNode.insertBefore(gcse, s);
				})();
			</script>
<?php
$html->Close();
