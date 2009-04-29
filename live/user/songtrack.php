<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $tracking = count($_GET);
  if(isset($_GET['_commentskip'])) $tracking--;
  if(isset($_GET['_commentshow'])) $tracking--;
  if($tracking < 1) {
    $page->Start('song tracking');
?>
      <p>
        track7 can keep track of what song you are listening to in
        <a href="http://www.winamp.com/">winamp 5</a>,
        <a href="http://www.oldversion.com/program.php?n=winamp" title="winamp on oldversion.com, since winamp.com no longer provides winamp 2">winamp 2</a>,
        <a href="http://audacious-media-player.org/">audacious</a>,
        <a href="http://www.xmms.org/">xmms</a>,
        or any other player that can request a web page every time a new song
        starts playing.&nbsp; instructions for winamp 5 or 2 setup are below,
        with instructions for audacious and xmms below that.&nbsp; to use
        another player, you will probably need to change the url listed in the
        winamp setup so that the stuff like %%URL_CURRENTSONGTITLE%% are
        recognized by your player.
      </p>

<?
    $page->Heading('winamp 5 or 2 setup');
?>
      <ol>
        <li>
          get the <a href="http://www.oddsock.org/tools/dosomething/">do something</a>
          plugin and install it.&nbsp; the download you want is the binary
          distribution (pimp installer).
        </li>
        <li>
          open up winamp, go to preferences and choose general purpose under
          plug-ins, then select do something in the list on the right.
        </li>
        <li>
          click the configure button to get the do something configuration
          window.
        </li>
        <li>
          make sure &ldquo;disable plugin&rdquo; is not checked.
        </li>
        <li>
          select &ldquo;submit a url&rdquo; in the actions dropdown.
        </li>
        <li>
          enter the following url in the url field that shows up after selecting
          &quot;submit a url&quot; -- make sure to replace &lt;SECRET&gt; with
          your actual password.
<?
    if($user->Valid) {
?>
          <samp>http://<?=$_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; ?>?user=<?=$user->Name; ?>&amp;pass=&lt;SECRET&gt;&amp;song=%%URL_CURRENTSONGTITLE%%&amp;artist=%%URL_CURRENTARTIST%%&amp;album=%%URL_CURRENTALBUM%%&amp;length=%%URL_CURRENTSONGLENGTH%%&amp;file=%%URL_CURRENTSONG%%</samp>
<?
    } else
      $page->Error('this feature is for registered users only, and you are not logged in.&nbsp; you will need to <a href="/user/login.php">log in</a> or <a href="/user/register.php">register</a> before you can use this feature.');
?>
        </li>
        <li>
          click the add button and then the ok button.
        </li>
        <li>
          play a song, then look at your profile!
        </li>
      </ol>

<?
    $page->Heading('audacious / xmms setup');
?>
      <ol>
        <li>
          both audacious and xmms come with a generic song change plugin, which
          basically runs anything.&nbsp; to give it something to run, copy the
          following code into a script, named something like songtrack.pl, and
          make sure it has execute permissions (chmod 700 songtrack.pl — no
          access to anyone but you since it will contain your password).
<?
    if($user->Valid) {
?>
          <samp>#!/usr/bin/perl -w<br />
$username = &quot;<?=$user->Name; ?>&quot;;<br />
$password = &quot;&lt;SECRET&gt;&quot;;<br />
$submiturl = &quot;http://<?=$_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']; ?>&quot;;<br />
<br />
use URI::Escape;<br />
use LWP::Simple;<br />
sub pushArg;<br />
<br />
pushArg('user', $username);<br />
pushArg('pass', $password);<br />
pushArg('length', int(shift(@ARGV) / 1000));<br />
pushArg('file', join(' ', @ARGV));<br />
get($submiturl . '?' . join('&amp;', @args));<br />
<br />
sub pushArg {<br />
&nbsp; push @args, uri_escape(shift @_) . '=' . uri_escape(shift @_);<br />
}<br /></samp>
<?
    } else
      $page->Error('this feature is for registered users only, and you are not logged in.&nbsp; you will need to <a href="/user/login.php">log in</a> or <a href="/user/register.php">register</a> before you can use this feature.');
?>
        </li>
        <li>
          be sure to replace &lt;SECRET&gt; in the file with your actual
          password.
        </li>
        <li>
          open up audacious / xmms, go to preferences.
        </li>
        <li>
          get to the song change preferences.  the path is different between
          audacious and xmms.
          <ul>
            <li>
              for audacious, select plugins, then check the box for song change,
              then choose song change on the left.
            </li>
            <li>
              for xmms, choose general plugins, then select song change from the
              list, then click the configure button to get the song change
              configuration window.
            </li>
          </ul>
        </li>
        <li>
          in the first command box (in xmms it’s under song change, not playlist
          end), enter <code>/path/to/songtrack.pl %l &quot;%s&quot;</code> and
          click close / ok.
        </li>
        <li>
          play a song, then look at your profile!
        </li>
      </ol>
<?
    $page->End();
    die;
  }

  if(!$_GET['user'])
    die('Cannot save song information -- no user information specified.');
  if(!$_GET['pass'])
    die('Cannot save song information -- no password specified.');
  if(!$_GET['song'] && !$_GET['file'])
    die('Cannot save song information -- no song title specified.');
  $u = 'select uid, pass from users where login=\'' . addslashes($_GET['user']) . '\'';
  $u = $db->GetRecord($u, '', '');
  if($u === false)
    die('User does not exist or error looking up user.');
  if(strlen($u->pass) == 32 && auUserTrack7::CheckOldPassword($_GET['pass'], $u->pass) || auUser::CheckPassword($_GET['pass'], $u->pass)) {
    $song = 'insert into usersongs (uid, instant, title, artist, album, length, filename) values (' . $u->uid . ', ' . time() . ', \'' . addslashes(htmlspecialchars($_GET['song'])) . '\', \'' . addslashes(htmlspecialchars($_GET['artist'])) . '\', \'' . addslashes(htmlspecialchars($_GET['album'])) . '\', \'' . addslashes(htmlspecialchars($_GET['length'])) . '\', \'' . addslashes(htmlspecialchars($_GET['file'])) . '\')';
    if(false !== $db->Put($song, 'err'))
      die('Song information saved.');
    else
      die('Cannot save song information -- error saving song information.');
  } else
    die('Cannot save song information -- invalid user and / or password.');
?>
