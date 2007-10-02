<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for pen vs. sword pages                            <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

p.story {
  text-indent: 2em;
  margin: 0 2em;
}
.indent {
  text-indent: 2em;
}

table.ready {
  margin: .5em 2em;
}
table.ready td {
  width: 33%;
  text-align: center;
  vertical-align: top;
}
table.ready td img {
  width: 141px;
  height: 155px;
}
div#main table.ready td p {
  margin: 0;
  padding: .2em .7em 1em;
  text-align: left;
}
