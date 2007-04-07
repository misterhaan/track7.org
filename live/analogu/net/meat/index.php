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
        <thead><tr><th>package</th><th>version</th><th>filename</th><th>size</th><th>downloads</th></tr></thead>
        <tbody>
          <tr class="current"><td>meat installer</td><td class="number">0.1.0</td><td><a href="/files/analogu/net/meat/meat-v0.1.0.msi">meat-v0.1.0.msi</a></td><td class="number"><?=auFile::Size('meat-v0.1.0.msi'); ?></td><td class="number"></td></tr>
          <tr class="current"><td>meat source <a href="#source" title="notes on installing source code">*</a></td><td class="number">0.1.0</td><td><a href="/files/analogu/net/meat/m-src010.cab">m-src010.cab</a></td><td class="number"><?=auFile::Size('m-src010.cab'); ?></td><td></td></tr>
        </tbody>
      </table>
      <p class="info" id="source">
        in order to get the source code, you must use the msi installer for the
        same version, and select either custom or complete installation.&nbsp;
        the source cab file must be in the same directory as the msi.
      </p>

<?
  $page->Heading('documentation', 'doc');
?>
      <p>
        the intent is for meat to be easy enough to use without needing to look
        for documentation or support.&nbsp; to help with this, the entire
        application is enabled with tooltips -- just move the mouse over the
        text next to a field and it will explain what it's for.&nbsp; when
        that's not enough, check the <a href="manual/" title="movie enlister and tracker manual">manual</a>
        that will eventually be added here.
      </p>
      <ul><li><a href="manual/">movie enlister and tracker manual</a></li></ul>
      
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
