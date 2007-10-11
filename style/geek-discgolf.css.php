<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style disc golf pages                                    <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#parlist tbody td {
  width: 1.9em;
}
div#parlist tbody th {
  font-weight: normal;
  text-align: left;
  background-color: #<?=LIGHT; ?>;
  color: #000000;
  border: 1px solid #ffffff;
}

h2 span.options {
  font-weight: normal;
  font-size: .6em;
  margin-left: 1.5em;
  vertical-align: middle;
}

td.minor {
  font-size: .8em;
}

table#parfields {
  width: 1%;
}
table#parfields tr th {
  border: none;
  text-align: center;
}
table#parfields tr td {
  border: none;
  padding: 1px 0 3px;
}
table#parfields tr td input {
  text-align: right;
}
