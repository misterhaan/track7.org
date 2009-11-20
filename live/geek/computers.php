<?
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/lib/track7.php';
  $page->start('computer hardware - geek', 'computer hardware', '', '', array('hecubus', 'tesla', 'galileo', 'egan'));

  $page->heading('hecubus', 'hecubus');
?>
      <table class="columns" cellspacing="0">
        <tr class="first"><th>name</th><td>hecubus</td></tr>
        <tr><th>class</th><td>workstation</td></tr>
        <tr><th>purpose</th><td>file / ftp / http server / dvr</td></tr>
        <tr><th>mainboard</th><td>gigabyte ga-945gzm-s2<!-- gigabyte ga-6vxe7+ --></td></tr>
        <tr><th>processor</th><td>intel pentium d 2.66 GHz<!-- intel pentium iii 933 MHz --></td></tr>
        <tr><th>ram</th><td>g.skill 1 GB pc2 5300 ddr2 sdram<!-- 256 MB pc133 sdram<br />128 MB pc133 sdram --></td></tr>
        <tr><th>video</th><td>intel gma950 (onboard)</td></tr>
        <tr><th>audio</th><td>realtek alc883 (onboard, disabled)</td></tr>
        <tr><th>tuner</th><td>hauppage wintv-pvr 150</td></tr>
        <tr><th>network</th><td>realtek 8110sc 10/100/1000 ethernet (onboard)</td></tr>
        <tr><th>ide0</th><td>western digital caviar 80 GB wd800jb</td></tr>
        <tr><th>ide1</th><td>iomega zip100</td></tr>
        <!-- western digital caviar 80 GB wd800jd -->
        <tr><th>sata2</th><td>western digital caviar 400 GB wd4000aaks</td></tr>
        <tr><th>sata3</th><td>lite-on dvd-rom dh-16d2s-04</td></tr>
        <tr><th>operating system</th><td>fedora 11 64-bit</td></tr>
      </table>

<?
  $page->heading('tesla', 'tesla');
?>
      <table class="columns" cellspacing="0">
        <tr class="first"><th>name</th><td>tesla</td></tr>
        <tr><th>class</th><td>workstation</td></tr>
        <tr><th>purpose</th><td>main workstation / gaming</td></tr>
        <tr><th>mainboard</th><td>gigabyte ga-ep35-ds3l</td></tr>
        <tr><th>processor</th><td>intel core 2 quad q9300 2.5 GHz</td></tr>
        <tr><th>ram</th><td>g.skill 1 GB pc2 8500 ddr2 sdram<br />g.skill 1 GB pc2 8500 ddr2 sdram<br />g.skill 1 GB pc2 8500 ddr2 sdram<br />g.skill 1 GB pc2 8500 ddr2 sdram</td></tr>
        <tr><th>video</th><td>evga 384-p3-n851-ar nvidia geforce 8800 gs (384 MB)</td></tr>
        <tr><th>audio</th><td>realtek high definition audio (onboard)</td></tr>
        <tr><th>network</th><td>realtek rtl8169 1000/100/10 ethernet (onboard)</td></tr>
        <tr><th>reader</th><td>rosewill multi card reader</td></tr>
        <tr><th>sata0</th><td>western digital velociraptor 150 GB wd1500hlfs</td></tr>
        <tr><th>sata1</th><td>samsung dvd burner sh-s183l</td></tr>
        <tr><th>operating system</th><td>ubuntu 9.10 intrepid ibex 64-bit<br />microsoft windows 7 64-bit</td></tr>
        <tr><th>keyboard</th><td>logitech premium desktop</td></tr>
        <tr><th>mouse</th><td>logitech mx revolution<br />logitech cordless optical</td></tr>
        <tr><th>monitor</th><td>samsung syncmaster 204b</td></tr>
        <tr><th>scanner</th><td>canon canoscan lide 100</td></tr>
        <tr><th>joystick</th><td>logitech extreme 3d pro<br />logitech wingman rumblepad</td></tr>
      </table>

<?
  $page->heading('galileo', 'galileo');
?>
      <table class="columns" cellspacing="0">
        <tr class="first"><th>name</th><td>galileo</td></tr>
        <tr><th>class</th><td>laptop / tablet</td></tr>
        <tr><th>purpose</th><td>mobile / secondary workstation</td></tr>
        <tr><th>mainboard</th><td>toshiba satellite r25</td></tr>
        <tr><th>processor</th><td>1.6-ghz intel core duo pentium m</td></tr>
        <tr><th>ram</th><td>512 MB pc4300 ddr2 sdram<br />512 MB pc4300 ddr2 sdram</td></tr>
        <tr><th>video</th><td>intel 945gm (128 MB)</td></tr>
        <tr><th>audio</th><td>intel 82801gbm ich7-m</td></tr>
        <tr><th>network</th><td>intel pro/100 ve<br />intel pro/wireless 3945abg</td></tr>
        <tr><th>modem</th><td>toshiba software modem</td></tr>
        <tr><th>reader</th><td>sd card reader</td></tr>
        <tr><th>ide0</th><td>hitachi 100 GB 5k100</td></tr>
        <tr><th>ide1</th><td>matshita dvd-ram</td></tr>
        <tr><th>operating system</th><td>ubuntu 9.10 intrepid ibex 32-bit<br />microsoft windows 7 32-bit</td></tr>
        <tr><th>keyboard</th><td>toshiba satellite r25</td></tr>
        <tr><th>mouse</th><td>logitech v450 wireless laser</td></tr>
        <tr><th>monitor</th><td>toshiba wide 14.1″</td></tr>
      </table>

<?
  $page->End();
?>
