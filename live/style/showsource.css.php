<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for showing php source                             <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div.source {
  font-family: bitstream vera sans mono, lucida console, monospace;
  margin: 1em 2%;
  width: 96%;
  height: 30em;
  overflow: auto;
  border: 1px dashed #<?=DARK; ?>;
  background-color: #<?=LIGHTGREY; ?>;
}
div.source ol {
  padding-left: 1.5em;
}
div.source li {
  white-space: nowrap;
}
div.source span.comment {
  color: #ff8000;
}
div.source span.default {
  color: #0000bb;
}
div.source span.html {
  color: #000000;
}
div.source span.keyword {
  color: #007700;
}
div.source span.string {
  color: #dd0000;
}