addStartupFunction(enhanceManufacturerField);

function enhanceManufacturerField() {
  enableSuggest("fldmanufacturer", "/geek/discgolf/discs.php?return=suggest&match=");
}
