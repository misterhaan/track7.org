<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('mythclient - c# .net - analogu', 'mythclient', '', '', array('files', 'doc', 'help'));
?>
      <p>
        mythclient (mythtv recorded programs) is designed to make it easier for
        windows clients to playback recordings from a separate mythtv server.&nbsp;
        the mythtv server must provide mythweb as well as a windows-accessible
        (probably through samba) share with the recording files.&nbsp; using
        mythweb directly requires the client to download the recording file
        before watching it, and using the share directly doesn’t include which
        show or episode each file is, so mythclient bridges the gap.
      </p>

<?
  $page->Heading('files', 'files');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>package</th><th>version</th><th>filename</th><th>size</th><th>type</th></tr></thead>
        <tbody>
          <tr class="current"><td>mythclient 64-bit installer</td><td class="number">0.1.0</td><td><a href="/files/analogu/net/mythclient/mythclient-v0.1.0_x64.msi">mythclient-v0.1.0_x64.msi</a></td><td class="number"><?=auFile::Size('mythclient-v0.1.0_x64.msi'); ?></td><td>windows installer</td></tr>
          <tr class="current"><td>mythclient 32-bit installer</td><td class="number">0.1.0</td><td><a href="/files/analogu/net/mythclient/mythclient-v0.1.0_x86.msi">mythclient-v0.1.0_x86.msi</a></td><td class="number"><?=auFile::Size('mythclient-v0.1.0_x86.msi'); ?></td><td>windows installer</td></tr>
          <tr class="current"><td>mythclient source</td><td class="number">0.1.0</td><td><a href="http://svn.track7.org/mythclient/releases/0.1/">0.1 source</a></td><td></td><td>subversion repository</td></tr>
        </tbody>
      </table>

<?
  $page->Heading('documentation', 'doc');
?>
      <p>
        the intent is for mythclient to be easy enough to use without needing to
        look for documentation or support.&nbsp; to help with this, the entire
        application is enabled with tooltips — just move the mouse over the text
        next to a field and it will explain what it’s for.&nbsp; when that’s not
        enough, check the documentation on auwiki.
      </p>
      <ul><li><a href="http://wiki.track7.org/MythClient">mythclient documentation on auwiki</a></li></ul>

<?
  $page->Heading('support', 'help');
?>
      <p>
        if you can’t find the help you need in the tooltips or the manual, post
        a question in the <a href="/oi/f3/" title="track7 help desk forum">forums</a> or
        <a href="/user/sendmessage.php?to=misterhaan" title="send a message to misterhaan">contact me</a>
        directly.&nbsp; either way i’ll do my best to help you out.
      </p>
      <ul>
        <li><a href="/oi/f3/">track7 help desk forum</a></li>
        <li><a href="/user/sendmessage.php?to=misterhaan">send a message to misterhaan</a></li>
      </ul>
<?
  $page->End();
?>
