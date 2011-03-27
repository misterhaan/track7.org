<?php
  $wgGroupPermissions['*']['edit'] = false;
  $wgGroupPermissions['*']['createaccount'] = false;

  class Auth_track7 {
    public function Auth_track7() {
      global $wgHooks;
      $wgHooks['UserLoginForm'][] = $this;
      $wgHooks['UserLoginComplete'][] = $this;
      $wgHooks['UserLogout'][] = $this;
    }
  }
?>
