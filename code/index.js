const sections = document.querySelectorAll("#codetypes section");
sections.forEach(section => {
	section.addEventListener("click", event => {
		if(event.target.nodeName.toLowerCase() != "a")
			location.href = event.currentTarget.querySelector("h2 a")?.href;
	});
	section.style.cursor = "pointer";
});
