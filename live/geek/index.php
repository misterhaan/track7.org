<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('geek', '<img alt="geek" src="geeksword.png" style="width: 275px; height: 200px;" />', '', '', array('guides', 'golf', 'games', 'hardware', 'stats'), array(null, null, null, 'hw'));

  $page->Heading('guides', 'guides');
?>
      <p>
        i’ve been using (and programming) computers for 15 to 20 years now, and
        by doing so i’ve managed to learn quite a bit.&nbsp; it also didn’t hurt
        to take courses in computer architecture.&nbsp; anyway, here you will
        find some (hopefully useful) tips that i’ll write down whenever
        something seems widely useful enough.&nbsp; along with that, other
        track7 users can submit their own guides.
      </p>
      <ul><li><a href="guides/">guides</a></li></ul>

<?
  $page->Heading('disc golf', 'golf');
?>
      <p>
        the disc golf section is inspired by
        <a href="http://www.discgolfstats.com/" title="disc golf course statistics">folfscores.com</a>,
        which can track scores for you but doesn’t do everything i was hoping
        for.&nbsp; beyond just scores, here you can also enter which discs you
        have in your arsenal.&nbsp; you will need a <a href="/user/">user account</a>
        before you can save any information, though anyone can request a disc or
        course to be added.
      </p>
      <ul>
        <li><a title="view a summary of the disc golf information stored on track7" href="discgolf/">disc golf</a><ul>
          <li><a title="view player information and statistics" href="discgolf/players.php">players</a></li>
          <li><a title="view / add disc golf courses" href="discgolf/courses.php">courses</a></li>
          <li><a title="view / add discs" href="discgolf/discs.php">discs</a></li>
        </ul></li>
      </ul>

<?
  $page->Heading('games', 'games');
?>
      <p>
        i’ve posted a thing or two about some of the games i’ve played.&nbsp;
        currently you can look at my <em>darkstone</em> and <em>diablo ii</em>
        characters (or post your own), and read my reviews on fan missions for
        <em>thief:&nbsp; the dark project</em>.
      </p>
      <ul>
        <li><a href="rpg/">role-playing games</a><ul>
          <li>darkstone<ul>
            <li><a href="rpg/?game=2">characters</a></li>
          </ul></li>
          <li>diablo ii (lord of destruction)<ul>
            <li><a href="rpg/?game=4">characters</a></li>
          </ul></li>
        </ul></li>
        <li>thief:&nbsp; the dark project<ul>
          <li><a title="my reviews of fan missions (with download links)" href="thief-tdp.php">fan missions</a></li>
        </ul></li>
      </ul>

<?
  $page->Heading('hardware', 'hw');
?>
      <p>
        i probably have too many computer parts.&nbsp; the following is a list
        of the computers i have running and what i’m using them for . . . click
        on them for detailed specs.
      </p>
      <ul>
        <li><a href="computers.php">computers</a><ul>
          <li><a href="computers.php#hecubus">hecubus</a> - server (file / ftp / http / pvr)</li>
          <li><a href="computers.php#tesla">tesla</a> - main workstation</li>
          <li><a href="computers.php#galileo">galileo</a> - mobile / secondary workstation</li>
        </ul></li>
        <li>network</li>
        <li>audio / visual<ul>
          <li>living room</li>
          <li>office</li>
        </ul></li>
      </ul>

<?
  $page->Heading('statistics', 'stats');
?>
      <p>
        <a href="http://shorturl.com/">shorturl.com</a> provides me with
        statistics on the people who access track7 through the url
        http://track7.vze.com.&nbsp; in august 2003 i wrote my own statistics
        system, so everything except the date-based information from before then
        comes from my system.&nbsp; i also reset all but the date-based
        information after moving to track7.org.&nbsp; statistics automatically
        update every night.
      </p>
      <ul><li><a href="hits/">statistics</a></li></ul>

<?
  $page->End();
?>
