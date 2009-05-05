<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for svn update page                                        *
\******************************************************************************/

a.update {
  margin-left: 1em;
}
a.update:hover {
  background-color: transparent;
}

ul.path li.up {
  list-style-image: url(/images/treetype/folder.png);
}
ul.path li.dir {
  list-style-image: url(/images/treetype/folder.png);
}
ul.path li.file {
  list-style-image: url(/images/treetype/file.png);
}
