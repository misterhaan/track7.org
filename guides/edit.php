<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';

if(!$user->IsAdmin()) {
	// this page is only for admin, so give an ajax error or try to view the guide
	if(isset($_GET['ajax'])) {
		$ajax = new t7ajax();
		$ajax->Fail('you don’t have the rights to do that.  you might need to log in again.');
		$ajax->Send();
		die;
	}
	if(isset($_GET['url']))
		if(isset($_GET['tag']))
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $_GET['tag'] . '/' . $_GET['url']));
		else
			header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/' . $_GET['url']));
	else
		header('Location: ' . t7format::FullUrl(dirname($_SERVER['PHP_SELF']) . '/'));
	die;
}

if(isset($_GET['ajax'])) {
	$ajax = new t7ajax();
	switch($_GET['ajax']) {
		case 'save':       Save();          break;
		case 'checktitle': CheckTitleGet(); break;
		default:
			$ajax->Fail('unknown function name.  supported function names are: save, checktitle.');
			break;
	}
	$ajax->Send();
	die;
}

$id = isset($_GET['id']) ? +$_GET['id'] : false;
$html = new t7html(['vue' => true]);
$html->Open(($id ? 'edit' : 'add') . ' guide');
?>
			<h1><?=$id ? 'edit' : 'add'; ?> guide</h1>
			<form id=editguide<?php if($id) echo ' data-id="' . $id . '"'; ?> v-on:submit.prevent=Save>
				<label title="title of the guide (for display)">
					<span class=label>title:</span>
					<span class=field><input id=title maxlength=128 required v-model=title v-on:change=ValidateDefaultUrl></span>
				</label>
				<label title="unique portion of guide url (alphanumeric with dots, dashes, and underscores)">
					<span class=label>url:</span>
					<span class=field><input id=url maxlength=32 pattern="[a-z0-9\-\._]+" v-model=url :placeholder=defaultUrl v-on:change=ValidateUrl></span>
				</label>
				<label class=multiline title="introduction to or summary of the guide (use markdown)">
					<span class=label>summary:</span>
					<span class=field><textarea required rows="" cols="" v-model=summary></textarea></span>
				</label>
				<label title="guide difficulty level">
					<span class=label>level:</span>
					<span class=field><select v-model=level><option>beginner</option><option>intermediate</option><option>advanced</option></select></span>
				</label>
<?php
$html->ShowTagsField('guide');
?>
				<fieldset v-for="(page, index) of pages">
					<legend>chapter {{index + 1}}</legend>
					<a class="action up" href="#moveup" title="move this chapter earlier" v-if=index v-on:click.prevent=MovePageUp(page)></a>
					<a class="action down" href="#movedown" title="move this chapter later" v-if="index < pages.length - 1" v-on:click.prevent=MovePageDown(page)></a>
					<a class="action del" href="#del" title="remove this chapter" v-on:click.prevent=RemovePage(page)></a>
					<label :title="'heading for chapter ' + (index + 1)">
						<span class=label>heading:</span>
						<span class=field><input maxlength=128 required v-model=page.heading></span>
					</label>
					<label class=multiline :title="'content for chapter ' + (index + 1) + ' (use markdown)'">
						<span class=label>content:</span>
						<span class=field><textarea required rows="" cols="" v-model=page.markdown></textarea></span>
					</label>
				</fieldset>
				<!--/ko-->
				<label>
					<span class=label></span>
					<span class=field><a class="action new" href="#addpage" title="add a new blank chapter to the end" v-on:click.prevent=AddPage>add chapter</a></span>
				</label>
				<label v-if="status == 'published'">
					<span class=label></span>
					<span class=field><span><input type=checkbox v-model=correctionsOnly> this edit is formatting / spelling / grammar only</span></span>
				</label>
				<button class=save>save</button>
			</form>
<?php
$html->Close();

/**
 * Save guide.
 */
function Save() {
	global $ajax, $db;
	if(isset($_POST['guidejson'])) {
		$guide = json_decode($_POST['guidejson']);
		if(!trim($guide->url))
			$guide->url = t7format::NameToUrl($guide->title);
		if(CheckTitle($guide->title, $guide->id))
			if(CheckUrl($guide->url, $guide->id)) {
				$ajax->Data->url = $guide->url;
				$q = 'guides set url=\'' . $db->escape_string($guide->url)
					. '\', title=\'' . $db->escape_string(trim($guide->title))
					. '\', summary_markdown=\'' . $db->escape_string(trim($guide->summary))
					. '\', summary=\'' . $db->escape_string(t7format::Markdown(trim($guide->summary)))
					. '\', level=\'' . $db->escape_string($guide->level) . '\'';
				if($guide->status != 'published' || !$guide->correctionsOnly)
					$q .= ', updated=\'' . +time() . '\'';
				$q = $guide->id ? 'update ' . $q . ' where id=\'' . +$guide->id . '\' limit 1'
					: 'insert into ' . $q . ', author=1';
				if($db->real_query($q)) {
					if(!$guide->id)
						$guide->id = $db->insert_id;
				} else
					$ajax->Fail('database error saving guide data.');
				foreach($guide->pages as $page) {
					$q = 'guide_pages set number=\'' . +$page->number . '\', heading=\'' . $db->escape_string(trim($page->heading)) . '\', markdown=\'' . $db->escape_string(trim($page->markdown)) . '\', html=\'' . $db->escape_string(t7format::Markdown(trim($page->markdown))) . '\'';
					$q = $page->id ? 'update ' . $q . ' where id=\'' . +$page->id . '\' limit 1' : 'insert into ' . $q . ', guide=\'' . +$guide->id . '\'';
					if(!$db->real_query($q))
						$ajax->Fail('database error saving page ' . +$page->number);
				}
				if(count($guide->deletedPageIDs))
					$db->real_query('delete from guide_pages where id in (' . implode(',', $guide->deletedPageIDs) . ')');
				$addtags = array_diff($guide->taglist, $guide->originalTaglist);
				if(count($addtags)) {
					$qat = $db->prepare('insert into guide_tags (name) values (?) on duplicate key update id=id');
					$qat->bind_param('s', $name);
					$qlt = $db->prepare('insert into guide_taglinks set guide=\'' . +$guide->id . '\', tag=(select id from guide_tags where name=? limit 1)');
					$qlt->bind_param('s', $name);
					foreach($addtags as $name) {
						if(!$qat->execute())
							$ajax->Fail('error adding tag:  ' . $qat->error);
						if(!$qlt->execute())
							$ajax->Fail('error linking tag:  ' . $qlt->error);
					}
					$qat->close();
					$qlt->close();
				}
				$deltags = array_diff($guide->originalTaglist, $guide->taglist);
				if(count($deltags))
					$db->real_query('delete from guide_taglinks where guide=\'' . +$guide->id . '\' and tag in (select id from guide_tags where name in (\'' . implode('\', \'', $deltags) . '\'))');
				if($guide->status == 'published') {
					$tags = array_merge($addtags, $deltags);
					if(count($tags))
						if(!$db->real_query('update guide_tags set count=(select count(1) as count from guide_taglinks as tl left join guides as g on g.id=tl.guide where g.status=\'published\' and tl.tag=guide_tags.id group by tl.tag), lastused=(select max(g.updated) as lastused from guide_taglinks as tl left join guides as g on g.id=tl.guide where g.status=\'published\' and tl.tag=guide_tags.id group by tl.tag) where name in (\'' . implode('\', \'', $tags) . '\')'))
							$ajax->Fail('error updating tag stats:  ' . $db->error);
				}
			}  // else URL isn't valid, but it already added a failure message
		// else title is already used, but it already added a failure message
	} else
		$ajax->Fail('missing required parameter guidejson.');
}

/**
 * Verifies a title has not already been used.
 * @param string $title Title to check.
 * @param integer $id Guide ID if editing a guide.
 * @return boolean Whether the title is available.
 */
function CheckTitle($title, $id = 0) {
	global $ajax, $db;
	if(trim($title))
		if($unique = $db->query('select id from guides where title=\'' . $db->escape_string($title) . '\' and id!=\'' . +$id . '\' limit 1'))
			if($unique = $unique->fetch_object())
				$ajax->Fail('title already in use.');
			else
				return true;
		else
			$ajax->Fail('error checking if title is in use:  ' . $db->error);
	else
		$ajax->Fail('title is required.');
	return false;
}

/**
 * Verifies a URL uses the allowed characters and isn't already in use.
 * @param string $url URL to check.
 * @param integer $id Guide ID if editing a guide.
 * @return boolean Whether the URL is valid.
 */
function CheckUrl($url, $id = 0) {
	global $ajax, $db;
	if(t7format::ValidUrlPiece($url))
		if($unique = $db->query('select title from guides where url=\'' . $db->escape_string($url) . '\' and id!=\'' . +$id . '\' limit 1'))
			if($unique = $unique->fetch_object())
				$ajax->Fail('url already in use by “' . $unique->title . '.”');
			else
				return true;
		else
			$ajax->Fail('database error checking for duplicate url:  ' . $db->error);
	else
		$ajax->Fail('url must be at least three characters and can only contain letters, digits, periods, dashes, and underscores.');
	return false;
}

/**
 * Check the title as an ajax call.
 */
function CheckTitleGet() {
	global $ajax;
	$ajax->Data->title = $_GET['title'];
	$ajax->Data->id = $_GET['id'];
	CheckTitle(trim($_GET['title']), $_GET['id']);
}
