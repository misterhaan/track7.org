<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('track7\'s neighborhood', 'neighbors', '', '', array('contact', 'link', 'visit', 'leave'));

  $page->Heading('contact me', 'contact');
?>
      <p>
        if you've been looking around here for a while, surely you've found
        something that has caused you to have some sort of reaction.&nbsp; well
        you're in luck, because here are a bunch of ways to say what you think!&nbsp;
        <a href="gb-sign.php">the guestbook</a> or <a href="/hb/">the forums</a>
        are probably best, because then anybody can see what you have to say.&nbsp;
        otherwise you can send me a message through track7 or contact me through
        icq / aim by going to <a href="user/misterhaan/">my profile</a>.&nbsp;
        and even if you don't have anything to say, you might want to sign the
        guestbook just for the mad lib!
      </p>

<?
  $page->Heading('link to me', 'link');
?>
      <p>
        you are welcome and encouraged to link to track7.&nbsp; if you don't
        like this graphic, feel free to use any other means of linking to
        this site, and please <a href="gb-sign.php">sign the guestbook</a> or
        <a href="/hb/">post in the forums</a> to let me know you have a link to
        my site.&nbsp; to use the link image below, copy and paste the code next
        to the graphic onto your page.
      </p>
      <div id="linkoptions">
        <a class="preview" href="http://www.track7.org/" title="track7"><img src="t7logo.png" alt="track7" style="width: 110px; height: 50px;" /></a>
        <code>
          &lt;a href=&quot;http://<?=$_SERVER['HTTP_HOST']; ?>/&quot; title=&quot;track7&quot;&gt;&lt;img src=&quot;http://<? echo $_SERVER['HTTP_HOST']; ?>/t7logo.png&quot; alt=&quot;track7&quot; style=&quot;width: 110px; height: 50px;&quot; /&gt;&lt;/a&gt;
        </code>
      </div>

<?
  $page->Heading('visit my friends', 'visit');
?>
      <p>
        yeah, i have some friends.&nbsp; some of them even have websites.&nbsp;
        if you thought you were my friend but are now unsure since you have a
        website that isn't listed here, let me know and i'll add your link (or
        i'll tell you you're not my friend).
      </p>
      <ul>
        <li><a href="http://jmeagher.2ya.com/">joe's index and links</a> *</li>
        <li><a href="http://www.thecircumlocution.com/">the circumlocution</a></li>
        <li><a href="http://www.katana.org/" title="visit katana.org">katana.org</a></li>
        <li><a href="http://www.allsyntax.com/">allsyntax.com</a></li>
        <li><a href="http://www.kzoiks.nl/">kzoiks!</a> *</li>
        <li><a href="http://www.restoman.com/" title="visit restoman.com">restoman.com</a></li>
        <li><a href="http://www.geocities.com/Yosemite/9483" title="visit the unofficial camp michigamme bum page">the unofficial camp michigamme bum page</a> *&nbsp; <i>!!plays music!!</i></li>
      </ul>
      <p class="note">* this site has a link to track7</p>

<?
  $page->Heading('go somewhere else', 'leave');
?>
      <p>
        once you've seen everything here, you may as well go somewhere else, so
        here are a few recommended options.
      </p>
<?
  $page->End(false);  // don't write out 'related links' heading
?>
