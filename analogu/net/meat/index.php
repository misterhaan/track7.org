<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  $page->Start('movie enlister and tracker (meat) - c# .net - analogu', 'movie enlister and tracker (meat)', '', '', array('files', 'doc', 'help'));
?>
      <p>
        movie enlister and tracker (meat) is designed to aid in managing large
        collections of digital movies and their backup status.&nbsp; it replaces
        movie information manager (mim) and should provide all the features that
        mim did, and hopefully a few more as well.&nbsp; it is able to read data
        files created with mim, but will only save them to the new meat format.
      </p>

<?
  $page->Heading('files', 'files');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>package</th><th>version</th><th>filename</th><th>size</th><th>type</th></tr></thead>
        <tbody>
          <tr class="current"><td>meat installer</td><td class="number">0.2.0</td><td><a href="/files/analogu/net/meat/meat-v0.2.0.msi">meat-v0.2.0.msi</a></td><td class="number"><?=auFile::Size('meat-v0.2.0.msi'); ?></td><td>windows installer</td></tr>
          <tr class="current"><td>meat source</td><td class="number">0.2.0</td><td><a href="http://svn.track7.org/meat/releases/0.2.0/">0.2.0 source</a></td><td></td><td>subversion repository</td></tr>
          <tr><td>meat installer</td><td class="number">0.1.0</td><td><a href="/files/analogu/net/meat/meat-v0.1.0.msi">meat-v0.1.0.msi</a></td><td class="number"><?=auFile::Size('meat-v0.1.0.msi'); ?></td><td>windows installer</td></tr>
          <tr><td>meat source</td><td class="number">0.1.0</td><td><a href="http://svn.track7.org/meat/releases/0.1.0/">0.1.0 source</a></td><td></td><td>subversion repository</td></tr>
        </tbody>
      </table>

<?
  $page->Heading('documentation', 'doc');
?>
      <p>
        the intent is for meat to be easy enough to use without needing to look
        for documentation or support.&nbsp; to help with this, the entire
        application is enabled with tooltips -- just move the mouse over the
        text next to a field and it will explain what it's for.&nbsp; when
        that's not enough, check the documentation on auwiki.
      </p>
      <ul><li><a href="http://wiki.track7.org/Movie_Enlister_And_Tracker">movie enlister and tracker documentation on auwiki</a></li></ul>
      
<?
  $page->Heading('support', 'help');
?>
      <p>
        if you can't find the help you need in the tooltips or the manual, post
        a question in the <a href="/oi/f3/" title="track7 help desk forum">forums</a> or
        <a href="/user/sendmessage.php?to=misterhaan" title="send a message to misterhaan">contact me</a>
        directly.&nbsp; either way i'll do my best to help you out.
      </p>
      <ul>
        <li><a href="/oi/f3/">track7 help desk forum</a></li>
        <li><a href="/user/sendmessage.php?to=misterhaan">send a message to misterhaan</a></li>
      </ul>
<?
  $page->End();
?>
