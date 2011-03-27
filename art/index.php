<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $page->Start('art', 'visual art', '', '', array('lego', 'sketch', 'comics', 'digital', 'cd', 'shop', 'skin', 'album'));
  $page->Heading('lego models', 'lego');
?>
      <img class="icon" src="lego/favicon.png" alt="" />
      <p class="iconned">
        i have a few lego models i built years ago that i’ve kept together and
        have displayed in my office at work.&nbsp; thanks to the
        <a href="http://www.ldraw.org/">ldraw utilities</a>, i have been able to
        relatively easily get those models into ldraw format and also produce
        some nice instruction images so you can build these yourself (provided
        you have the right legos, of course).
      </p>
      <ul><li><a href="lego/">original lego models</a></li></ul>

<?
  $page->Heading('pen / pencil sketches', 'sketch');
?>
      <p>
        while i don’t do it often (mostly due to the fact that i can’t think of
        anything to draw), i sometimes sketch out something.&nbsp; i used to do
        this in pencil, but after a certain mindless summer job where i had pens
        but no pencils, i’ve turned more toward pen.&nbsp; everything in here is
        scanned directly and not touched up on the computer.
      </p>
      <ul><li><a href="sketch.php">sketch gallery</a></li></ul>

<?
  $page->Heading('comics', 'comics');
?>
      <p>
        i’ve drawn a few comics, mostly during sophomore year of high school.&nbsp;
        there ended up being two series that i actually still have some of the
        original drawings for (there was also an earlier series that i no longer
        have any of).
      </p>
      <ul>
        <li><a href="nazi.php" title="inspired by high school social studies">the nazi teacher party</a> (4 comics)</li>
        <li><a href="linz.php" title="inspired by high school math">the many deaths of mr. linzmeyer</a> (3 comics)</li>
      </ul>

<?
  $page->Heading('digital art', 'digital');
?>
      <p>
        this is where i put the results of my messing around with various
        graphics programs.&nbsp; i unfortunately don’t get around to doing this
        sort of thing often, but it’s entertaining every now and then.
      </p>
      <ul><li><a href="digital.php">digital gallery</a></li></ul>

<?
  $page->Heading('cd compilations', 'cd');
?>
      <p>
        one of my hobbies is to use my limited tools and skills to throw together
        mix / compilation cds.&nbsp; i usually start with a theme, then pick out
        some tracks, do some audio work, and design cover art.&nbsp; i never
        design labels for on the cds since i am told that they tend to come off in
        slot-feed cd players when the cds get too hot.
      </p>
      <p>
        i do my audio work in audacity (previously cool edit).&nbsp; i can’t
        really do much, but i like to work with making the left channel
        different from the right channel, fading tracks together for smooth
        transitions, and am starting to play with distortion a little.&nbsp; i
        also enjoy taking two versions of the same song, hacking them up, and
        making a hybrid version using my favorite parts from each version.
      </p>
      <p>
        my graphics work is done in the gimp (previously paint shop pro).&nbsp;
        sometimes i start with images from the internet, other times i scan in a
        photo or sketch something out myself.&nbsp; the graphics part can be
        even more enjoyable than the audio sometimes, but i tend to feel a
        stronger sense of accomplishment if i do something i like with the audio
        than i do with graphics.
      </p>
      <p>
        i’ve put information on most of my work up here:&nbsp; there are
        scaled-down covers as well as track listings for most of the cds i’ve
        made.
      </p>
      <ul><li><a href="compilations.php">cover art and track lists</a></li></ul>

<?
  $page->Heading('merchandise', 'shop');
?>
      <p>
        some of the designs from this section are available on shirts and other
        sorts of things from cafepress.&nbsp; since i don’t want to pay
        cafepress in order to be able to have multiple designs available on the
        same page, i have an index of all the designs i’ve put up there.
      </p>
      <ul><li><a href="shop/">track7 merchandise</a></li></ul>

<?
  $page->Heading('skins / themes / etc.', 'skin');
?>
      <p>
        it’s been a long time since i made a skin or theme for anything, so most
        of these are for software people don’t use anymore (windows 98 and an
        old version of firefox).&nbsp; they’re still something i made though,
        and the latest winamp can still use this winamp skin (though there’s
        little reason to since you probably have a true color display), so
        they’re still here.
      </p>
      <ul><li><a href="skin.php">skins and themes</a></li></ul>

<?
  $page->Heading('photo album', 'album');
?>
      <img class="icon" src="/album/favicon.png" alt="" />
      <p class="iconned">
        everybody likes photos, right?&nbsp; i tend to hoard them and not look
        at them <em>or</em> show them to anybody.&nbsp; to help reverse that,
        here’s an online photo album with some pictures, which i really should
        add to more frequently.
      </p>
      <ul><li><a href="/album/">photo album</a></li></ul>

<?
  $page->End();
?>
