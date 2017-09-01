<?php
define('TR_EXT_PROFILES', 12);
define('STEP_RENAME_PROF_COL', 1);
define('STEP_ADD_PROF_COL', 2);
define('STEP_INDEX_PROF_COL', 3);
define('STEP_COPY_PROFILES', 4);
define('STEP_LINK_PROFILES', 5);
define('STEP_DEL_PROF_COL', 6);
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/t7.php';
$html = new t7html([]);
$html->Open('external profile migration');
?>
			<h1>external profile migration</h1>
<?php
if(isset($_GET['dostep']))
	switch($_GET['dostep']) {
		case 'renameprofcol':
			if($db->real_query('alter table login_google change profile profileurl varchar(64) not null default \'\''))
				if($db->real_query('alter table login_twitter change profile profileurl varchar(64) not null default \'\''))
					if($db->real_query('alter table login_facebook change profile profileurl varchar(64) not null default \'\''))
						$db->real_query('update transition_status set stepnum=' . STEP_RENAME_PROF_COL . ', status=\'profile url column renamed\' where id=' . TR_EXT_PROFILES . ' and stepnum<' . STEP_RENAME_PROF_COL);
					else
						echo '<pre><code>error renaming facebook profile column:' . "\n" . $db->error . '</code></pre>';
				else
					echo '<pre><code>error renaming twitter profile column:' . "\n" . $db->error . '</code></pre>';
			else
				echo '<pre><code>error renaming google profile column:' . "\n" . $db->error . '</code></pre>';
			break;
		case 'addprofcol':
			if($db->real_query('alter table login_google add profile mediumint unsigned'))
				if($db->real_query('alter table login_twitter add profile mediumint unsigned'))
					if($db->real_query('alter table login_facebook add profile mediumint unsigned'))
						$db->real_query('update transition_status set stepnum=' . STEP_ADD_PROF_COL . ', status=\'new profile url column added\' where id=' . TR_EXT_PROFILES . ' and stepnum<' . STEP_ADD_PROF_COL);
					else
						echo '<pre><code>error adding facebook profile column:' . "\n" . $db->error . '</code></pre>';
				else
					echo '<pre><code>error adding twitter profile column:' . "\n" . $db->error . '</code></pre>';
			else
				echo '<pre><code>error adding google profile column:' . "\n" . $db->error . '</code></pre>';
			break;
		case 'indexprofcol':
			if($db->real_query('alter table login_google add foreign key(profile) references external_profiles(id)'))
				if($db->real_query('alter table login_twitter add foreign key(profile) references external_profiles(id)'))
					if($db->real_query('alter table login_facebook add foreign key(profile) references external_profiles(id)'))
						$db->real_query('update transition_status set stepnum=' . STEP_INDEX_PROF_COL . ', status=\'foreign key added\' where id=' . TR_EXT_PROFILES . ' and stepnum<' . STEP_INDEX_PROF_COL);
					else
						echo '<pre><code>error adding facebook profile foreign key:' . "\n" . $db->error . '</code></pre>';
				else
					echo '<pre><code>error adding twitter profile foreign key:' . "\n" . $db->error . '</code></pre>';
			else
				echo '<pre><code>error adding google profile foreign key:' . "\n" . $db->error . '</code></pre>';
			break;
		case 'copyprofiles':
			if($db->real_query('insert into external_profiles (url) select profileurl from login_google'))
				if($db->real_query('insert into external_profiles (url) select profileurl from login_twitter'))
					if($db->real_query('insert into external_profiles (url, avatar) select profileurl, concat(\'http://graph.facebook.com/v2.10/\', extid, \'/picture\') from login_facebook'))
						$db->real_query('update transition_status set stepnum=' . STEP_COPY_PROFILES . ', status=\'profile urls copied\' where id=' . TR_EXT_PROFILES . ' and stepnum<' . STEP_COPY_PROFILES);
					else
						echo '<pre><code>error copying facebook profile urls:' . "\n" . $db->error . '</code></pre>';
				else
					echo '<pre><code>error copying twitter profile urls:' . "\n" . $db->error . '</code></pre>';
			else
				echo '<pre><code>error copying google profile urls:' . "\n" . $db->error . '</code></pre>';
			break;
		case 'linkprofiles':
			if($db->real_query('update login_google set profile=(select id from external_profiles where url=profileurl limit 1)'))
				if($db->real_query('update login_twitter set profile=(select id from external_profiles where url=profileurl limit 1)'))
					if($db->real_query('update login_facebook set profile=(select id from external_profiles where url=profileurl limit 1)'))
						$db->real_query('update transition_status set stepnum=' . STEP_LINK_PROFILES . ', status=\'profiles linked\' where id=' . TR_EXT_PROFILES . ' and stepnum<' . STEP_LINK_PROFILES);
					else
						echo '<pre><code>error linking facebook profiles:' . "\n" . $db->error . '</code></pre>';
				else
					echo '<pre><code>error linking twitter profiles:' . "\n" . $db->error . '</code></pre>';
			else
				echo '<pre><code>error linking google profiles:' . "\n" . $db->error . '</code></pre>';
			break;
		case 'delprofcol':
			if($db->real_query('alter table login_google drop profileurl'))
				if($db->real_query('alter table login_twitter drop profileurl'))
					if($db->real_query('alter table login_facebook drop profileurl'))
						$db->real_query('update transition_status set stepnum=' . STEP_DEL_PROF_COL . ', status=\'old profile url columns dropped\' where id=' . TR_EXT_PROFILES . ' and stepnum<' . STEP_DEL_PROF_COL);
					else
						echo '<pre><code>error dropping facebook profile url column:' . "\n" . $db->error . '</code></pre>';
				else
					echo '<pre><code>error dropping twitter profile url column:' . "\n" . $db->error . '</code></pre>';
			else
				echo '<pre><code>error dropping google profile url column:' . "\n" . $db->error . '</code></pre>';
			break;
	}

if($status = $db->query('select stepnum, status from transition_status where id=' . TR_EXT_PROFILES))
	$status = $status->fetch_object();
?>
			<h2>modify login tables</h2>
<?php
if($status->stepnum < STEP_RENAME_PROF_COL) {
?>
			<p>
				the google, twitter, and facebook login tables have a profile column
				containing the url to the profile.  that information will move to the
				external_profiles table (make sure it’s been created!) and a new profile
				column will link to extenal_profiles.  the first step is to rename the
				existing column.
			</p>
			<p class=calltoaction><a class="edit action" href="?dostep=renameprofcol">rename profile columns!</a></p>
<?php
} elseif($status->stepnum < STEP_ADD_PROF_COL) {
?>
			<p>
				now that the old profile columns have a new name, there’s room to add
				the new profile column.
			</p>
			<p class=calltoaction><a class="add action" href="?dostep=addprofcol">add new profile columns!</a></p>
<?php
} elseif($status->stepnum < STEP_INDEX_PROF_COL) {
?>
			<p>
				now that the new profile columns have been created, they need to be
				linked to the external_profiles table.
			</p>
			<p class=calltoaction><a class="index action" href="?dostep=indexprofcol">index the profile columns!</a></p>
<?php
} else {
?>
			<p>
				login tables have been modified to link to the external_profiles table.
			</p>

			<h2>create external profiles</h2>
<?php
	if($status->stepnum < STEP_COPY_PROFILES) {
?>
			<p>
				now that the login tables are ready, we can copy the profile urls to the
				external_profiles table.
			</p>
			<p class=calltoaction><a class="copy action" href="?dostep=copyprofiles">copy the profile urls!</a></p>
<?php
	} elseif($status->stepnum < STEP_LINK_PROFILES) {
?>
			<p>
				now that the profiles have been created, the logins can link to them.
			</p>
			<p class=calltoaction><a class="copy action" href="?dostep=linkprofiles">link profiles to logins!</a></p>
<?php
	} else {
?>
			<p>profiles have been created and linked.</p>

			<h2>remove old columns</h2>
<?php
		if($status->stepnum < STEP_DEL_PROF_COL) {
?>
			<p>
				now that profile urls have been migrated, the old columns can be removed
				from the login tables.
			</p>
			<p class=calltoaction><a class="copy action" href="?dostep=delprofcol">remove old columns!</a></p>
<?php
		} else {
?>
			<p>
				old profile url columns have been removed.  external profile migration
				complete!
			</p>
<?php
		}
	}
}
$html->Close();
