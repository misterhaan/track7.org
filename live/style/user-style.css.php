<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for user messages page                             <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

ul#colorchoice {
  list-style-type: none;
  width: 444px;
  margin: .5em auto;
}
ul#colorchoice li {
  display: block;
  float: left;
  margin: 0 7px 7px 0;
  width: 214px;
}
ul#colorchoice a {
  display: block;
  border: 1px solid #<?=MEDIUM; ?>;
  text-align: center;
  padding: 5px;
}
ul#colorchoice a:hover {
  background-color: #<?=LIGHT; ?>;
}
ul#colorchoice a img {
  display: block;
  border: 1px solid #<?=MEDIUM; ?>;
  margin: 0 auto 5px;
  width: 200px;
  height: 114px;
}
ul#colorchoice a:hover,
ul#colorchoice a:hover img {
  border-color: #<?=LINKTEXT; ?>;
}
