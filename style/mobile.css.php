<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style to use most of the width of the browser                    *
\******************************************************************************/

/* ==============================================================[ layout ]== */
html {
  background: none;
  -webkit-text-size-adjust: none;
}
body {
  max-width: none;
  border: none;
  margin: 0;
}
div.dynamic {
  clear: both;
  width: 24.81em;
}
div#foot {
  margin-top: 1em;
  padding: .5em .75em;
  color: #000000;
  background: #<?=BGMEDIUM; ?>;
}
div#copyright {
  margin-top: 0;
  float: none;
}
div#copyright a:link,
div#copyright a:visited {
  color: #000000;
  border-bottom: 1px dotted #000000;
}

/* ===============================================================[ forms ]== */
input[type="text"],
input[type="password"],
input[type="file"],
textarea,
select {
  width: 100%;
}

/* ============================================================[ comments ]== */
div#usercomments {
  margin: 2em 0 1em;
  border: none;
}
div#usercomments h2 {
  margin: 1em 1.2em .5em;
  -khtml-border-radius: .5em;
  -webkit-border-radius: .5em;
  -moz-border-radius: .5em;
  border-radius: .5em;
}
