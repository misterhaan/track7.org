<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for guide submission pages                         <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div.guidepreview {
  margin: 0 3em .5em;
  border: 1px solid #000000;
}
h2.guidepreview {
  border: 1px solid #000000;
  border-bottom: none;
  margin: .5em 2.5em 0;
}
div.guidepreview h1 {
  padding-bottom: 30px;
  background-image: url(/style/heavyknot.png);
  background-repeat: repeat-x;
  background-position: bottom center;
}
