<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for guide view pages                               <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#guidetools {
  background-color: #<?=LIGHTGREY; ?>;
  border: 1px solid #<?=MEDGREY; ?>;
  -moz-border-radius: 1em;
  margin: 1.5em 2em;
}
div#guidetools ol,
div#guidetools p {
  margin: .5em 1em;
}
div#guidetools ol {
  padding: 0;
  list-style-type: none;
  line-height: 1.25em;
}