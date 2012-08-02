<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('gamesink - c# .net - analogu', 'gamesink', '', '', array('files', 'doc', 'help'));
?>
      <p>
        gamesink is designed to simplify backing up and restoring game save
        files, especially between computers or when reinstalling a computer from
        scratch.&nbsp; once a backup directory is defined, supported games can
        be backed up as a single file per game in that directory.&nbsp; choose a
        directory on an external drive, or copy the contents of the directory to
        an external location to transfer to a new installation.&nbsp; the
        <a href="http://wiki.track7.org/GameSink_Supported_Games">list of supported games</a>
        is kept up-to-date on auwiki.
      </p>
<?
  $page->Heading('files', 'files');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>package</th><th>version</th><th>filename</th><th>size</th><th>type</th></tr></thead>
        <tbody>
          <tr class="current"><td>gamesink 64-bit installer</td><td class="number">0.1.0</td><td><a href="/files/analogu/net/gamesink/gamesink-v0.1.0_x64.msi">gamesink-v0.1.0_x64.msi</a></td><td class="number"><?=auFile::Size('gamesink-v0.1.0_x64.msi'); ?></td><td>windows installer</td></tr>
          <tr class="current"><td>gamesink 32-bit installer</td><td class="number">0.1.0</td><td><a href="/files/analogu/net/gamesink/gamesink-v0.1.0_x64.msi">gamesink-v0.1.0_x64.msi</a></td><td class="number"><?=auFile::Size('gamesink-v0.1.0_x64.msi'); ?></td><td>windows installer</td></tr>
          <tr class="current"><td>gamesink source</td><td class="number">0.1.0</td><td><a href="http://svn.track7.org/GameSink/releases/0.1/">0.1.0 source</a></td><td></td><td>subversion repository</td></tr>
        </tbody>
      </table>

<?
  $page->Heading('documentation', 'doc');
?>
      <p>
        the intent is for gamesink to be easy enough to use without needing to
        look for documentation or support.&nbsp; to help with this, the entire
        application is enabled with tooltips -- just move the mouse over the
        text next to a field and it will explain what it's for.&nbsp; when
        that's not enough, check the documentation on auwiki.
      </p>
      <ul><li><a href="http://wiki.track7.org/GameSink">gamesink documentation on auwiki</a></li></ul>

<?
  $page->Heading('support', 'help');
?>
     <p>
        if you can't find the help you need in the tooltips or auwiki, post a
        question in the <a href="/hb/" title="track7 forums">forums</a> or
        <a href="/user/sendmessage.php?to=misterhaan" title="send a message to misterhaan">contact me</a>
        directly.&nbsp; either way i'll do my best to help you out.
      </p>
      <ul>
        <li><a href="/hb/">track7 forums</a></li>
        <li><a href="/user/sendmessage.php?to=misterhaan">send a message to misterhaan</a></li>
      </ul>
<?
  $page->End();
?>
