<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';

  $page->Start('output', null, '', '', array('pen', 'lego', 'cd', 'graphics'));
  $page->Heading('pen vs. sword', 'pen');
?>
      <img class="icon" src="pen/favicon.png" alt="" />
      <p class="iconned">
        unfortunately, there are no swords in the section.&nbsp; there is pencil
        fighting though, which is a little similar to sword fighting.&nbsp; here
        you will find stories i've written, along with some thoughts i've
        decided to post.&nbsp; feel free to add your thoughts to any of it using
        the form at the bottom of the page.
      </p>
      <ul><li><a href="pen/">pen vs. sword</a></li></ul>

<?
  $page->Heading('lego models', 'lego');
?>
      <img class="icon" src="lego/favicon.png" alt="" />
      <p class="iconned">
        i have a few lego models i built years ago that i've kept together and
        have displayed in my office at work.&nbsp; thanks to the
        <a href="http://www.ldraw.org/">ldraw utilities</a>, i have been able to
        relatively easily get those models into ldraw format and also produce
        some nice instruction images so you can build these yourself (provided
        you have the right legos, of course).
      </p>
<?
  echo '      <ul>' . "\n" . '        <li><a href="lego.php">original lego models</a>';
  $legos = 'select name from legos order by adddate desc';
  if($legos = $db->GetLimit($legos, 0, 5, 'error looking up listing of lego models', '')) {
    echo "<ul>\n";
    while($lego = $legos->NextRecord()) {
?>
          <li><?=$lego->name; ?></li>
<?
    }
    echo '        </ul>';
  }
  echo "</li>\n      </ul>\n\n";
 
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
        i do my audio work in cool edit.&nbsp; i can't really do much, but i like
        to work with making the left channel different from the right channel,
        fading tracks together for smooth transitions, and am starting to play
        with distortion a little.&nbsp; i also enjoy taking two versions of the
        same song, hacking them up, and making a hybrid version using my favorite
        parts from each version.
      </p>
      <p>
        my graphics work is done in paint shop pro.  sometimes i start with images
        from the internet, other times i scan in a photo or sketch something out
        myself.  the graphics part can be even more enjoyable than the audio
        sometimes, but i tend to feel a stronger sense of accomplishment if i do
        something i like with the audio than i do with graphics.
      </p>
      <p>
        i've put information on most of my work up here:&nbsp; there are
        scaled-down covers as well as track listings for most of the cds i've
        made.
      </p>
      <ul><li><a href="compilations.php">cover art and track lists</a></li></ul>

<?
  $page->Heading('graphics', 'graphics');
?>
      <p>
        i used to draw things, both on paper and on the computer.&nbsp; i'm not
        totally sure that what i did on the computer would really qualify as
        drawing (some may say that about what i did on paper as well), but
        either way the results of that sort of activity are on display here.
      </p>
      <ul><li><a href="gfx/">graphics</a></li></ul>

<?
  $page->End();
?>
