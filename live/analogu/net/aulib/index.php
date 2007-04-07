<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  $page->Start('shared libraries - c# .net - analogu', 'shared libraries (aulib)', '', '', array('files', 'help'));
?>
      <p>
        shared libraries used by most analog underground applications (including
        the two above).&nbsp; it's not an application itself, and is pretty much
        useless to anyone who isn't a programmer.&nbsp; programmers may be
        interested to know that the installer places auIO and auComCtl into the
        .net gac, which you then should be able to use in your own .net projects
        (though i couldn't seem to get that part to work).
      </p>

<?
  $page->Heading('files', 'files');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>package</th><th>version</th><th>filename</th><th>size</th><th>type</th></tr></thead>
        <tbody>
          <tr class="current"><td>aulib installer</td><td class="number">3.0.0</td><td><a href="/files/analogu/net/aulib/aulib-v3.0.0.msi">aulib-v3.0.0.msi</a></td><td class="number"><?=auFile::Size('aulib-v3.0.0.msi'); ?></td><td>windows installer</td></tr>
          <tr class="current"><td>aulib source <a href="#source" title="notes on installing source code">*</a></td><td class="number">3.0.0</td><td><a href="/files/analogu/net/aulib/ausrc300.cab">ausrc300.cab</a></td><td class="number"><?=auFile::Size('ausrc300.cab'); ?></td><td>windows cabinet archive</td></tr>
        </tbody>
      </table>
      <p class="info" id="source">
        in order to get the source code, you must use the msi installer for the
        same version, and select either custom or complete installation.&nbsp;
        the source cab file must be in the same directory as the msi.
      </p>

<?
  $page->Heading('support', 'help');
?>
      <p>
        since there's no manual, post a question in the
        <a href="/io/f3/" title="track7 help desk forum">forums</a> or
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
