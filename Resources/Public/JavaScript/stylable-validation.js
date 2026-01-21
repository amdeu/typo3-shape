{
	const connectElement = el => {
		const form = el.closest('[data-shape-form]') ?? el.querySelector('[data-shape-form]')
		if (!form) return

		if (!form.__shapeValidationDelegation) {
			form.__shapeValidationDelegation = true

			form.addEventListener('invalid', e => {
				if (!e.target.matches('[data-shape-control]')) return
				const control = e.target
				const error = control.closest('[data-shape-field]')?.querySelector('[data-shape-error]')
				if (!error) return

				e.preventDefault()
				form.querySelector('[data-shape-control]:invalid')?.focus()
				error.classList.remove('--hidden')
				const errorType = Object.keys(control.validity).find(key => control.validity[key])
				error.innerHTML = `<div>${control.dataset.shapeValidationMessage || control.validationMessage}</div>`
			}, true)

			form.addEventListener('change', e => {
				if (!e.target.matches('[data-shape-control]')) return

				const control = e.target
				if (control.validity.valid) {
					const error = control.closest('[data-shape-field]')?.querySelector('[data-shape-error]')
					error?.classList.add('--hidden')
				}
			})
		}
	}

	document.addEventListener('shape:connect', e => connectElement(e.detail.element))
	document.querySelectorAll('[data-shape-form]').forEach(form => connectElement(form))
}