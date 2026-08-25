const loader = document.getElementById('shape-form-lazy-loader')
if (loader?.dataset?.fetch) {
	fetch(loader.dataset.fetch)
		.then(r => r.text())
		.then(html => {
			// Inserting this HTML auto-upgrades any custom elements within it (e.g. <shape-form>) -
			// no manual "just connected" signal needed.
			loader.insertAdjacentHTML('beforebegin', html)
			loader.remove()
		})
}
