<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  $page->Start('shared libraries - c# .net - analogu', 'shared libraries (au.util)', '', '', array('files', 'help'));
?>
      <p>
        shared libraries used by most analog underground applications.&nbsp;
        they're not an application themselves, and are pretty much useless to
        anyone who isn't a programmer.&nbsp; the two dll files are
        strongly-named which means they can be installed to the
        <abbr title="global assembly cache">gac</abbr>, which will be done if
        you build the source.&nbsp; downloading the binaries will get you the
        two dll files, which can be used without being put into the gac.
      </p>

<?
  $page->Heading('files', 'files');
?>
      <table class="data" cellspacing="0">
        <thead><tr><th>package</th><th>version</th><th>filename</th><th>size</th><th>type</th></tr></thead>
        <tbody>
          <tr class="current"><td>au.util binaries</td><td class="number">3.1.0</td><td><a href="/files/analogu/net/aulib/au.util-v3.1.0.zip">au.util-v3.1.0.zip</a></td><td class="number"><?=auFile::Size('au.util-v3.1.0.zip'); ?></td><td>zip archive</td></tr>
          <tr class="current"><td>au.util source</td><td class="number">3.1.0</td><td><a href="http://svn.track7.org/util/releases/3.1.0/">3.1.0 source</a></td><td></td><td>subversion repository</td></tr>
          <tr><td>aulib installer</td><td class="number">3.0.0</td><td><a href="/files/analogu/net/aulib/aulib-v3.0.0.msi">aulib-v3.0.0.msi</a></td><td class="number"><?=auFile::Size('aulib-v3.0.0.msi'); ?></td><td>windows installer</td></tr>
          <tr><td>aulib source</td><td class="number">3.0.0</td><td><a href="http://svn.track7.org/util/releases/3.0.0/">3.0.0 source</a></td><td></td><td>subversion repository</td></tr>
        </tbody>
      </table>

<?
  $page->Heading('support', 'help');
?>
      <p>
        the documentation for the shared libraries is in progress, available on
        <a href="http://wiki.track7.org/Shared_Libraries" title="shared libraries documentation on auwiki">auwiki</a>.&nbsp;
        if you don't find the information you need there, post a question in the
        <a href="/io/f3/" title="track7 help desk forum">forums</a> or
        <a href="/user/sendmessage.php?to=misterhaan" title="send a message to misterhaan">contact me</a>
        directly.&nbsp; either way i'll do my best to help you out.
      </p>
      <ul>
        <li><a href="http://wiki.track7.org/Shared_Libraries">shared libraries documentation on auwiki</a></li>
        <li><a href="/oi/f3/">track7 help desk forum</a></li>
        <li><a href="/user/sendmessage.php?to=misterhaan">send a message to misterhaan</a></li>
      </ul>
<?
  $page->End();
?>
