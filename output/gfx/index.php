<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('graphics', null, '', '', array('skin', 'digital', 'sketch', 'comics', 'shop'));

  $page->Heading('skins / themes / etc.', 'skin');
?>
      <p>
        sometimes i want to change how winamp or windows or something else
        looks, so i'll make a skin or a desktop theme or whatever might
        apply.&nbsp; i'm probably not all that good, but go ahead and take a
        look at what i've come up with -- you can even download and use it if
        you are so inclined.
      </p>
      <ul><li><a href="skin.php">skins and themes</a></li></ul>

<?
  $page->Heading('digital art', 'digital');
?>
      <p>
        this is where i put the results of my messing around with various
        graphics programs.&nbsp; i unfortunately don't get around to doing this
        sort of thing often, but it's entertaining every now and then.
      </p>
      <ul><li><a href="digital.php">digital gallery</a></li></ul>

<?
  $page->Heading('pen / pencil sketches', 'sketch');
?>
      <p>
        while i don't do it often (mostly due to the fact that i can't think of
        anything to draw), i sometimes sketch out something.&nbsp; i used to do
        this in pencil, but after a certain mindless summer job where i had pens
        but no pencils, i've turned more toward pen.&nbsp; everything in here is
        scanned directly and not touched up on the computer.
      </p>
      <ul><li><a href="sketch.php">sketch gallery</a></li></ul>

<?
  $page->Heading('comics', 'comics');
?>
      <p>
        i've drawn a few comics, mostly during sophomore year of high school.&nbsp;
        there ended up being two series that i actually still have some of the
        original drawings for (there was also an earlier series that i no longer
        have any of).
      </p>
      <ul>
        <li><a href="nazi.php" title="inspired by high school social studies">the nazi teacher party</a> (4 comics)</li>
        <li><a href="linz.php" title="inspired by high school math">the many deaths of mr. linzmeyer</a> (3 comics)</li>
      </ul>

<?
  $page->Heading('merchandise', 'shop');
?>
      <p>
        some of the designs from this section are available on shirts and other
        sorts of things from cafepress.&nbsp; since i don't want to pay
        cafepress in order to be able to have multiple designs available on the
        same page, i have an index of all the designs i've put up there.
      </p>
      <ul><li><a href="shop/">track7 merchandise</a></li></ul>

<?
  $page->End();
?>
