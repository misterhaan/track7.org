<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for the feeds page                                 <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

ul.feeds {
  margin-left: 0;
}
ul.feeds li {
  list-style-type: none;
}
ul.feeds a {
  background: url(/style/feed.png) no-repeat center left;
  padding-left: 20px;
}