export default class Ajax {
	static get(url) {
		return ajax("GET", url);
	}

	static post(url, data) {
		return ajax("POST", url, data);
	}

	static put(url, data) {
		return ajax("PUT", url, data);
	}

	static patch(url, data) {
		return ajax("PATCH", url, data);
	}

	static delete(url) {
		return ajax("DELETE", url);
	}
}

async function ajax(method, url, data) {
	const init = { method: method };
	if(data)
		if(typeof data == "string" || data instanceof FormData || data instanceof URLSearchParams)
			init.body = data;
		else
			init.body = new URLSearchParams(data);
	const response = await fetch(url, init);
	if(!response.ok)
		throw new Error(await response.text());
	return await response.json();
}
