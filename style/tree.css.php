<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for the site map (tree) page                               *
\******************************************************************************/

ul#treelinks {
  margin-left: 2em;
  padding-left: 0;
  list-style-type: none;
}
ul#treelinks ul {
  padding-left: 0;
  list-style-type: none;
}
ul#treelinks li {
  background-repeat: no-repeat;
  background-position: top left;
  line-height: 16px;
  padding-left: 20px;
}
li.folder {
  background-image: url(/images/treetype/folder.png);
}
li.anchor {
  background-image: url(/images/treetype/anchor.png);
}
li.page {
  background-image: url(/images/treetype/file.png);
}
