<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for svn update page                                <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

a.update {
  margin-left: 1em;
}
a.update:hover {
  background-color: transparent;
}

ul.path li.up {
  list-style-image: url(/style/folder.png);
}
ul.path li.dir {
  list-style-image: url(/style/folder-closed.png);
}
ul.path li.file {
  list-style-image: url(/style/file.png);
}
