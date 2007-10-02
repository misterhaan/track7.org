<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> '<?=STYLE; ?>' layout style for cd compilations page                           <? for($i = strlen(STYLE); $i < 5; $i++) echo ' '; ?>*
\******************************************************************************/

div#covers {
  margin: 1em auto;
  width: 561px;
}
div#covers a {
  display: block;
  width: 175px;
  height: 175px;
  float: left;
  margin: 5px;
  border: 1px solid #<?=LIGHTMEDGREY; ?>;
}
div#covers a:hover {
  border: 1px solid #000000;
}
div#covers a img {
  width: 175px;
  height: 175px;
}

h1 img.cd {
  margin-top: .5em;
  border: 1px solid #000000;
  width: 175px;
  height: 175px;
}
table#tracklist {
  margin: 1em auto;
}
table#tracklist td.title,
table#tracklist td.time,
table#tracklist td.total {
  text-align: right;
}
td.total {
  border-top: 1px solid #<?=TEXT; ?>;
}
