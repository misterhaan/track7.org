<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('c# .net projects - the analog underground', 'c# .net projects', '', '', array('gamesink', 'mo', 'meat', 'aulib'));
?>
      <p>
        i’ve long since left <a href="../vb.php">visual basic</a> behind in
        favor of c# and the latest version of .net for windows programming.&nbsp;
        since then i’ve only released a single working application but started
        on three others, hoping to have another ready for release soon.
      </p>
      <p>
        to run an application that uses .net, the correct version of the .net
        framework must be installed.&nbsp; these can be downloaded for free from
        microsoft.&nbsp; currently, the latest version is 4 and is available
        here:&nbsp; <a href="http://www.microsoft.com/download/en/details.aspx?id=17851">microsoft .net framework 4 web installer</a>.&nbsp;
        to work with the source code the minimum is a text editor and the
        correct version of the .net sdk, with visual c# express or visual
        studio (not free) being more convenient.&nbsp; any of those will install
        the .net framework so it does not need to be installed separately.
      </p>
      <p>
        msi installer packages are built using windows installer xml (wix).&nbsp;
        just run the msi file to install.&nbsp; to build the installer from
        source, the <a href="http://wix.sourceforge.net/">wix toolset</a> is
        needed.&nbsp; some wix code available here may not be compatible with
        the latest wix toolset. 
      </p>
      <p>
        all source code is stored in a subversion repository at <a href="http://svn.track7.org/">svn.track7.org</a>.&nbsp;
        the easiest way to retrieve it is to install <a href="http://tortoisesvn.net/downloads.html">tortoisesvn</a>,
        create a directory, then right-click and do an svn checkout of the
        project’s dev url.&nbsp; alternately, code files can be browsed or even
        downloaded one-by-one through a browser.
      </p>

<?
  $page->Heading('gamesink', 'gamesink');
?>
      <p>
        gamesink is designed to simplify backing up and restoring game save
        files.&nbsp; it requires <a href="http://msdn.microsoft.com/en-us/netframework/aa569263">.net 4</a>
        to run or visual studio 2010 to develop.
      </p>
      <ul>
        <!--li><a href="/files/analogu/net/gamesink/gamesink-v0.0.0.msi">download latest gamesink installer</a></li-->
        <li><a href="http://wiki.track7.org/GameSink">gamesink documentation on auwiki</a></li>
        <li><a href="gamesink/">gamesink project page</a></li>
      </ul>

<?
  $page->Heading('memory organizer', 'mo');
?>
      <p>
        replaces the vb program known as pad -- coming &quot;soon!&quot;
      </p>

<?
  $page->Heading('movie enlister and tracker (meat)', 'meat');
?>
      <img src="meat/meat.png" alt="" class="icon" />
      <p class="iconned">
        movie enlister and tracker (meat) is designed to aid in managing large
        collections of digital movies and their backup status.&nbsp; it replaces
        movie information manager (mim) and should provide all the features that
        mim did.
      </p>
      <ul>
        <li><a href="/files/analogu/net/meat/meat-v0.2.0.msi">download latest meat installer</a></li>
        <li><a href="http://wiki.track7.org/Movie_Enlister_And_Tracker">meat documentation on auwiki</a></li>
        <li><a href="meat/">meat project page</a></li>
      </ul>

<?
  $page->Heading('shared libraries', 'aulib');
?>
      <img src="aulib/dll.png" alt="" class="icon" />
      <p class="iconned">
        shared libraries used by most analog underground applications (including
        the two above).&nbsp; it's not an application itself, and is pretty much
        useless to anyone who isn't a programmer.&nbsp; the two dll files are
        strongly-named which means they can be installed to the
        <abbr title="global assembly cache">gac</abbr> &mdash; in fact this is
        what happens when one of the applications using them gets installed.
      </p>

      <ul>
        <li><a href="/files/analogu/net/aulib/au.util-v3.1.0.zip">download latest aulib package</a></li>
        <li><a href="http://wiki.track7.org/Shared_Libraries">au.util documentation on auwiki</a></li>
        <li><a href="aulib/">au.util project page</a></li>
      </ul>

<?
  $page->End();
?>
