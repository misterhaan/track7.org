<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->start('computer hardware - geek', 'computer hardware', '', '', array('hecubus', 'tesla', 'galileo', 'egan'));

  $page->heading('hecubus', 'hecubus');
?>
      <table class="columns" cellspacing="0">
        <tr><th>name</th><td>hecubus</td></tr>
        <tr><th>purpose</th><td>file / ftp / http server</td></tr>
        <tr><th>mainboard</th><td>gigabyte ga-6vxe7+</td></tr>
        <tr><th>processor</th><td>intel pentium iii 933 MHz</td></tr>
        <tr><th>ram</th><td>256 MB pc133 sdram<br />128 MB pc133 sdram</td></tr>
        <tr><th>video</th><td>matrox mystique 220 pci (4 MB)</td></tr>
        <tr><th>network</th><td>belkin pci 10/100 ethernet</td></tr>
        <tr><th>ide0</th><td>maxtor 60 GB</td></tr>
        <tr><th>ide1</th><td>iomega zip 100</td></tr>
        <tr><th>ide2</th><td>western digital caviar 80 GB wd800bb</td></tr>
        <tr><th>ide3</th><td>western digital caviar 250 GB wd2500jb</td></tr>
        <tr><th>operating system</th><td>fedora core 4</td></tr>
      </table>

<?
  $page->heading('tesla', 'tesla');
?>
      <table class="columns" cellspacing="0">
        <tr><th>name</th><td>tesla</td></tr>
        <tr><th>purpose</th><td>main workstation / <abbr title="personal video recorder">pvr</abbr></td></tr>
        <tr><th>mainboard</th><td>gigabyte ga-945gzm-s2</td></tr>
        <tr><th>processor</th><td>2.66-ghz intel pentium d</td></tr>
        <tr><th>ram</th><td>1 GB pc2 5300 ddr2 sdram</td></tr>
        <tr><th>video</th><td>pny verto nvidia geforce 7300 gt (256 MB)</td></tr>
        <tr><th>audio</th><td>creative soundblaster live! value</td></tr>
        <tr><th>tuner</th><td>hauppage wintv-pvr 150</td></tr>
        <tr><th>network</th><td>realtek rtl8169 1000/100/10 ethernet</td></tr>
        <tr><th>fd0</th><td>generic 3½" floppy</td></tr>
        <tr><th>ide0</th><td>western digital caviar 80 GB wd800jb</td></tr>
        <tr><th>ide1</th><td>lg 8/4/24/4/2/16/12/32x dvd+/-rw</td></tr>
        <tr><th>operating system</th><td>microsoft windows xp professional, sp2<br />fedora core 6</td></tr>
        <tr><th>keyboard</th><td>logitech premium desktop</td></tr>
        <tr><th>mouse</th><td>logitech trackman wheel<br />logitech cordless optical</td></tr>
        <tr><th>monitor</th><td>samsung syncmaster 204b</td></tr>
        <tr><th>scanner</th><td>canon n670u</td></tr>
        <tr><th>joystick</th><td>logitech wingman rumblepad</td></tr>
      </table>

<?
  $page->heading('galileo', 'galileo');
?>
      <table class="columns" cellspacing="0">
        <tr><th>name</th><td>galileo</td></tr>
        <tr><th>purpose</th><td>mobile / secondary workstation</td></tr>
        <tr><th>mainboard</th><td>toshiba satellite r25</td></tr>
        <tr><th>processor</th><td>1.6-ghz intel core duo pentium m</td></tr>
        <tr><th>ram</th><td>512 MB pc4300 ddr2 sdram<br />512 MB pc4300 ddr2 sdram</td></tr>
        <tr><th>video</th><td>intel 945gm (128 MB)</td></tr>
        <tr><th>audio</th><td>intel 82801gbm ich7-m</td></tr>
        <tr><th>network</th><td>intel pro/100 ve<br />intel pro/wireless 3945abg</td></tr>
        <tr><th>modem</th><td>toshiba software modem</td></tr>
        <tr><th>ide0</th><td>hitachi 100 GB 5k100</td></tr>
        <tr><th>ide1</th><td>matshita dvd-ram</td></tr>
        <tr><th>operating system</th><td>microsoft windows xp professional tablet edition, sp2</td></tr>
        <tr><th>keyboard</th><td>toshiba satellite r25</td></tr>
        <tr><th>mouse</th><td>logitech v450 wireless laser</td></tr>
        <tr><th>monitor</th><td>toshiba wide 14.1&quot;</td></tr>
      </table>

<?
  $page->heading('egan', 'egan');
?>
      <table class="columns" cellspacing="0">
        <tr><th>name</th><td>egan</td></tr>
        <tr><th>purpose</th><td>(mostly) retired mobile</td></tr>
        <tr><th>mainboard</th><td>toshiba tecra 8000</td></tr>
        <tr><th>processor</th><td>intel pentium ii 300 MHz</td></tr>
        <tr><th>ram</th><td>64 MB pc66 sdram<br />64 MB pc66 sdram</td></tr>
        <tr><th>video</th><td>neomagic magicgraph256av (2.5 MB)</td></tr>
        <tr><th>audio</th><td>yamaha</td></tr>
        <tr><th>network</th><td>linksys usb 10t ethernet</td></tr>
        <tr><th>modem</th><td>toshiba internal v.90</td></tr>
        <tr><th>fd0</th><td>generic 3½" floppy</td></tr>
        <tr><th>ide0</th><td>ibm travelstar 6 GB</td></tr>
        <tr><th>ide1</th><td>teac 24x cd-rom</td></tr>
        <tr><th>operating system</th><td>microsoft windows xp professional</td></tr>
        <tr><th>keyboard</th><td>toshiba tecra 8000</td></tr>
        <tr><th>mouse</th><td>targus optical</td></tr>
        <tr><th>monitor</th><td>toshiba tecra 8000</td></tr>
      </table>

<?
  $page->End();
?>
