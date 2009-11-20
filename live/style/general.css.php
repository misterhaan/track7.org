<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
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
a img {
  border: none;
}
a.feed {
  margin-left: .5em;
}
a.feed img {
  width: 12px;
  height: 12px;
  vertical-align: middle;
}
a.feed:hover {
  background: none;
}

h2 {
  clear: both;
  font-size: 1.2em;
  margin: 1em 1.2em .5em;
  padding: 0 .5em;
  background-color: #<?=HEADMEDIUM; ?>;
  color: #000000;
  -khtml-border-radius: .5em;
  -webkit-border-radius: .5em;
  -moz-border-radius: .5em;
  border-radius: .5em;
}
h3 {
  clear: both;
  font-size: 1.1em;
  margin: 1em 1.75em .7em;
  padding: 0 .25em;
  color: #000000;
  border-bottom: 2px solid #<?=HEADMEDIUM; ?>;
  line-height: .8em;
}
div.minorline,
hr.minor {
  background-position: center center;
  clear: both;
  border: none;
  margin: 0;
  height: 27px;
  background-image: url(/style/lightknot.png);
}
p {
  margin: .5em 2em;
  line-height: 1.3em;
}
div#content p {
  text-align: justify;
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
  background-image: url(/style/info.png);
}
p.error {
  border: 1px solid #<?=ERRORDARK; ?>;
  background-color: #<?=ERRORMEDIUM; ?>;
  color: #000000;
  background-image: url(/style/error.png);
}

img.icon {
  float: left;
  padding-left: 2em;
  width: 32px;
  height: 32px;
}
p.iconned {
  padding-left: 32px;
  margin-left: 2.5em;
}

div.preview {
  float: right;
  margin-top: .3em;
  padding-right: 2.5em;
  width: 152px;
  text-align: center;
}
div.preview img {
  border: 1px solid #000000;
  margin-bottom: .5em;
  width: 150px;
}
div.preview div,
div.thumb div {
  font-style: italic;
  font-size: .8em;
}
p.previewed {
  padding-right: 152px;
  margin-right: 4em;
}

.seemore {
  text-align: center;
  font-style: italic;
}
div#content .seemore a {
  font-weight: normal;
}

.when {
  float: right;
  font-style: italic;
}
h2 .when {
  font-size: .65em;
  font-weight: normal;
  padding-top: .5em;
}
.note {
  font-size: .8em;
  text-align: center;
  font-style: italic;
}
.detail {
  font-size: .8em;
}
.detail a {
  font-weight: normal;
}

code, samp {
  font-family: dejavu sans mono, bitstream vera sans mono, consolas, lucida console, monospace;
}
samp {
  display: block;
  margin: .5em 3em;
  padding: .2em .5em;
  white-space: nowrap;
  overflow: auto;
  text-align: left;
  max-height: 20em;
  border: 1px solid #<?=BGLIGHT; ?>;
  background-color: #<?=BGVERYLIGHT; ?>;
}

/* =============================================================[ ratings ]== */

div.rating {
  text-align: center;
  white-space: nowrap;
}
div.rating a.votepartner {
  background-color: #<?=LINKLIGHT; ?>;
}
div.rating img {
  vertical-align: middle;
}


/* ==============================================================[ t7code ]== */

cite {
  display: block;
  margin: .5em 1.5em -.5em;
}
q:before {content: '';}
q:after {content: '';}
q {
  display: block;
  margin: .5em 1em;
  border: 1px solid #<?=BGMEDIUM; ?>;
  padding: .2em .5em;
  -khtml-border-radius: .75em;
  -webkit-border-radius: .75em;
  -moz-border-radius: .75em;
  border-radius: .75em;
}
q q,
q q q q,
q q q q q q {
  background-color: #ffffff;
}
q,
q q q,
q q q q q,
q q q q q q q {
  background-color: #<?=BGLIGHT; ?>;
}


/* ===============================================================[ lists ]== */

ol,
ul {
  margin: .5em 0 0 2em;
  padding: 0 3em;
}
ol ol,
ul ul,
ol ul,
ul ol {
  margin: 0;
  padding: 0 0 0 2em;
}
dl {
  margin: .5em 2em;
  padding-left: 10px;
}
dt {
  margin-left: -10px;
  padding-left: 10px;
  background-image: url(/style/dt-bullet.png);
  background-position: 0 5px;
  background-repeat: no-repeat;
}
dd {
  margin: 0 1em .5em;
}


/* ==============================================================[ tables ]== */

td.number {
  text-align: right;
}

table.columns,
table.text,
table.data {
  margin: .5em 2em;
}
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
table.columns td th {
  border-right: none;
}

table.text thead th {
  text-align: center;
  line-height: 80%;
  border-bottom: 1px solid #<?=BGMEDIUM; ?>;
  color: #000000;
  background-color: #<?=BGLIGHT; ?>;
  padding-left: 1em;
}
table.text thead.minor th {
  font-size: .8em;
  font-weight: normal;
  padding-left: 1.25em;
}
table.text thead th:first-child,
table.text thead.minor th:first-child {
  padding-left: 0;
}
table.text tbody td {
  padding-left: 1em;
  border-top: 1px dashed #<?=BGLIGHT; ?>;
}
table.text tbody tr td:first-child {
  padding-left: 0;
}
table.text tbody tr.firstchild td,
table.text tbody tr:first-child td {
  border-top: none;
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
table.data td.clear {
  background-color: transparent;
  border: none;
}
table.data tbody td.clear a img {
  display: block;
}


/* ==============================================================[ forms ]== */

form {
  margin: 1em 2em;
}
form table.columns {
  margin: 0;
  width: 100%;
}
form table.columns th {
  width: 1%;
  white-space: nowrap;
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
  padding: 1em;
  margin: 1em 0;
  border: 1px solid #<?=HEADDARK; ?>;
  -khtml-border-radius: 1em;
  -webkit-border-radius: 1em;
  -moz-border-radius: 1em;
  border-radius: 1em;
}
fieldset legend {
  padding: 0 .5em;
  border-left: 3px solid #<?=HEADDARK; ?>;
  border-right: 3px solid #<?=HEADDARK; ?>;
  background-color: #<?=HEADMEDIUM; ?>;
  color: #000000;
  font-weight: bold;
}

form tr.required th {
  font-weight: bold;
}
label[title] {
  cursor: help;
}

input[type="text"],
input[type="password"],
input[type="file"],
textarea,
select {
  font-family: dejavu sans, bitstream vera sans, corbel, verdana, arial, sans-serif;
  font-size: 1em;
  border: 1px solid #<?=BGLIGHT; ?>;
  color: #<?=TEXT; ?>;
  background-color: #ffffff;
  padding: .1em .2em;
}
textarea {
  width: 100%;
}
input[type="text"]:focus,
input[type="password"]:focus,
textarea:focus,
select:focus {
  border-color: #<?=BGDARK; ?>;
  background-color: #<?=BGVERYLIGHT; ?>;
}
input.checkbox {
  vertical-align: middle;
}

input[type="submit"] {
  border: 1px solid #<?=BGDARK; ?>;
  background-color: #<?=BGLIGHT; ?>;
  color: #<?=LINKDARK; ?>;
  font-size: 1em;  /* buttons don't seem to inherit font size without something like this */
  font-weight: bold;
  -khtml-border-radius: .5em;
  -webkit-border-radius: .5em;
  -moz-border-radius: .5em;
  border-radius: .5em;
}
input[type="submit"]:hover {
  cursor: pointer;
  border-color: #000000;
  background-color: #<?=LINKDARK; ?>;
  color: #ffffff;
}
input[type="submit"][disabled],
input[type="submit"][disabled]:hover {
  cursor: default;
  border-color: #<?=BGLIGHT; ?>;
  color: #<?=BGMEDIUM; ?>;
  background-color: #<?=BGLIGHT; ?>;
}


/* ===========================================================[ pagelinks ]== */

div.pagelinks {
  text-align: center;
  margin: .5em 4em .7em;
  clear: both;
}
div.pagelinks span.active {
  padding: 0 .2em;
  border: 1px solid #<?=LINKDARK; ?>;
}
div.pagelinks a {
  padding: 0 .2em;
}


/* =======================================================[ related links ]== */

dl.relatedlinks {
  padding-left: 1em;
  margin: 0;
}
dl.relatedlinks dt {
  clear: left;
  float: left;
  margin: 0;
  padding: .3em .5em 0 2em;
  background-image: none;
}
dl.relatedlinks dd {
  font-size: .8em;
  margin: .5em 2.5em;
  padding-bottom: .7em;
  text-align: justify;
}


/* ============================================================[ comments ]== */

div#usercomments {
  margin: 2em 5em 1em;
  border: 1px solid #<?=HEADMEDIUM; ?>;
  clear: both;
  -moz-border-radius: .8em;
  border-radius: .8em;
}
div#usercomments h2 {
  margin: 0;
  border: none;
  -moz-border-radius-bottomleft: 0;
  -moz-border-radius-bottomright: 0;
  border-radius-bottomleft: 0;
  border-radius-bottomright: 0;
}

table.post {
  margin: 1.5em 2em;
}
table.post td {
  vertical-align: top;
  padding: 0;
}
table.post td.userinfo {
  padding: .3em .5em;
  height: 100%;
  background-color: #<?=BGLIGHT; ?>;
  border-right: 1px solid #<?=BGMEDIUM; ?>;
}
table.post td.userinfo img.avatar {
  display: block;
}
table.post td.userinfo div {
  font-size: .8em;
}
table.post div.head {
  font-size: .8em;
  padding: 0 .5em;
  border-bottom: 1px dashed #<?=BGMEDIUM; ?>;
}
table.post p {
  margin: .5em 1em;
}
table.post div.foot {
  font-size: .8em;
  padding: 1px .5em 0;
  border-top: 1px dashed #<?=BGMEDIUM; ?>;
  text-align: right;
  line-height: 16px;
  height: 16px;
}
table.post div.foot div.userlinks {
  float: left;
  padding-right: 1.5em;
}
table.post div.foot a {
  margin-left: .5em;
  vertical-align: middle;
}
table.post div.foot div.userlinks a {
  margin-left: 0;
  margin-right: .5em;
}
table.post div.foot a img {
  vertical-align: middle;
}


/* ===========================================================[ tag cloud ]== */

div.tagcloud {
  margin: .5em 2em;
  padding: 3px 6px 3px 26px;
  background: #<?=BGLIGHT; ?> url(/style/tag.png) no-repeat 4px 4px;
  min-height: 18px;
  border: 1px solid #<?=BGMEDIUM; ?>;
  -khtml-border-radius: 9px;
  -webkit-border-radius: 9px;
  -moz-border-radius: 9px;
  border-radius: 9px;
}
div#content div.tagcloud a {
  margin: 0 .2em;
  font-weight: normal;
  vertical-align: middle;
}
div.tagcloud a.tagfew {
  font-size: .8em;
  line-height: 2.5em;
}
div.tagcloud a.tagsome {
  line-height: 2em;
}
div.tagcloud a.tagmany {
  font-size: 1.2em;
  line-height: 1.538em;
}
div.tagcloud a.taglots {
  font-size: 1.5em;
  line-height: 1.33em;
}
div.tagcloud a.tagtons {
  font-size: 2em;
  line-height: 1em;
}


/* ==============================================================[ layout ]== */

html {
  font-family: dejavu sans, bitstream vera sans, corbel, verdana, arial, sans-serif;
  margin: 0;
  padding: 0;
  background: #<?=BGDARK; ?> url(/style/html-tile.png) repeat-x fixed center top;
  font-size: 76%;
}
body {
  margin: 0 auto;
  padding: 0;
  background: #<?=BGLIGHT; ?> url(/style/body-tile.png) repeat-x center top;
  max-width: 74.5em;
  border: 1px solid #000000;
  color: #<?=TEXT; ?>;
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
div#dynamic {
  clear: both;
}
br.clear {
  clear: both;
  display: block;
  height: 0;
}
div#foot {
  clear: both;
  font-size: .8em;
  padding: 20px .75em .5em;
  background: url(/style/lightknot.png) repeat-x top center;
}


/* ==============================================================[ header ]== */
div#head {
  vertical-align: bottom;
  height: 100px;
  white-space: nowrap;
  border-bottom: 1px solid #000000;
}

div#welcome {
  float: right;
  height: 100px;
  font-size: .8em;
  background-color: #<?=BGLIGHT; ?>;
  border-left: 1px solid #000000;
  overflow: auto;
}
div#welcome p {
  margin: 0 .5em;
  line-height: 18px;
  text-align: center;
}
div#welcome p.welcomeguest {
  width: 10em;
  margin: .75em 0;
  padding-left: 1em;
  white-space: normal;
  line-height: 1.3em;
  text-align: left;
}
div#welcome div.avatar {
  text-align: center;
}
div#welcome div.avatar img {
  line-height: 64px;
  vertical-align: middle;
}

div#navhelp {
  float: right;
  margin-top: .5em;
	margin-right: 1.5em;
  text-align: right;
  font-size: .8em;
}
div#navhelp a {
  margin-left: 1.25em;
}
div#navhelp a:link:hover,
div#navhelp a:visited:hover {
  border-bottom: 1px dotted #<?=LINKDARK; ?>;
}

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
ul#sectnav {
  padding: 0;
  list-style-type: none;
  display: inline;
  margin: 0;
  position: relative;
  top: -1px;
  vertical-align: bottom;
}
ul#sectnav li {
  display: inline;
}
ul#sectnav li a {
  padding: .15em .3em 1px;
  font-weight: bold;
  border: 1px solid #<?=BGDARK; ?>;
  border-bottom: none;
  background-color: #<?=BGLIGHT; ?>;
  -khtml-border-radius-topleft: .5em;
  -khtml-border-radius-topright: .5em;
  -webkit-border-radius-topleft: .5em;
  -webkit-border-radius-topright: .5em;
  -moz-border-radius-topleft: .5em;
  -moz-border-radius-topright: .5em;
  border-radius-topleft: .5em;
  border-radius-topright: .5em;
}
ul#sectnav li a:hover {
  border-color: #000000;
  background-color: #<?=LINKDARK; ?>;
  color: #ffffff;
}
ul#sectnav li.active a {
  padding-bottom: 2px;
  border: 1px solid #000000;
  border-bottom: none;
  background-color: #ffffff;
  color: #2c2c2c;
}
ul#sectnav li.active a:hover {
  background-color: #<?=LINKLIGHT; ?>;
  color: #000000;
}
div#location {
  font-size: .9em;
  padding-left: .4em;
  padding-top: .1em;
}
div#location a {
  font-weight: normal;
}


/* ===============================================================[ login ]== */


div#loginmask {
  display: none;
  background-color: #000000;
  opacity: .7;
  width: 100%;
  height: 100%;
  position: fixed;
  top: 0;
  left: 0;
}
form#loginform {
  display: none;
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

div#title {
  padding-bottom: 30px;
  background-image: url(/style/heavyknot.png);
  background-position: bottom center;
  background-repeat: repeat-x;
}
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

ul.elements {
  font-size: .8em;
  text-align: center;
  list-style-type: none;
  margin: 0;
  margin-bottom: .7em;
  padding: 0;
}
ul.elements:before {
  content: '[';
}
ul.elements:after {
  content: ']';
}
ul.elements li {
  display: inline;
}
ul.elements li:before {
  content: '| ';
  color: #<?=TEXT; ?>;
}
ul.elements li:first-child:before {
  content: '';
}
div#content ul.elements a {
  font-weight: normal;
}


/* =====================================================[ dynamic content ]== */

div#dynamic {
  width: 62.01em;
  margin: 0 auto;
}
div.dynsec {
  float: left;
  width: 12em;
  padding: 0 .2em;
}

div#dynamic h2 {
  margin: 1em 0 .5em;
  font-size: 1em;
  border: 1px solid #<?=HEADDARK; ?>;
}
div#dynamic ul {
  font-size: .8em;
  padding-left: .3em;
  padding-right: 1em;
}
div#dynamic table,
div#dynamic p {
  font-size: .8em;
  margin: .3em 1em;
}
div#dynamic a:link:hover,
div#dynamic a:visited:hover {
  border-bottom: 1px dotted #<?=LINKDARK; ?>;
}

div#poweredby ul {
  list-style-type: none;
  margin: 1em 0;
  padding: 0;
}
div#poweredby li {
  width: 80px;
  margin: .25em auto;
  padding: 0;
  text-align: center;
}
div#poweredby li a {
  display: block;
  border: none;
}
div#dynamic div#poweredby a:hover {
  border: none;
}
div#poweredby img {
  width: 80px;
  height: 15px;
  display: block;
}


/* ==============================================================[ footer ]== */

div#copyright {
  margin-top: -1.2em;
  float: right;
}