<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  require_once 'auFile.php';

  $page->Start('ti8x programs - the analog underground', 'calculator programs', '', '', array('science', 'math', 'misc'));
?>
      <p class="note">zip files contain standard .85? or .86? filetypes used by all interface utilities</p>

<?
  $page->Heading('science', 'science');
?>
      <ul>
        <li>
          <a href="/files/analogu/ti8x/alphabet.zip">Alphabet</a> (<?=auFile::Size('ti8x/alphabet.zip'); ?>)
          <div>a physics program which helps you look up all the equations from my high school algebra-based physics book</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/clc_r4p.zip">ChemLemm Companion</a> (<?=auFile::Size('ti8x/clc_r4p.zip'); ?>) &nbsp; [<a href="/files/analogu/ti8x/clc_text.zip">text only version</a> (<?=auFile::Size("ti8x/clc_text.zip"); ?>)]
          <div>a chemistry program that does mole conversions and shows its work</div>
        </li>
      </ul>

<?
  $page->Heading('math', 'math');
?>
      <ul>
        <li>
          <a href="/files/analogu/ti8x/dscrm.zip">Dscrm</a> (<?=auFile::Size('ti8x/dscrm.zip'); ?>)
          <div>a program to find a discriminant of a quadratic equation</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/graphing.zip">Graphing</a> (<?=auFile::Size('ti8x/graphing.zip'); ?>)
          <div>a program which will give you information on a sine or cosine function</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/revolver.zip">Revolver</a> (<?=auFile::Size('ti8x/revolver.zip'); ?>)
          <div>a program to help with those calculus problems where a curve is revolved around an axis</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/syndiv.zip">Synthetic Divider</a> (<?=auFile::Size('ti8x/syndiv.zip'); ?>)
          <div>a program which performs synthetic division</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/trap.zip">Trap</a> (<?=auFile::Size('ti8x/trap.zip'); ?>)
          <div>a program that estimates the value of an integral using the trapezoid method</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/trig.zip">Trig</a> (<?=auFile::Size('ti8x/trig.zip'); ?>)
          <div>a program that gives the values of all six trigonometry functions when given the coordinates for a point</div>
        </li>
      </ul>

<?
  $page->Heading('misc', 'misc');
?>
      <ul>
        <li>
          <a href="/files/analogu/ti8x/easketch.zip">EaSketch</a> <abbr title="for the ti86 only">*</abbr> (<?=auFile::Size('ti8x/easketch.zip'); ?>)
          <div>an etch-a-sketch program, just for fun</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/vectr.zip">Vectr</a> (<?=auFile::Size('ti8x/vectr.zip'); ?>)
          <div>a program to do basic math on vectors, taking angles as number of degrees 'east of north' or similar</div>
        </li>
        <li>
          <a href="/files/analogu/ti8x/vops.zip">Vops</a> <abbr title="for the ti86 only">*</abbr> (<?=auFile::Size('ti8x/vops.zip'); ?>)
          <div>a program to perform more advanced operations on vectors</div>
        </li>
      </ul>

      <p class="note">*these programs for TI·86 only</p>

<?
  $page->End();
?>
