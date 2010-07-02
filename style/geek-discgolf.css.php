<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style disc golf pages                                            *
\******************************************************************************/

div#parlist tbody td {
  width: 1.9em;
}
div#parlist tbody th {
  font-weight: normal;
  text-align: left;
  background-color: #<?=HEADLIGHT; ?>;
  color: #000000;
  padding-left: .3em;
  padding-right: .3em;
  border: 1px solid #ffffff;
}

h2 ul.elements {
  display: inline;
  font-weight: normal;
  font-size: .6em;
  margin-left: 1.5em;
  vertical-align: middle;
}
h2 ul.elements li {
  padding: 0 .2em;
}
div#content h2 ul.elements li a {
  font-weight: bold;
}

td.minor {
  font-size: .8em;
}
table.text tbody tr.comments td {
  border-top: none;
  color: #<?=DISABLED; ?>;
  padding-left: 1em;
}
fieldset#scoreset table {
  margin: .5em 0;
}
fieldset#scoreset th {
  background-color: #<?=BGLIGHT; ?>;
  padding: 0 .2em;
  min-width: 1.6em;
}
fieldset#scoreset thead th {
  border: 1px solid #<?=BGMEDIUM; ?>;
}
fieldset#scoreset tbody th {
  font-weight: normal;
}
fieldset#scoreset tbody td {
  border-bottom: 1px solid #<?=BGLIGHT; ?>;
  text-align: center;
}
fieldset#scoreset tbody td input[type="text"] {
  width: 1em;
  display: block;
  text-align: center;
  margin: 0 auto;
}
fieldset#scoreset tbody input.partner,
fieldset#scoreset tbody span.partner {
  display: none;
}
fieldset#scoreset.partners tbody input.partner,
fieldset#scoreset.partners tbody span.partner {
  display: block;
}

fieldset#scoreset tfoot td {
  text-align: center;
}

fieldset#scoreset tfoot td input {
  font-size: .8em;
  font-weight: normal;
}

textarea#fldcomments {
  height: 6em;
}

div#content a#changecourse {
  font-size: .8em;
  font-weight: normal;
  margin-left: 1em;
}

th.player {
  text-align: left;
}
tr.par th.player {
  text-align: right;
}
