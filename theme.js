const themeToggle = document.querySelector("#theme-toggle");

function applyTheme(theme) {
	const html = document.querySelector("html");
	themeToggle?.classList.remove("theme-system", "theme-dark", "theme-light");
	html.classList.remove("dark", "light");

	themeToggle?.classList.add("theme-" + theme);
	if(theme != "system")
		html.classList.add(theme);
}

let currentTheme = localStorage.getItem("theme") || "system";
applyTheme(currentTheme);

themeToggle?.addEventListener("click", event => {
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
