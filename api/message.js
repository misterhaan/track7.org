import Ajax from "./ajax.js";

const apiURL = "/api/message.php/";

export default class MessageApi {
	static list() {
		const url = `${apiURL}list`;
		return Ajax.get(url);
	}

	static conversation(withUserID, skip) {
		let url = `${apiURL}conversation/${withUserID}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static send(toUserID, message, fromName, fromContact) {
		const url = `${apiURL}send/${toUserID}`;
		const data = { message: message };
		if(fromName)
			data.fromname = fromName;
		if(fromContact)
			data.fromcontact = fromContact;
		return Ajax.post(url, data);
	}
}
