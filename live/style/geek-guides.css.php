<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for guide pages                                            *
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
  margin: 1em 0 0;
  background-image: none;
}
dl.guides dd {
  margin: 0;
}
div.guideinfo {
  background-color: #<?=BGLIGHT; ?>;
  padding: .1em .5em;
  margin: .2em 0;
  font-size: .8em;
  -khtml-border-radius: .5em;
  -webkit-border-radius: .5em;
  -moz-border-radius: .5em;
  border-radius: .5em;
}
div.guideinfo span {
  margin: 0 1.5em 0 0;
}
div#content div.guideinfo a {
  font-weight: normal;
}
