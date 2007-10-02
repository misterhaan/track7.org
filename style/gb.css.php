<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for guestbook                                      <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div.gbintro {
  margin: 0 2em;
}
div.gbintro div.gbtime {
  text-align: right;
  font-size: .8em;
  margin-bottom: -1em;
}
div.gbintro div.gbnum {
  border-bottom: 1px solid #<?=TEXT; ?>;
  margin-bottom: .5em;
}
div.gbintro div.gbnum:before {
  content: "[";
}
div.gbintro div.gbnum:after {
  content: "]";
}
span.response {
  color: #<?=DARK; ?>;
}
div.comments,
p.comments {
  margin: .5em 4em;
  overflow: hidden;
  color: #<?=DARK; ?>;
}
div.madlib {
  margin: .5em 2em;
}
div.excerpt {
  font-size: .8em;
  text-align: right;
  padding-right: 1.8em;
  font-style: italic;
}
div.excerpt:before {
  content: "(";
}
div.excerpt:after {
  content: ")";
}
