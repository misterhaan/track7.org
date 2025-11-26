import Ajax from "./ajax.js";

const apiURL = "/api/release.php/";

export default class ReleaseApi {
	static list(application, skip) {
		let url = `${apiURL}list/${application}`;
		if(skip)
			url += `/${skip}`;
		return Ajax.get(url);
	}

	static versionAvailable(application, version) {
		const url = `${apiURL}versionAvailable/${application}`;
		return Ajax.post(url, version);
	}

	static add(application, version, instant, language, dotNet, visualStudio, changelog, binURL, bin32URL, srcURL) {
		const url = `${apiURL}add/${application}`;
		const data = {
			version: version,
			instant: instant,
			language: language,
			dotnet: dotNet,
			visualstudio: visualStudio,
			changelog: changelog,
			binurl: binURL,
			bin32url: bin32URL,
			srcurl: srcURL
		};
		return Ajax.post(url, data);
	}
}
