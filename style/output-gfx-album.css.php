<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for photo album pages                              <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

img#photo {
  display: block;
  margin: .5em auto;
  border: 1px solid #000000;
}

object#photo {
  display: block;
  margin: .5em auto;
  border: 1px solid #000000;
  width: 480px;
  height: 385px;
}

object#photo embed {
  display: block;
  width: 480px;
  height: 385px;
}
