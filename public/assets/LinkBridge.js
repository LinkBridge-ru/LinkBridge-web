class API {
	async sendRequest(url, type = "POST", body = null, headers = {}) {
		const options = {
			method: type, headers: {...headers}
		};

		if (body && !(body instanceof FormData) && type !== "GET" && type !== "HEAD") {
			options.headers["Content-Type"] = "application/x-www-form-urlencoded";
		}

		if (body && type !== "GET" && type !== "HEAD") options.body = body;

		try {
			const response = await fetch(url, options);
			const data = await response.json();

			if (!response.ok) {
				console.debug("Server response:\n", data ? data : "Something went wrong...");
			}

			return data;
		} catch (error) {
			throw error;
		}
	}
}

const api = new API();
const formData = new FormData();

/**
 * Метод ожидает ссылку.
 * @param pin
 */
function LinkBridgeFetchLink(pin) {
	const requestInterval = 0x003e8;
	const sessionDuration = 0x00014 * 60 * 1000;

	const buildUrlWithParams = (baseUrl, params) => {
		const url = new URL(baseUrl);
		Object.entries(params).forEach(([key, value]) => {
			url.searchParams.append(key, String(value));
		});
		return url.toString();
	};

	const baseUrl = window.location.protocol + "//" + window.location.hostname + "/api/check";
	const params = {pin};

	const intervalId = setInterval(() => {
		const urlWithParams = buildUrlWithParams(baseUrl, params);

		api.sendRequest(urlWithParams, "GET", null, {})
			.then(response => {
				console.debug(response);
				if (response.data.url) {
					clearInterval(intervalId);
					clearTimeout(timeoutId);
					document.location.href = response.data.url;
				}
			})
			.catch(error => {
				console.error("API Error: ", error.message);
				// clearInterval(intervalId);
				// clearTimeout(timeoutId);
			});
	}, requestInterval);

	const timeoutId = setTimeout(() => {
		clearInterval(intervalId);
		//	QR Replace.
		document.getElementById("LB_QR").classList.add("d-none");
		document.getElementById("LB_QR_INVALID").classList.remove("d-none");

		//	PIN Replace.
		document.getElementById("LB_PIN").classList.add("d-none");
		document.getElementById("LB_PIN_TimeOut").classList.remove("d-none");

		//	Description Replace.
		document.getElementById("LB_PIN_Description").classList.add("d-none");
		document.getElementById("LB_PIN_Description_TimeOut").classList.remove("d-none");
	}, sessionDuration);
}

/**
 * @deprecated Метод отправки ссылки.
 */
function LinkBridgeSendLink() {
	const create_alert = document.getElementById("BridgeAlert");

	function returnAlert(text, type = "info") {
		create_alert.innerHTML = '<p class="lb-alert-' + type + ' text-uppercase">' + text + '</p>';
	}

	document.addEventListener("DOMContentLoaded", function () {
		const sendButton = document.getElementById("send-button");
		const pinInput = document.getElementById("pin");
		const urlInput = document.getElementById("url");

		sendButton.addEventListener("click", function (_el) {
			_el.preventDefault();
			formData.append("pin", pinInput.value);
			formData.append("url", urlInput.value);

			api.sendRequest(window.location.protocol + "//" + window.location.hostname + "/api/send/2", "POST", formData)
				.then(response => {
					if (response && response.status && response.message) {
						returnAlert(response.message, response.status === "HTTP_ACCEPTED" ? "success" : "warning");
						pinInput.value = "";
						urlInput.value = "";
						pinInput.focus();
						setTimeout(function () {
							window.location.href = window.location.protocol + "//" + window.location.hostname;
						}, 3000);
					} else {
						returnAlert("Unknown Error", "danger");
					}
				})
				.catch(error => {
					returnAlert(error, "danger");
				});
		});

		[pinInput, urlInput].forEach(input => {
			input.addEventListener("keydown", function (e) {
				if (e.key === "Enter" || e.code === "NumpadEnter") {
					e.preventDefault();
					sendButton.click();
				}
			});
		});
	});
}

/**
 * Ожидаем нажатие на #pin-copy.
 */
document.addEventListener("DOMContentLoaded", () => {
	const element = document.querySelector("#pin-copy");
	if (!element) return;

	const tt = document.createElement("div");
	tt.id = "custom-tooltip";
	tt.style.cssText = "position:absolute;display:none;background:#000;color:#fff;padding:4px 8px;border-radius:4px;font-size:20px;z-index:1000";
	document.body.appendChild(tt);

	const pos = () => {
			const r = element.getBoundingClientRect();
			tt.style.top = (r.top - tt.offsetHeight - 5 + window.scrollY) + "px";
			tt.style.left = (r.left + r.width / 2 - tt.offsetWidth / 2 + window.scrollX) + "px";
		}, show = t => {
			tt.innerText = t;
			tt.style.display = "block";
			pos();
		}, hide = () => tt.style.display = "none",
		orig = element.getAttribute("data-original-title") || element.getAttribute("title");

	let timer;
	element.addEventListener("mouseenter", () => show(orig));
	element.addEventListener("mouseleave", () => {
		hide();
		if (timer) {
			clearTimeout(timer);
			timer = null;
		}
	});
	element.addEventListener("click", () => {
		const selection = window.getSelection();
		const range = document.createRange();
		range.selectNodeContents(element);
		selection.removeAllRanges();
		selection.addRange(range);

		const text = selection.toString().trim();
		navigator.clipboard.writeText(text).catch(() => {
		});

		show(element.getAttribute("data-copied-title"));
		if (timer) clearTimeout(timer);
		timer = setTimeout(() => {
			element.matches(":hover") && show(orig);
			timer = null;
		}, 3000);
	});
});
