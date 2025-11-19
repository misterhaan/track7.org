import "jquery";

const themeToggle = $("#theme-toggle");

function applyTheme(theme) {
	const html = $("html");
	themeToggle.removeClass("theme-system theme-dark theme-light");
	html.removeClass("dark light");

	themeToggle.addClass("theme-" + theme);
	if(theme != "system") {
		html.addClass(theme);
	}
}

let currentTheme = localStorage.getItem("theme") || "system";
applyTheme(currentTheme);

themeToggle.on("click", event => {
	event.stopPropagation();
	event.preventDefault();
	const darkSystem = matchMedia("(prefers-color-scheme: dark)").matches;
	switch(currentTheme) {
		case "dark":
			currentTheme = darkSystem ? "system" : "light";
			break;
		case "light":
			currentTheme = darkSystem ? "dark" : "system";
			break;
		default:
			currentTheme = darkSystem ? "light" : "dark";
			break;
	}

	applyTheme(currentTheme);
	if(currentTheme == "system")
		localStorage.removeItem("theme");
	else
		localStorage.setItem("theme", currentTheme);
});
