<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('few rights reserved', null, '', '', array('reasons', 'links', 'credit', 'tell me', 'borrowed'), array('reasoning', 'linking', 'credit', 'tellme', 'borrowed'));

  $page->Heading('reasoning', 'reasoning');
?>
      <p>
        the contents of track7 are copyright 'few rights reserved' instead of
        'all rights reserved' like you are probably more used to seeing.&nbsp;
        the basic idea is that i don't have any reason to try to impose a lot of
        limitations on how my work is allowed to be used.&nbsp; so instead of
        reserving all rights, i'm only reserving a few.&nbsp; please note that
        some of the contents of track7 are under someone else's copyright, these
        are all noted at the bottom of this page.&nbsp; i cannot give permission
        to use that content for any other purposes, and in some cases i do not
        have explicit permission myself.&nbsp; for copyright holders who are
        displeased with my use of your copyright on track7,
        <a href="/user/sendmessage?user=1">send me a message</a> to inform me
        and i will work something out with you.
      </p>

<?
  $page->Heading('linking to track7', 'linking');
?>
      <p>
        some sites seek to disallow linking to any page other than the root
        page and / or require that all links are phrased a certain way or use
        certain images.&nbsp; if you would like to link to track7 from your
        website, go ahead and link directly to any page you want!&nbsp; if,
        however, you are linking to a download file, please link through my
        download.php script so i can have a more accurate download count.&nbsp;
        all download links from track7 should be going through download.php, so
        copying the links is a great way to go.&nbsp; the script is mostly
        transparent to users, other than right-clicking to save the link target
        will come up with the wrong filename.&nbsp; there is unfortunately
        nothing i can do about that.
      </p>

<?
  $page->Heading('don\'t take credit for my work', 'credit');
?>
      <p>
        you're welcome to use anything you want from track7 (with the exception
        of things not of my own creation, listed below).&nbsp; if you do use
        something of mine, i ask that you refrain from presenting my work as
        your own.&nbsp; often the act of using someone else's work without
        saying that it is someone else's work is the same as saying that it is
        your own work, so give appropriate credit&mdash;preferably with a link
        to track7.
      </p>

<?
  $page->Heading('let me know', 'tellme');
?>
      <p>
        if you do use something from track7, i would love to know what you've
        done with it!&nbsp; as long as you're not presenting my work as your own
        i'm not going to be upset with you, so go to the
        <a href="/neighbors.php" title="track7 neighborhood">neighborhood page</a>
        and tell me how you found some part of track7 useful.&nbsp; if you have
        a website i will most likely link to it from track7 unless you prefer
        that i don't.
      </p>

<?
  $page->Heading('borrowed content', 'borrowed');
?>
      <p>
        as mentioned earlier on this page, some of the content on track7 is
        restricted by copyright held by someone else.&nbsp; the 'few rights
        reserved' idea may not apply to these:
      </p>
      <ul>
        <li>
          dreamhost, linux, apache, php, and mysql logos belong to other people,
          probably the organizations that provide the software (or service, in
          the case of dreamhost).&nbsp; i shrunk their logos to fit into my
          &quot;powered by&quot; images, the rest of which i created on my own.
        </li>
        <li>
          cover art for all your bass are belong to us, rants and raves, khaos
          theory, save the party, and the ska-skank redemption are based
          respectively on images from <a href="http://en.wikipedia.org/wiki/Zero_Wing">zero wing</a>,
          an <a href="http://www.mcescher.com/">m.c. escher</a> sketch, a
          mandelbrot set from a university math or science page,
          <a href="http://www.asterix.tm.fr/">astérix</a>, and
          <a href="http://www.imdb.com/title/tt0111161/">the shawshank redemption</a>.
        </li>
      </ul>
      <p>
        some of the images that i have made are based on other images i have
        found on the internet.&nbsp; i am not sure if the owners of the original
        images have any say in whether or not you can use my images that are
        based on theirs.
      </p>

<?
  $page->End();
?>
