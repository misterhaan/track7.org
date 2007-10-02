<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for lego models page                               <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div.thumb {
  float: left;
  margin-top: .4em;
  padding-left: 2.5em;
  padding-bottom: .5em;
  width: 152px;
  text-align: center;
}
div.thumb div {
  font-style: italic;
  font-size: .8em;
}
div.thumb a.img {
  border: 1px solid #<?=LIGHTMEDGREY; ?>;
  margin: 0 0 .5em;
  padding: 0;
  display: block;
}
div.thumb a.img:hover {
  border: 1px solid #000000;
  margin: 0 0 .5em;
  padding: 0;
}
div.thumb a img {
  display: block;
  width: 150px;
}
div.thumbed {
  padding-left: 150px;
  margin-left: 3.5em;
}
