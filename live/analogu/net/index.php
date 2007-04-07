<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('c# .net projects - the analog underground', 'c# .net projects', '', '', array('phd', 'meat', 'aulib'));
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
        <a href="http://www.microsoft.com/downloads/details.aspx?FamilyID=262d25e3-f589-4842-8157-034d1e7cf3a3">.net framework 1.1</a>
        which you can get for free from microsoft if you don't already have it
        (windows xp service pack 2 includes it, and so does windows 2003).&nbsp;
        in order to build the projects from source you will need the .net
        framework sdk (also free from microsoft), and visual studio .net might
        be of some help as well (but that's sure not free).
      </p>

<?
  $page->Heading('photo album designer (phd)', 'phd');
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
        <li><a href="/files/analogu/net/meat/meat-v0.1.0.msi">download latest meat installer</a></li>
        <li><a href="meat/">meat project page</a></li>
        <li><a href="meat/manual/">meat documentation</a></li>
      </ul>

<?
  $page->Heading('shared libraries', 'aulib');
?>
      <img src="aulib/dll.png" alt="" class="icon" />
      <p class="iconned">
        shared libraries used by most analog underground applications (including
        the two above).&nbsp; it's not an application itself, and is pretty much
        useless to anyone who isn't a programmer.&nbsp; programmers may be
        interested to know that the installer places auIO and auComCtl into the
        .net gac, which you then should be able to use in your own .net projects
        (though i couldn't seem to get that part to work).
      </p>

      <ul>
        <li><a href="/files/analogu/net/aulib/aulib-v3.0.0.msi">download latest aulib installer</a></li>
        <li><a href="aulib/">aulib project page</a></li>
      </ul>

<?
  $page->End();
?>
