addStartupFunction(enhanceUserField);

function enhanceUserField() {
  enableSuggest("fldto", "/user/list.php?return=suggest&match=");
}
