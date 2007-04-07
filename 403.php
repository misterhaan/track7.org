<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('403 forbidden', '403 banished');
?>
    <p>
      if you're seeing this page, that means the server thinks you're a bot or a
      spammer.&nbsp; it also sometimes shows up if you go to a directory with no
      index.&nbsp; if you are actually some sort of lifeform (a.i. does not
      count) and you came here from a site that has been added to the spammers
      list, you can probably get where you wanted to go by following
      <a href="<?=htmlspecialchars($_SERVER['REQUEST_URI']); ?>">this link</a>.
    </p>
    <p>
      if you're not a spammer or a bot and are wondering why you're seeing this
      page, it's because your browser is lying to the server.&nbsp; user-agent
      strings that match &quot;one or two words, possibly followed by a 1-, 2-,
      or 3-segment version number&quot; are considered useless bots and are not
      allowed.&nbsp; also certain urls passed through the HTTP Referer header
      which have suspiciously showed up in my statistics data are blocked.&nbsp;
      if you have set your browser to send a user-agent that matches the above
      description, you will need to change it to be able to view this site.&nbsp;
      i don't know of any browsers who send such a useragent by default.&nbsp;
      the blocked referers are either porn sites or spam sites, but if you by
      chance were visiting one before coming here, your browser may have told
      the server where you came from and caused the server to assume you're a
      spammer.&nbsp; clicking the link above should get you where you tried to
      go without logging an entry for the porn/spam site.
    </p>
    <p>
      sorry for any inconvenience this may have caused for actual people trying
      to view my site!
    </p>
    
    <table class="columns" cellspacing="0">
      <tr class="firstchild"><th>HTTP_REFERER</th><td><?=htmlspecialchars($_SERVER['HTTP_REFERER']); ?></td></tr>
      <tr><th>REMOTE_ADDR</th><td><?=htmlspecialchars($_SERVER['REMOTE_ADDR']); ?></td></tr>
      <tr><th>HTTP_USER_AGENT</th><td><?=htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></td></tr>
    </table>

<?
  $page->End();
?>
