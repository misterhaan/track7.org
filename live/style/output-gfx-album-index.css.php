<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for photo album thumbnail pages                    <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

ul#photos {
  margin: .5em 1.5em;
  padding: 0;
  text-align: center;
}
ul#photos li {
  display: -moz-inline-box;
  display: inline-block;
  margin: 0;
  padding: 0;
  width: 170px;
}
ul#photos a {
  display: block;
  text-align: center;
  border: 1px solid #ffffff;
  padding: 4px;
  margin: 1px;
}
ul#photos a:hover {
  border: 1px solid #<?=MEDIUM; ?>;
}

span.photopreview {
  display: table-cell;
  vertical-align: middle;
  width: 158px;
  height: 158px;
}
span.photopreview img {
  display: block;
  margin: auto;
  padding: 3px;
  background-color: #ffffff;
  border: 1px solid #<?=LINK; ?>;
}
span.caption {
  display: block;
  margin-top: .3em;
}