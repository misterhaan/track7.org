<?
  require_once dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for disc golf rounds page                                  *
\******************************************************************************/

table.data {
  font-size: .8em;
}
tr.par td {
  text-align: center;
  background-color: #<?=HEADLIGHT; ?>;
  padding: .1em .3em;
}
tbody th {
  background-color: #<?=HEADLIGHT; ?>;
  color: #000000;
  font-weight: normal;
}
tbody td {
  text-align: center;
}
fieldset#roundnotes * {
  display: block;
}
fieldset#roundnotes label {
  margin: 0 .3em;
}
