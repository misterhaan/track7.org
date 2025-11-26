import Ajax from "./ajax.js";

const apiURL = "/api/update.php/";

export default class UpdateApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static add(markdown, posted) {
		const url = `${apiURL}add`;
		const data = { markdown: markdown, posted: posted };
		return Ajax.post(url, data);
	}
}
