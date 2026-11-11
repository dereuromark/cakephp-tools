/**
 * Decodes links rendered by ObfuscateHelper::emailLink().
 *
 * The data-tools-obfuscate value is the mailto URL, base64 encoded and then reversed.
 * Links are decoded on DOMContentLoaded, by ToolsObfuscate.init(root) for inserted content,
 * and lazily on the first mouseover, focus, touch or click of any link not yet decoded.
 */
(function (window, document) {
	'use strict';

	var ATTR = 'data-tools-obfuscate';
	var TEXT_ATTR = 'data-tools-obfuscate-text';
	var SELECTOR = 'a[' + ATTR + ']';

	function decode(value) {
		var binary = window.atob(value.split('').reverse().join(''));
		var bytes = new Uint8Array(binary.length);
		for (var i = 0; i < binary.length; i++) {
			bytes[i] = binary.charCodeAt(i);
		}

		return new TextDecoder().decode(bytes);
	}

	function reveal(link) {
		var encoded = link.getAttribute(ATTR);
		if (!encoded) {
			return;
		}

		var href;
		try {
			href = decode(encoded);
		} catch (e) {
			return;
		}
		if (href.indexOf('mailto:') !== 0) {
			return;
		}

		link.setAttribute('href', href);
		link.removeAttribute(ATTR);
		if (link.hasAttribute(TEXT_ATTR)) {
			link.textContent = href.slice(7).split('?')[0];
			link.removeAttribute(TEXT_ATTR);
		}
	}

	function init(root) {
		root = root || document;
		if (root.nodeType === 1 && root.matches(SELECTOR)) {
			reveal(root);
		}
		var links = root.querySelectorAll(SELECTOR);
		for (var i = 0; i < links.length; i++) {
			reveal(links[i]);
		}
	}

	function onInteraction(event) {
		var node = event.target;
		while (node && node.nodeType !== 1) {
			node = node.parentNode;
		}
		var link = node ? node.closest(SELECTOR) : null;
		if (link) {
			reveal(link);
		}
	}

	['mouseover', 'focusin', 'touchstart', 'click'].forEach(function (type) {
		document.addEventListener(type, onInteraction, {capture: true, passive: true});
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			init(document);
		});
	} else {
		init(document);
	}

	window.ToolsObfuscate = {init: init, reveal: reveal, decode: decode};
})(window, document);
