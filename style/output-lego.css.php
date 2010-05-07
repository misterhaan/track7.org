<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/style.track7.php';
?>
/******************************************************************************\
 * track7 style sheet by misterhaan of http://www.track7.org/                 *
 *                                                                            *
 * -> layout style for lego models page                                       *
\******************************************************************************/

div.thumb {
  float: left;
  margin-top: .4em;
  padding-left: 2.5em;
  padding-bottom: .5em;
  width: 152px;
  text-align: center;
}
@media all and (max-width: 480px) {
  div.thumb {
    float: none;
    margin: .4em auto 1em;
    padding: 0;
  }
}
div.thumb div {
  font-style: italic;
  font-size: .8em;
}
div.thumb a.img {
  border: 1px solid #<?=LINKLIGHT; ?>;
  margin: 0 0 .5em;
  padding: 0;
  display: block;
}
div.thumb a.img:hover {
  border: 1px solid #<?=LINKDARK; ?>;
  margin: 0 0 .5em;
  padding: 0;
}
div.thumb a.img img {
  display: block;
  width: 150px;
}
div.thumbed {
  padding-left: 150px;
  margin-left: 3.5em;
}
@media all and (max-width: 480px) {
  div.thumbed {
    padding-left: 0;
    margin-left: 0;
  }
}

a.award {
  float: right;
  margin: 2em 2em 0 -1.5em;
}
@media all and (max-width: 480px) {
  a.award {
    float: none;
    display: block;
    width: 100px;
    margin: .5em auto;
  }
}
a.award img {
  display: block;
}