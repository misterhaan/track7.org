<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for guide pages                                    <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#sortoptions {
  font-size: .8em;
  text-align: right;
  margin: 0 2.5em 1em;
}
div#sortoptions a {
  font-weight: normal;
}

dl.guides {
  margin: .5em 2em;
}
dl.guides dt {
  float: none;
  padding: 0;
  margin-top: .5em;
  background-image: none;
}
dl.guides dd {
  margin: 0 1em;
}
div.guideinfo {
  background-color: #<?=LIGHT; ?>;
  padding: .1em .5em;
  margin: .2em 0;
  font-size: .8em;
}
div.guideinfo span {
  margin: 0 1.5em 0 0;
}
div#content div.guideinfo a {
  font-weight: normal;
}