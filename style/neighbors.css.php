<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for neighborhood page                                      *
\******************************************************************************/

div#linkoptions {
  margin: .5em 4em;
}
div#linkoptions a.preview {
  float: left;
  margin: 0 1em;
  padding: 0;
  border: none;
}
div#linkoptions a.preview img {
  display: block;
  width: 110px;
  height: 50px;
}
div#linkoptions samp {
  font-size: .8em;
  white-space: normal;
  padding: 2px .5em;
  height: 44px;  /* 50px height of img, minus 1px *2 for border, minus 2px *2 for padding */
}
