<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for forum pages                                    <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#content table.post div.head a {
  font-weight: normal;
  color: #<?=TEXT; ?>;
}
table.post div.head a:hover {
  border-bottom: none;
}
