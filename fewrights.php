<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('few rights reserved', null, '', '', array('links', 'credit', 'tell me', 'borrowed'), array('reasoning', 'linking', 'credit', 'tellme', 'borrowed'));
  echo "\n\nAccept: " . $_SERVER['HTTP_ACCEPT'] . "\n\n";
?>
      <p>
        the contents of track7 are copyright “few rights reserved” instead of
        “all rights reserved” like you are probably more used to seeing.&nbsp;
        the basic idea is that i don’t have any reason to try to impose a lot of
        limitations on how my work is allowed to be used.&nbsp; so instead of
        reserving all rights, i’m only reserving a few.&nbsp; please note that
        some of the contents of track7 are under someone else’s copyright (these
        are all noted at the bottom of this page).&nbsp; i cannot give
        permission to use that content for any other purposes, and in some cases
        i do not have explicit permission myself.&nbsp; for copyright holders
        who are displeased with my use of your work on track7,
        <a href="/user/sendmessage?user=1">send me a message</a> with your
        specific complaint and i will work something out with you.
      </p>

<?
  $page->Heading('linking to track7', 'linking');
  $page->SubHeading('pages');
?>
      <p>
        some sites seek to disallow linking to any page other than the root
        page and / or require that all links are phrased a certain way or use
        certain images.&nbsp; if you would like to link to track7 from your
        website, go ahead and link directly to any page you want!
      </p>
<?
  $page->SubHeading('downloads');
?>
      <p>
        if you want to link to a download file (that means anything that starts
        with <?=$_SERVER['HTTP_HOST']; ?>/files/), i prefer if you link to my
        page that contains the link.&nbsp; if you have a strong preference to
        link directly to the file anyway, please make it clear that it’s not
        your file (see the next section).
      </p>
<?
  $page->SubHeading('images');
?>
      <p>
        you should not display any image hosted on <?=$_SERVER['HTTP_HOST']; ?>
        directly on any other site.&nbsp; this process is known as
        <a href="http://en.wikipedia.org/wiki/Inline_Linking">hotlinking</a>
        (plus a few other names) and is troublesome in that the site actually
        hosting the image has to send the image to everyone who visits the page
        displaying the image, and that comes out of a monthly quota.&nbsp;
        you’re welcome to save images from my site, put them on a different
        server, and include them on your pages from there — just please make it
        clear that you didn’t create the image (see the next section).&nbsp; if
        i find cases of my images being hotlinked from other sites, i will
        replace them with <a href="/hotlink.png">this</a> instead.
      </p>

<?
  $page->Heading('don’t take credit for my work', 'credit');
?>
      <p>
        you’re welcome to use anything you want from track7 (with the exception
        of things not of my own creation, listed below).&nbsp; if you do use
        something of mine, i ask that you refrain from presenting my work as
        your own.&nbsp; often the act of using someone else’s work without
        saying that it is someone else's work is the same as saying that it is
        your own work, so give appropriate credit — preferably with a link
        to track7.
      </p>

<?
  $page->Heading('let me know', 'tellme');
?>
      <p>
        if you do use something from track7, i would love to know what you’ve
        done with it!&nbsp; as long as you’re not presenting my work as your own
        i’m not going to be upset with you, so go to the
        <a href="/neighbors.php" title="track7 neighborhood">neighborhood page</a>
        and tell me how you found some part of track7 useful.&nbsp; if you have
        a website i will most likely link to it from track7 unless you prefer
        that i don’t.
      </p>

<?
  $page->Heading('borrowed content', 'borrowed');
?>
      <p>
        as mentioned earlier on this page, some of the content on track7 is
        restricted by copyright held by someone else.&nbsp; the “few rights
        reserved” idea may not apply to these:
      </p>
      <ul>
        <li>
          dreamhost, linux, apache, php, and mysql logos belong to other people,
          probably the organizations that provide the software (or service, in
          the case of dreamhost).&nbsp; i shrunk their logos to fit into my
          “powered by” images, the rest of which i created on my own.
        </li>
        <li>
          cover art for all your bass are belong to us, rants and raves, khaos
          theory, save the party, and the ska-skank redemption are based
          respectively on images from <a href="http://en.wikipedia.org/wiki/Zero_Wing">zero wing</a>,
          an <a href="http://www.mcescher.com/">m.c. escher</a> sketch, a
          mandelbrot set from a university math or science page,
          <a href="http://www.asterix.tm.fr/">astérix</a>, and
          <a href="http://www.imdb.com/title/tt0111161/">the shawshank redemption</a>.&nbsp;
          the icon for the graphics section is practically the same as the rants
          and raves cover art.
        </li>
        <li>
          icons from the <a href="http://tango.freedesktop.org/">tango desktop project</a>:&nbsp;
          edit (<img src="/style/edit.png" alt="" />) and www (<img src="/style/www.png" alt="" />).&nbsp;
          these icons are free for anyone to use under the
          <a href="http://creativecommons.org/licenses/by-sa/2.5/">creative commons attribution share-alike license</a>.
        </li>
        <li>
          icon from <a href="http://www.iconbuffet.com/freedelivery/packages/tower-grove-telecom?ref=misterhaan">iconbuffet</a>:&nbsp;
          private message (<img src="/style/pm.png" alt="" />).&nbsp; these icons are
          free to use by anyone who has a free iconbuffet account and has
          collected the appropriate icon sets.
        </li>
        <li>
          icons from the <a href="http://arvidaxelsson.se/qute/">qute</a> and
          <a href="http://arvidaxelsson.se/kempelton/">kempelton</a> icon sets:&nbsp;
          delete (<img src="/style/del.png" alt="" />), friend (<img src="/style/friend.png" alt="" />),
          and quote (<img src="/style/reply-quote.png" alt="" />).&nbsp; kempleton
          icons are free for anyone to use under the
          <a href="http://creativecommons.org/licenses/by-sa/2.5/se/deed.en_US">creative commons attribution-share alike 2.5 sweden license</a>.&nbsp;
          qute icons don’t list a license so i assume it to be under the same
          license as kempleton. 
        </li>
        <li>
          the rss icon (<img src="/style/feed.png" alt="" />) is available all over the
          internet under the <a href="http://www.mozilla.org/MPL/">mpl</a>,
          <a href="http://www.gnu.org/copyleft/gpl-3.0.html">gpl</a>, or
          <a href="http://www.gnu.org/licenses/lgpl.html">lgpl</a>.
        </li>
        <li>
          icons for the geek section and the forums are based on images of a
          rutabaga and a shouting cartoon guy i found through a google image
          search.&nbsp; i no longer remember what websites these came from.
        </li>
      </ul>

<?
  $page->End();
?>
