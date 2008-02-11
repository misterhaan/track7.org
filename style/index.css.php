<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for main index page                                <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#content p#randomquote {
	font-size: .9em;
	font-style: italic;
  text-align: center;
}
p#randomquote:before {
  content: '“';
  color: #<?=DARKGREY; ?>;
}
p#randomquote:after {
  content: '”';
  color: #<?=DARKGREY; ?>;
}
div#welcomeabout {
  float: left;
  width: 345px;
  padding-bottom: 1em;
}
div#features {
  margin-left: 345px;
}

div#features h2 {
  clear: none;
}
div#features dt {
  margin: 0;
  padding: 0;
  background-image: none;
}
div#features dt img.icon {
  padding-left: 0;
  margin-right: .5em;
}
div#features dd {
  margin-left: 32px;
  padding-left: .5em;
}

table#updates th {
  font-size: .8em;
  white-space: nowrap;
}
table#updates td {
  vertical-align: top;
  font-size: .9em;
}
table#updates td.type {
  padding-right: 0;
}
table#updates img {
  display: block;
  width: 16px;
  height: 16px;
}

div#content p.links {
  font-size: .8em;
  text-align: center;
}

p#shorturl {
  clear: both;
  margin-top: 2em;
  font-size: .8em;
  border-top: 1px dashed #<?=MEDIUM; ?>;
  padding: .2em .5em;
  margin-bottom: 0;
}
p#shorturl a {
  font-weight: normal;
}
