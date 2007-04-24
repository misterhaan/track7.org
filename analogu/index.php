<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('the analog underground', null, '', '', array('php', '.net', 'vb', 'ti8x', 'games'), array('', 'net', '', '', 'mzx'));
?>
      <p>
        the analog underground is the name of my software 'company.'&nbsp; it's
        just me, and i don't make any money at it since everything's free (and
        nobody has offered to donate, so i don't have any sort of donation
        system set up), so i don't really call it a company.&nbsp; i've been
        programming since i was too young to remember, and track7 hosts some of
        the results that might be useful to you.
      </p>
      <p>
        most files are available in zip format.&nbsp; windows xp can open these
        files itself, but if you have an older version you might need something
        else, like <a href="http://www.7-zip.org/" title="download 7-zip">7-zip</a>
        (free!) or 
        <a href="http://www.winzip.com/" title="download winzip">winzip</a>
        (free evaluation).&nbsp; actually you might want one of these anyway.&nbsp;
        i use 7-zip to create my zip files because it makes smaller files than
        winzip.&nbsp; it also supports the 7z format which makes files smaller
        yet!&nbsp; i use zip files here instead of 7z files so that you aren't
        forced to use 7-zip.
      </p>

<?
  $page->Heading('php scripts', 'php');
?>
      <p>
        i have taken some parts of track7 and packaged them up for anyone
        interested in modifying the scripts to work for their own site.&nbsp;
        some of the scripts available here are no longer being used on track7,
        but everything at least was used at some time.&nbsp; note that
        practically every one of them will require some modification before it
        will work for a different site -- read the directions after choosing a
        script to find out see what needs to be changed.
      </p>
      <p>
        please note that the directions listed here are purposely not very
        complete or specific:&nbsp; i do not intend for any of this to
        automatically work if you just upload it to your site.&nbsp; in other
        words, <em>you should have at least some php experience</em> if you want
        to use these scripts.
      </p>
      <ul>
        <li><a href="scripts/" title="php and mysql scripts">php script list</a><ul>
<?
  $scripts = 'select name, title from auscripts order by title';
  if($scripts = $db->Get($scripts, 'error looking up available scripts', 'no scripts found')) {
    while($script = $scripts->NextRecord()) {
?>
          <li><a href="scripts/<?=$script->name; ?>"><?=$script->title; ?></a></li>
<?
    }
  }
?>
        </ul></li>
      </ul>

<?
  $page->Heading('.net applications', 'net');
?>
      <p>
        though my programs written in visual basic (see below) were working and
        doing most of what i wanted, it seemed it was time to grow up a little
        and move away from programming in vb.&nbsp; because of that, i am
        currently re-writing my visual basic applications using the .net
        framework in c#.&nbsp; supposedly using .net should make it easier for
        these applications to run on something other than windows, but i haven't
        tried it.
      </p>
      <ul>
        <li><a href="net/">c# .net projects</a></li>
      </ul>

<?
  $page->Heading('visual basic applications', 'vb');
?>
      <p>
        basic was the first language i ever learned.&nbsp; specifically, it
        was ti-99/4a basic, which i taught myself with the help of sample
        programs from 3-2-1 contact magazine.&nbsp; i stuck with the basic
        programming thing and now find it most convenient to write visual basic
        applications to do things for me--usually they generate html.&nbsp; i've
        also started using activex to split things up a little better.&nbsp;
        feel free to play around with my source code (it's available here), but
        if you haven't installed the application you will need to download and
        register the analog underground common controls.
      </p>
      <ul>
        <li><a href="vb.php" title="visual basic programs and source code">vb programs</a></li>
      </ul>

<?
  $page->Heading('calculator programs', 'ti8x');
?>
      <p>
        another form of basic i used to work with is ti85/86 basic.&nbsp; this
        helped to pass the time during less interesting classes, and also helped
        me out with some of the work for a few classes.&nbsp; i should still have
        a couple more besides what's already here, and hopefully i'll get them up
        soon!&nbsp; all of these programs will work on the ti86, and all but the
        newest ones will work on the ti85.&nbsp; buy the cable from texas
        instruments or make your own--whatever program you use to transfer these
        programs to your calculator should work just fine.
      </p>
      <ul>
        <li><a href="ti8x.php" title="basic programs for ti85 and ti86 graphing calculators">ti8x programs</a></li>
      </ul>

<?
  $page->Heading('gameworlds', 'mzx');
?>
      <p>
        every geeky kid wants to make his or her own games, and with zzt (and
        later megazeux) i was able to do just that.&nbsp; i realized after
        weirdland ii that my games sucked, and decided to make them cooler from
        then on.&nbsp; unfortunately all i got is three ideas and two animated
        beginning sequences.&nbsp; anyway the three sucky games are here, as
        well as some info on three (hopefully) non-sucky games, which i may
        eventually even do some more on.&nbsp; keep in mind that i am no longer
        a geeky kid -- instead i have become a geek 'adult' and seem to have
        lost the motivation to work on these.&nbsp; let me know if you care and
        that might be enough to get me going again . . .
      </p>
      <ul>
        <li><a href="gameworlds.php" title="gameworlds written for megazeux and zzt">gameworlds</a></li>
      </ul>

<?
  $page->End();
?>
