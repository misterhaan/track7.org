<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  $page->Start('gameworlds - the analog underground', 'zzt and megazeux (mzx) gameworlds');

  $page->Heading('Weirdland I:&nbsp; <em>The Breakout</em>');
?>
      <div class="preview">
        <img src="wl1.png" title="screenshot from Weirdland I" alt="" /><br />
        <a href="/files/analogu/mzx/wl1_v33.zip" title="download Weirdland I">wl1_v33.zip</a>
        <div><?=auFile::Size('mzx/wl1_v33.zip'); ?></div>
      </div>
      <p class="previewed">
        This game started a series in which the games have strange, sometimes
        confusing plots that have nothing to do with the other games in the
        series.&nbsp; This is the only Weirdland game that can be found in ZZT
        format as the rest are Megazeux.&nbsp; It's actually my second or third
        gameworld in ZZT, but the first one I kept.
      </p>

<?
  $page->Heading('Hercules:&nbsp; <em>The Thirteenth Labor</em>');
?>
      <div class="preview">
        <img src="hercules.png" title="screenshot from Hercules" alt="" class="mzxss" />
        <a href="/files/analogu/mzx/hercules.zip" title="download Hercules:&nbsp; The Thirteenth Labor">hercules.zip</a>
        <div><?=auFile::Size('mzx/hercules.zip'); ?></div>
      </div>
      <p class="previewed">
        A product of a 10th grade English class.&nbsp; This was a group project
        for the mythology unit, where Weirdland II (unfinished at the time) was
        adapted to a Greek mythological storyline and carried out to become a
        full (though short) game.&nbsp; This was the first time I had a raft
        going across a river in a game, and I made the river by coloring lava
        dark blue.
      </p>

<?
  $page->Heading('Weirdland II:&nbsp; <em>Yellow Snow and the Seventh Dork</em>');
?>
      <div class="preview">
        <img src="wl2.png" title="screenshot from Weirdland II" alt="" class="mzxss" />
        <a href="/files/analogu/mzx/wl2_v53.zip" title="download Weirdland II:&nbsp; Yellow Snow and the Seventh Dork">wl2_v53.zip</a>
        <div><?=auFile::Size('mzx/wl2_v53.zip'); ?></div>
      </div>
      <p class="previewed">
        Featuring music from They Might Be Giants and The Benny Hill show, this
        game combines weird humor and classic fairy tales.&nbsp; You must take
        on the role of of Yellow Snow and attempt to rescue the seventh dork
        from the evil witch.&nbsp; The other dorks aren't much help to you, but
        at least you can sell some of the diamonds from their mine.
      </p>

<?
  $page->Heading('Weirdland III:&nbsp; <em>Jay &amp; Andrew vs. the Nazi Teacher Party</em>');
?>
      <div class="preview">
        <img src="wl3.png" title="screenshot from Weirdland III" alt="" class="mzxss" />
        <a href="/files/analogu/mzx/wl3_demo.zip" title="download Weirdland III:&nbsp; Jay &amp; Andrew vs. the Nazi Teacher Party">wl3_demo.zip</a>
        <div><?=auFile::Size('mzx/wl3_demo.zip'); ?></div>
      </div>
      <p class="previewed">
        This is a game in progress and this is only a demo.&nbsp; A high school
        history teacher goes nazi during a World War II unit and it's up to you
        and your straw wrappers to stop him and his followers from eliminating
        all students, including you and your friend.
      </p>

<?
  $page->Heading('<em>Larry\'s Adventure in:</em>&nbsp; Happy Fairy Land');
?>
      <div class="preview">
        <img src="wl4.png" title="screenshot from Happy Fairy Land" alt="" class="mzxss" />
        <a href="/files/analogu/mzx/wl4_demo.zip" title="download Larry's Adventure in:&nbsp; Happy Fairy Land">wl4_demo.zip</a>
        <div><?=auFile::Size('mzx/wl4_demo.zip'); ?></div>
      </div>
      <p class="previewed">
        Another demo of a game in progress.&nbsp; This one's a lot closer to RPG
        format.&nbsp; You play Larry, a regluar guy that somehow ends up in
        Happy Fairy Land and has to figure out what's going on and eventually
        get home.&nbsp; There's actually something to do here--give those elves
        a pummeling!
      </p>

<?
  $page->Heading('Plexus:&nbsp; <em>The Plexus Project</em>');
?>
      <div class="preview">
        <img src="wl5.png" title="(future screenshot from The Plexus Project)" alt="" class="mzxss" />
      </div>
      <p class="previewed">
        This game is not technically even started thus far, but the idea is
        there.&nbsp; You play a low-level factory worker who completes various
        subversive missions for a co-worker named Mark while avoiding bosses as
        not to get laid off.&nbsp; The Sue Biest is another danger to be wary
        of--while it is enjoyable to play various pranks on her, to incur her
        wrath is to place little value in one's well-being.
      </p>

      <br class="clear" />

      <p>
        * in order to play these games, you will need to download the
        appropriate game environment.&nbsp; for weirdland i you will need zzt,
        and for everything else you will need megazeux.&nbsp; both are available
        here download since they are either shareware or freeware.&nbsp; also
        available are icons (provided by the analog underground) for each
        environment.&nbsp; for megazeux you may find it necessary to use a
        program such as <a href="http://sourceforge.net/projects/vdmsound/">vdm sound</a> in order
        to play music and effects through your sound card.
      </p>

      <table class="data" cellspacing="0">
        <thead><tr><td class="clear"></td><th>environment</th><th>icon</th></tr></thead>
        <tbody>
          <tr>
            <td>ZZT</td>
            <td><a title="download zzt v3.2" href="/files/analogu/mzx/zzt32.zip"><?=auFile::Size('mzx/zzt32.zip'); ?></a></td>
            <td><a title="download zzt icon" href="mzx/zzt.ico"><?=auFile::Size('mzx/zzt.ico'); ?></a></td>
          </tr>
          <tr>
            <td>Megazeux</td>
            <td><a title="download megazeux v2.51" href="/files/analogu/mzx/megazeux2_51.zip"><?=auFile::Size('mzx/megazeux2_51.zip'); ?></a></td>
            <td><a title="download megazeux icon" href="mzx/mzx.ico"><?=auFile::Size('mzx/mzx.ico'); ?></a></td>
          </tr>
        </tbody>
      </table>

<?
  $page->End();
?>
