<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for forum thread pages                             <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

table.post p.history {
  font-size: .8em;
  font-style: italic;
  color: #<?=DARKGREY; ?>;
  margin: .625em 1.25em;
  padding: 0 .2em;
}
table.post p.signature {
  font-size: .8em;
  margin: .625em 1.25em;
  border-top: 1px solid #<?=LIGHTGREY; ?>;
  padding: .15em .5em 0;
  max-height: 3.5em;
  overflow: auto;
}
