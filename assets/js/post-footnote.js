/**
 * Post footnote — subscribe form submit.
 *
 * The nonce is fetched at submit time (not baked into the page): pages are
 * cached by Cloudflare APO, so an embedded nonce would outlive its validity
 * and start rejecting legitimate visitors. Without JS the form falls back to
 * its action attribute (the Listmonk hosted subscription page).
 */
(function () {
	'use strict';

	document.querySelectorAll('.fn-subscribe-form').forEach(function (form) {
		form.addEventListener('submit', function (ev) {
			ev.preventDefault();

			var msg = form.querySelector('.fn-msg');
			var btn = form.querySelector('button[type="submit"]');
			var email = form.querySelector('input[type="email"]').value;
			var hp = form.querySelector('input[name="fn_website"]').value;

			// Turnstile injects its token as a hidden input once the (usually
			// invisible) challenge completes. No token yet → ask the user to
			// wait for / complete the check instead of sending a doomed POST.
			var tsInput = form.querySelector('input[name="cf-turnstile-response"]');
			var tsToken = tsInput ? tsInput.value : '';
			if (!tsToken) {
				msg.textContent = form.dataset.msgError;
				return;
			}

			btn.disabled = true;
			msg.textContent = '…';

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
					msg.textContent = (res.body && res.body.message) || form.dataset.msgError;
					if (res.ok) {
						form.querySelector('input[type="email"]').value = '';
					}
				})
				.catch(function () {
					msg.textContent = form.dataset.msgError;
				})
				.finally(function () {
					btn.disabled = false;
					// Tokens are single-use — reset so a second submit gets a fresh one.
					if (window.turnstile) {
						try { window.turnstile.reset(); } catch (e) { /* widget gone — fine */ }
					}
				});
		});
	});
})();
