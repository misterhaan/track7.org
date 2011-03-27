<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  $page->Start('skins / themes / etc.');

  $page->Heading('simpamp', 'simpamp');
?>
      <div class="preview">
        <img src="simpamp0.png" alt="" style="height: 63px;" /><br />
        <a href="/files/av/simpamp0.wsz" title="download the simpamp skin for winamp">simpamp</a>
        <div><?=auFile::Size('simpamp0.wsz'); ?></div>
      </div>
      <p class="previewed">
        this is the only winamp skin i've made so far.&nbsp; the motivation
        behind this is that i have a laptop that can only display 256 colors
        (8-bit) and all winamp skins available to me at the time looked stupid
        because they got their colors scrambled all the time (windows does that
        if you have a 256-color display).&nbsp; so the main purpose of me making
        this skin (by hand in paint shop pro) was to have something that didn't
        look stupid on my laptop.&nbsp; it uses the default windows 16-color
        palette, which is the only way to avoid the scrambled color thing.
      </p>

<?
  $page->Heading('the echoing theme', 'echoingtheme');
?>
      <div class="preview">
        <img src="echoingtheme.png" alt="" style="height: 100px;" /><br />
        <a href="/files/av/echoingtheme.zip" title="download the echoing theme for windows">the echoing theme</a>
        <div><?=auFile::Size('echoingtheme.zip'); ?></div>
      </div>
      <p class="previewed">
        i'm not sure if this will be useful to anybody anymore, but it's an
        echoing green desktop theme made for use with windows 98.&nbsp; it's
        basically a wallpaper, window color scheme, and some cursors.&nbsp; it
        also includes a full set of sounds that i pulled off a few echoing green
        cds.&nbsp; if you want to know more about the echoing green, visit
        <a href="http://www.echocentral.com/" title="echocentral, the echoing green's official website">their website</a>.&nbsp;
        i'm pretty sure you can still download mp3s and maybe a quicktime music
        video or two--definitely worth checking out!
      </p>

<?
  $page->Heading('qute program icons', 'quteprogicon');
?>
      <div class="preview">
        <img src="quteprogicon.png" alt="" style="height: 141px;" /><br />
        <a href="/files/av/quteprogicon.xpi" title="install the qute program icons extension for firebird on windows">qute program icons</a>
        <div><?=auFile::Size('quteprogicon.xpi'); ?></div>
      </div>
      <p class="previewed">
        a firebird extension (sort of) to change the icons of various windows so
        that they better match the default (qute) theme.&nbsp; the icons i used
        are designed by <a href="http://www.quadrone.org/" title="visit quadrone.org">arvid axelsson</a>
        who designed the qute theme for firebird.&nbsp; i packaged them with an
        install script that will put them in the proper location to change your
        window icons.&nbsp; presently shortcuts are unaffected as is the icon
        used for html files and the like.&nbsp; clicking on the link should
        install the icons for you if you're running firebird.&nbsp; currently
        the following icons are changed:
      </p>
      <ul>
        <li>browser window</li>
        <li>bookmarks manager window</li>
        <li>downloads window</li>
        <li><abbr title="document object model">dom</abbr> inspector window</li>
        <li>javascript console window</li>
      </ul>

<?
  $page->End();
?>
