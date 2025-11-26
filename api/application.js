import Ajax from "./ajax.js";

const apiURL = "/api/application.php/";

export default class ApplicationApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static edit(id) {
		let url = `${apiURL}edit/${id}`;
		return Ajax.get(url);
	}

	static idAvailable(newId, oldId) {
		const url = `${apiURL}idAvailable/${oldId}`;
		return Ajax.post(url, newId);
	}

	static save(originalId, formData, newId, name, markdown, github, wiki) {
		const url = `${apiURL}save/${originalId}`;
		formData.append("id", newId);
		formData.append("name", name);
		formData.append("markdown", markdown);
		if(github)
			formData.append("github", github);
		if(wiki)
			formData.append("wiki", wiki);
		return Ajax.post(url, formData);
	}
}
