<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> printing-specific style information                                     *
\******************************************************************************/

div#main {
  color: #000000;
}
div#body {
  background-image: none;
  float: none;
  margin: 0;
}
div#content {
  margin: 0;
  padding: 0;
}

h2 {
  border-right: none;
}

div#welcome,
ul#sectnav,
div#dynamic,
div#lost,
div#usercomments form {
  display: none;
}
