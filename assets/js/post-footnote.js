/**
 * Post footnote — subscribe form submit. (v1.3.1)
 *
 * The nonce is fetched at submit time (not baked into the page): pages are
 * cached by Cloudflare APO, so an embedded nonce would outlive its validity
 * and start rejecting legitimate visitors. Without JS the form falls back to
 * its action attribute (the Listmonk hosted subscription page).
 *
 * Status messages are STATE-DRIVEN: the .fn-msg node stays hidden until a
 * submit produces a result, then shows exactly one message matched to it
 * (success / rate-limited / error — the server picks the copy, incl. 429's
 * own wording). Classes fn-msg--ok / fn-msg--err drive the styling.
 */
(function () {
	'use strict';

	// Sync the Turnstile widget's theme to the site's dark-mode toggle BEFORE
	// the deferred turnstile api.js renders it (this script is enqueued first).
	// The toggle stamps data-theme on <html>; "auto" only follows the OS.
	var siteTheme = document.documentElement.getAttribute('data-theme');
	if (siteTheme === 'light' || siteTheme === 'dark') {
		document.querySelectorAll('.cf-turnstile').forEach(function (w) {
			w.setAttribute('data-theme', siteTheme);
		});
	}

	function showMsg(form, text, kind) {
		var msg = form.querySelector('.fn-msg');
		msg.textContent = text;
		msg.classList.remove('fn-msg--ok', 'fn-msg--err');
		if (kind) {
			msg.classList.add(kind === 'ok' ? 'fn-msg--ok' : 'fn-msg--err');
		}
		msg.hidden = false;
	}

	document.querySelectorAll('.fn-subscribe-form').forEach(function (form) {
		form.addEventListener('submit', function (ev) {
			ev.preventDefault();

			var btn = form.querySelector('button[type="submit"]');
			var email = form.querySelector('input[type="email"]').value;
			var hp = form.querySelector('input[name="fn_website"]').value;

			// Turnstile injects its token as a hidden input once the
			// (interaction-only, usually invisible) challenge completes.
			var tsInput = form.querySelector('input[name="cf-turnstile-response"]');
			var tsToken = tsInput ? tsInput.value : '';
			if (!tsToken) {
				showMsg(form, form.dataset.msgError, 'err');
				return;
			}

			btn.disabled = true;
			showMsg(form, '…', null);

			fetch(form.dataset.nonceUrl, { headers: { Accept: 'application/json' } })
				.then(function (r) { return r.json(); })
				.then(function (n) {
					return fetch(form.dataset.rest, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({
							email: email,
							fn_website: hp,
							post: parseInt(form.dataset.post, 10),
							lang: form.dataset.lang,
							nonce: n.nonce,
							turnstile: tsToken
						})
					});
				})
				.then(function (r) {
					return r.json().then(function (j) { return { ok: r.ok, body: j }; });
				})
				.then(function (res) {
					showMsg(form, (res.body && res.body.message) || form.dataset.msgError, res.ok ? 'ok' : 'err');
					if (res.ok) {
						form.querySelector('input[type="email"]').value = '';
					}
				})
				.catch(function () {
					showMsg(form, form.dataset.msgError, 'err');
				})
				.finally(function () {
					btn.disabled = false;
					// Tokens are single-use — reset so a retry mints a fresh one.
					if (window.turnstile) {
						try { window.turnstile.reset(); } catch (e) { /* widget gone — fine */ }
					}
				});
		});
	});
})();
