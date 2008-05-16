<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for user profile view page                         <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

table#userprofile th {
  white-space: nowrap;
}

ul#friends {
  margin: .5em 1.5em;
  padding: 0;
  text-align: center;
  list-style-type: none;
}
ul#friends li {
  display: -moz-inline-box;
  margin: 0.3em;
  padding: 0;
}
div.friend a.profile {
  display: block;
  padding: 5px 5px 0;
  margin-bottom: .5em;
}
div.friend a.profile img {
  display: block;
  margin: 0 auto .2em;
}
div.friend div.actions a img {
  vertical-align: middle;
}
