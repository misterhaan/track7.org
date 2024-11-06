import "jquery";

export function GetCurrentUser() {
	const userLink = $("#whodat");
	return userLink.length ? {
		URL: userLink.attr("href"),
		DisplayName: userLink.text(),
		Avatar: userLink.find("img").attr("src"),
		Level: userLink.data("level")
	} : null;
}
