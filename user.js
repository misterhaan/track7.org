import "jquery";

const userLink = $("#whodat");

export const currentUser = userLink.length ? {
	URL: userLink.attr("href"),
	DisplayName: userLink.text(),
	Avatar: userLink.find("img").attr("src"),
	Level: userLink.data("level")
} : null;

