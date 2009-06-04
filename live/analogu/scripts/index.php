<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->Start('scripts - the analog underground', 'scripts');
?>
      <p>
        i have taken some parts of track7 and packaged them up for anyone
        interested in modifying the scripts to work for their own site.&nbsp;
        some of the scripts available here are no longer being used on track7,
        but everything at least was used at some time.&nbsp; note that
        practically every one of them will require some modification before it
        will work for a different site — read the directions after choosing a
        script to find out see what needs to be changed.
      </p>
      <p>
        please note that the directions listed here are purposely not very
        complete or specific:&nbsp; i do not intend for any of this to
        automatically work if you just upload it to your site.&nbsp; in other
        words, <em>you should have at least some php experience</em> if you want
        to use these scripts.
      </p>
      <p>
        i’m also starting to put up some javascripts, which may or may not have
        anything to do with track7 (for example, MoreSPORE is a greasemonkey
        script for spore.com).&nbsp; greasemonkey scripts should be usable
        exactly as they are, but the others expect some level of javascript
        knowledge in order to use them.
      </p>

<?
  $scripts = 'select name, title from auscripts order by title';
  if($scripts = $db->Get($scripts, 'error looking up available scripts', 'no scripts found')) {
?>
      <ul>
<?
    while($script = $scripts->NextRecord()) {
?>
        <li><a href="<?=$script->name; ?>"><?=$script->title; ?></a></li>
<?
    }
?>
      </ul>

<?
  }
  $page->End();
?>
