<?
  require_once dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style common to the entire site                                  *
\******************************************************************************/


/* =============================================================[ general ]== */

a {
  text-decoration: none;
}
div#content a {
  font-weight: bold;
}
a:link {
  color: #<?=LINKDARK; ?>;
}
a:visited {
  color: #<?=LINKVERYDARK; ?>;
}
a:link:hover,
  a:visited:hover {
  background-color: #<?=LINKLIGHT; ?>;
}

p {
  margin: .5em;
  line-height: 1.3em;
}

p.info,
p.error {
  padding: 3px 6px 3px 26px;
  background-repeat: no-repeat;
  background-position: 4px 4px;
  min-height: 18px;
  -khtml-border-radius: 9px;
  -webkit-border-radius: 9px;
  -moz-border-radius: 9px;
  border-radius: 9px;
}
p.info {
  border: 1px solid #<?=BGMEDIUM; ?>;
  background-color: #<?=BGLIGHT; ?>;
  background-image: url(http://www.track7.org/style/info.png);
}
p.error {
  border: 1px solid #<?=ERRORDARK; ?>;
  background-color: #<?=ERRORMEDIUM; ?>;
  color: #000000;
  background-image: url(http://www.track7.org/style/error.png);
}


/* ==============================================================[ tables ]== */

table.columns th {
  text-align: right;
  padding: 1px .5em;
  vertical-align: top;
  background-color: #<?=BGLIGHT; ?>;
  border-right: 1px solid #<?=BGMEDIUM; ?>;
  border-top: 1px dotted #<?=BGMEDIUM; ?>;
  color: #000000;
  font-weight: normal;
}
table.columns tr.firstchild th,
table.columns tr:first-child th {
  border-top: none;
}
table.columns td {
  padding: 1px .5em;
}

table.data thead th {
  text-align: center;
  padding: .1em .5em;
  border: 1px solid #<?=HEADMEDIUM; ?>;
  background-color: #<?=HEADLIGHT; ?>;
  color: #000000;
}
table.data tbody td {
  padding: .1em .3em;
  background-color: #<?=BGVERYLIGHT; ?>;
  border: 1px solid #ffffff;
}


/* ===============================================================[ forms ]== */

form {
  margin: 0 .5em;
}

/* hide the spam trap fields from people with css */
div#content form input,
div#content form textarea {
  display: none;
}
/* display the actual fields again */
div#content form table input,
div#content form table textarea {
  display: inline;
}

fieldset {
  padding: .7em;
  margin: 1em 0;
  border: 1px solid #<?=HEADDARK; ?>;
  -webkit-border-radius: .7em;
  border-radius: .7em;
  background-color: #ffffff;
}


/* ==============================================================[ header ]== */

img#punkhead {
  margin-left: 3px;
  width: 78px;
  height: 100px;
  vertical-align: bottom;
}
img#t7head {
  margin-top: 15px;
  margin-bottom: 6px;
  width: 133px;
  height: 79px;
  vertical-align: bottom;
}


/* ===============================================================[ login ]== */


div#loginmask {
  background-color: #000000;
  opacity: .7;
  width: 100%;
  height: 100%;
  position: fixed;
  top: 0;
  left: 0;
}
form#loginform {
  position: fixed;
  left: 0;
  top: 50%;
  margin: 0;
  margin-top: -7.5em;
  width: 100%;
}
form#loginform fieldset {
  background-color: #ffffff;
  margin: 0 auto;
  width: 20em;
}

form#loginform input[type="submit"] {
  margin-top: .45em;
}


/* ===============================================================[ title ]== */

h1 {
  margin: 0;
  text-align: center;
  color: #000000;
  font-size: 1.6em;
}
h1 span.sub {
  display: block;
  font-weight: normal;
  font-size: .7em;
  color: #<?=TEXT; ?>;
}


/* ==============================================================[ layout ]== */

html {
  font-family: dejavu sans, bitstream vera sans, corbel, verdana, arial, sans-serif;
  margin: 0;
  padding: 0;
  font-size: 76%;
}
body {
  margin: 0;
  padding: 0;
  background: #<?=BGLIGHT; ?> url(http://www.track7.org/style/body-tile.png) repeat-x center top;
  color: #<?=TEXT; ?>;
}
div#head {
  border-bottom: 1px solid #000000;
}
div#body {
  width: 100%;
  float: right;
  margin-left: -13.5em;
}
div#content {
  background-color: #ffffff;
  margin: 0 .5em;
  border: 1px solid #<?=BGDARK; ?>;
  border-top: none;
  padding-bottom: 1.5em;
}
div#foot {
  clear: both;
  font-size: .8em;
  padding: .5em .75em .5em;
}
