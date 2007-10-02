<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for neighborhood page                              <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#linkoptions {
  margin: .5em 4em;
}
div#linkoptions a.preview {
  float: left;
  margin: 0;
  padding: 0;
  border: none;
}
div#linkoptions a.preview img {
  display: block;
  width: 110px;
  height: 50px;
}
div#linkoptions code {
  font-family: lucida console, monospace;
  font-size: .8em;
  display: block;
  margin-left: 1.5em;
  text-align: left;
  padding: 2px;
  height: 44px;  /* 50px height of img, minus 1px *2 for border, minus 2px *2 for padding */
  overflow: auto;
  border: 1px solid #DARKMEDGREY;
}
