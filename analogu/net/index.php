<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('c# .net projects - the analog underground', 'c# .net projects', '', '', array('mo', 'meat', 'aulib'));
?>
      <p>
        though my programs written in <a href="../vb.php">visual basic</a> are
        working and doing most of what i want, it seemed it was time to grow up
        a little and move away from programming in vb.&nbsp; because of that, i
        am currently re-writing my visual basic applications using the .net
        framework in c#.&nbsp; supposedly using .net should make it easier for
        these applications to run on something other than windows, but i haven't
        tried it.
      </p>
      <p>
        i've also been learning windows installer xml (wix) while working on
        these projects, so they all come in msi form, along with an optional cab
        file containing the source code (even the wix source).&nbsp; i like the
        level of control i get with wix -- i can make much nicer installers with
        it than trying to use the confusing deployment tools that are included
        with visual studio.
      </p>
      <p>
        to run any of these projects, you will need to have the
        <a href="http://www.microsoft.com/downloads/details.aspx?familyid=0856EACB-4362-4B0D-8EDD-AAB15C5E04F5">.net framework 2.0</a>
        which you can get for free from microsoft if you don't already have it.&nbsp;
        in order to build the projects from source you will need the .net
        framework sdk (also free from microsoft), and visual studio 2005 might
        be of some help as well but that's sure not free.
      </p>

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
