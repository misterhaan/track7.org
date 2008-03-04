<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for user profile edit page                         <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
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
  background-color: #dddddd;
  border: 1px solid #aaaaaa;
  border-bottom: none;
}
ul.tabs li.active a {
  border: 1px solid #<?=DARK ?>;
  padding-bottom: 3px;
  border-bottom: none;
  background-color: #ffffff;
}
ul.tabs li.active a {
  color: #000000;
}
ul.tabs li a:hover {
  background-color: #<?=DARK; ?>;
  border-color: #<?=DARK; ?>;
  color: #ffffff;
}
div.tabbed {
  border: 1px solid #<?=DARK; ?>;
  margin: 0 2em 2em;
  padding: 1em 1.5em;
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
table#colorchoice td {
  padding: 0;
}
table#colorchoice label {
  display: block;
  margin: 0 7px 7px 0;
  border: 1px solid #<?=DARKMEDGREY; ?>;
  text-align: center;
  width: 212px;
  padding: 0;
}
table#colorchoice img {
  display: block;
  margin: 5px;
  border: 1px solid #<?=DARKMEDGREY; ?>;
}
textarea#fldsignature {
  height: 3.5em;
}
