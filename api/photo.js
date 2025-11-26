import Ajax from "./ajax.js";
import { FindAddedTags } from "tag";

const apiURL = "/api/photo.php/";

export default class PhotoApi {
	static list(tag, skip) {
		let url = `${apiURL}list`;
		if(tag)
			url += `/${tag}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static edit(id) {
		const url = `${apiURL}edit/${id}`;
		return Ajax.get(url);
	}

	static idAvailable(newID, oldID) {
		const url = `${apiURL}idAvailable/${oldID}`;
		return Ajax.post(url, newID);
	}

	static save(originalID, formData, newID, taken, tags, originalTags) {
		const url = `${apiURL}save/${originalID}`;
		formData.append("id", newID);
		formData.append("taken", taken);
		formData.append("addtags", FindAddedTags(tags, originalTags));
		formData.append("deltags", FindAddedTags(originalTags, tags));
		return Ajax.post(url, formData);
	}
}
