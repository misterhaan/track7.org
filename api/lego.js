import Ajax from "./ajax.js";

const apiURL = "/api/lego.php/";

export default class LegoApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static edit(id) {
		const url = `${apiURL}edit/${id}`;
		return Ajax.get(url);
	}

	static idAvailable(newId, oldId) {
		const url = `${apiURL}idAvailable/${oldId}`;
		return Ajax.post(url, newId);
	}

	static save(originalId, formData, newId, title, pieces, description) {
		const url = `${apiURL}save/${originalId}`;
		formData.append("id", newId);
		formData.append("title", title);
		formData.append("pieces", pieces);
		formData.append("description", description);
		return Ajax.post(url, formData);
	}
}
