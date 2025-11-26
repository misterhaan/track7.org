import Ajax from "./ajax.js";

const apiURL = "/api/forum.php/";

export default class ForumApi {
	static list(tag, skip) {
		let url = `${apiURL}list`;
		if(tag)
			url += `/${tag}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static start(title, tags, message, isLoggedIn, name, contact) {
		const url = `${apiURL}start`;
		const data = {
			title: title,
			tags: tags,
			message: message
		};
		if(!isLoggedIn) {
			data.name = name;
			data.contact = contact;
		}
		return Ajax.post(url, data);
	}
}
