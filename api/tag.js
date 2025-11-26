import Ajax from "./ajax.js";

const apiURL = "/api/tag.php/";

export default class TagApi {
	static list(subsite, minOccurrences) {
		let url = `${apiURL}list/${subsite}`;
		if(minOccurrences)
			url += `/${minOccurrences}`;
		return Ajax.get(url);
	}

	static stats(subsite) {
		const url = `${apiURL}stats/${subsite}`;
		return Ajax.get(url);
	}

	static description(subsite, name, description) {
		const url = `${apiURL}description/${subsite}/${name}`;
		return Ajax.put(url, description);
	}
}
