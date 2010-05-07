<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for photo album thumbnail pages                            *
\******************************************************************************/

div#sortoptions {
  text-align: right;
  margin: -2em 2.5em 0;
  font-size: .8em;
}
@media all and (max-width: 45em) {
  div#sortoptions {
    text-align: left;
    margin-top: 0;
  }
}

ul#photos {
  margin: .5em 1.5em;
  padding: 0;
  text-align: center;
}
ul#photos li {
  display: -moz-inline-box;
  display: inline-block;
  margin: 0;
  padding: 0;
  width: 170px;
}
ul#photos a {
  display: block;
  text-align: center;
  border: 1px solid #ffffff;
  padding: 4px;
  margin: 1px;
  -khtml-border-radius: 7px;
  -webkit-border-radius: 7px;
  -moz-border-radius: 7px;
  border-radius: 7px;
}
ul#photos a:hover {
  border: 1px solid #<?=LINKMEDIUM; ?>;
}

span.photopreview {
  display: table-cell;
  vertical-align: middle;
  width: 158px;
  height: 158px;
}
span.photopreview img {
  display: block;
  margin: auto;
  padding: 3px;
  background-color: #ffffff;
  border: 1px solid #<?=LINKDARK; ?>;
}
span.caption {
  display: block;
  margin-top: .3em;
}
