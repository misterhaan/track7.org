<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for the site map (tree) page                       <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

ul#treelinks {
  margin-left: 0;
}
li.folder {
  list-style-image: url(/style/folder.png);
}
li.anchor {
  list-style-image: url(/style/anchor.png);
}
li.page {
  list-style-image: url(/style/html-<?=(OPERA ? 'opera' : (MSIE ? 'ie' : 'firefox')); ?>.png);
}
<?
  if(OPERA || MSIE) {
?>
li.folder,
li.anchor,
li.page {
  padding-left: .5em;
}
<?
  }
?>
