import Ajax from "./ajax.js";

const apiURL = "/api/date.php/";

export default class DateApi {
	static validatePast(dateString) {
		const url = `${apiURL}validatePast`;
		return Ajax.post(url, dateString);
	}

	static validateTime(timeString) {
		const url = `${apiURL}validateTime`;
		return Ajax.post(url, timeString);
	}
}
