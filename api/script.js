import Ajax from "./ajax.js";

const apiURL = "/api/script.php/";

export default class ScriptApi {
	static list(skip) {
		let url = `${apiURL}list`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static edit(id) {
		const url = `${apiURL}edit/${id}`
		return Ajax.get(url);
	}

	static idAvailable(newId, oldId) {
		const url = `${apiURL}idAvailable/${oldId}`;
		return Ajax.post(url, newId);
	}

	static save(originalId, data, newId, title, type, description, instructions, download, github, wiki, instant) {
		const url = `${apiURL}save/${originalId}`;
		data.append("id", newId);
		data.append("title", title);
		data.append("type", type);
		data.append("description", description);
		data.append("instructions", instructions);
		if(download)
			data.append("download", download);
		if(github)
			data.append("github", github);
		if(wiki)
			data.append("wiki", wiki);
		if(instant)
			data.append("instant", instant);
		return Ajax.post(url, data);
	}
}
