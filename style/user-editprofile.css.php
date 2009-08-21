<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for user profile edit page                                 *
\******************************************************************************/

ul.tabs {
  padding: 2px 2em;
  margin-top: 1em;
}
ul.tabs li {
  display: inline;
  list-style-type: none;
}
ul.tabs li a {
  padding: 2px .5em;
  background-color: #<?=BGLIGHT; ?>;
  border: 1px solid #<?=BGMEDIUM; ?>;
  border-bottom: none;
  -khtml-border-radius-topleft: .5em;
  -khtml-border-radius-topright: .5em;
  -webkit-border-radius-topleft: .5em;
  -webkit-border-radius-topright: .5em;
  -moz-border-radius-topleft: .5em;
  -moz-border-radius-topright: .5em;
  border-radius-topleft: .5em;
  border-radius-topright: .5em;
}
ul.tabs li.active a {
  border: 1px solid #<?=BGDARK ?>;
  padding-bottom: 3px;
  border-bottom: none;
  background-color: #ffffff;
}
ul.tabs li.active a {
  color: #000000;
}
ul.tabs li a:hover {
  background-color: #<?=LINKDARK; ?>;
  border-color: #000000;
  color: #ffffff;
}
div.tabbed {
  border: 1px solid #<?=BGDARK; ?>;
  margin: 0 2em 2em;
  padding: 1em 1.5em;
  -khtml-border-radius: .75em;
  -webkit-border-radius: .75em;
  -moz-border-radius: .75em;
  border-radius: .75em;
}
div.tabbed form {
  margin: 0;
}
div.tabbed form.textarea table.columns {
  padding: .5em 2em;
}
form img.avatar {
  float: left;
  padding: 0 .5em 0 0;
}
textarea#fldsignature {
  height: 3.5em;
}
textarea#fldgeekcode {
  height: 5.8em;
}