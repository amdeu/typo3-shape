import jstin from '../lib/subscript-9.0.0/justin.min.js'

export class ShapeFieldElement extends HTMLElement {
	connectedCallback() {
		const form = this.closest('form')
		const shapeForm = this.closest('shape-form')
		if (!form || !shapeForm) return
		this._form = form

		if (this.getAttribute('condition') && shapeForm.getAttribute('conditional-fields') === '1') {
			const namespace = shapeForm.getAttribute('namespace')
			this._onConditionChange = () => this.evaluateCondition(namespace)
			form.addEventListener('change', this._onConditionChange)
			this.evaluateCondition(namespace)
		}

		// Sets a custom message *text* for native HTML5 validation (the browser's own tooltip/bubble
		// still shows it, just with our wording instead of the browser default).
		if (shapeForm.getAttribute('validation-messages') === '1') {
			this._onInvalidMessage = e => {
				if (!e.target.matches('[data-shape-validation-message]')) return
				e.target.setCustomValidity(e.target.dataset.shapeValidationMessage)
			}
			this._onChangeMessage = e => {
				if (!e.target.matches('[data-shape-validation-message]')) return
				e.target.setCustomValidity('')
			}
			this.addEventListener('invalid', this._onInvalidMessage, true)
			this.addEventListener('change', this._onChangeMessage)
		}

		// Replaces the native (unstylable) validation bubble with a styled error element in this field.
		if (shapeForm.getAttribute('stylable-validation') === '1') {
			this._onInvalidStyle = e => {
				if (!e.target.matches('[data-shape-control]')) return
				const control = e.target
				const error = this.errorElement
				if (!error) return

				e.preventDefault()
				this.querySelector('[data-shape-control]:invalid')?.focus()
				error.classList.remove('--hidden')
				error.innerHTML = `<div>${control.dataset.shapeValidationMessage || control.validationMessage}</div>`
			}
			this._onChangeStyle = e => {
				if (!e.target.matches('[data-shape-control]')) return
				if (e.target.validity.valid) this.errorElement?.classList.add('--hidden')
			}
			this.addEventListener('invalid', this._onInvalidStyle, true)
			this.addEventListener('change', this._onChangeStyle)
		}
	}

	disconnectedCallback() {
		if (this._onConditionChange) this._form?.removeEventListener('change', this._onConditionChange)
		if (this._onInvalidMessage) this.removeEventListener('invalid', this._onInvalidMessage, true)
		if (this._onChangeMessage) this.removeEventListener('change', this._onChangeMessage)
		if (this._onInvalidStyle) this.removeEventListener('invalid', this._onInvalidStyle, true)
		if (this._onChangeStyle) this.removeEventListener('change', this._onChangeStyle)
	}

	evaluateCondition(namespace) {
		const cond = this.getAttribute('condition')
		if (!cond || !this._form) return

		const data = Object.fromEntries(new FormData(this._form))
		const isVisible = jstin(cond)({
			value: fId => data[`tx_shape_form[${namespace}][${fId}]`] ?? null,
			formData: str => data[`tx_shape_form[${namespace}]${str}`] ?? null
		})
		this.toggleVisibility(isVisible)
	}

	toggleVisibility(isVisible) {
		this.classList.toggle('--hidden', !isVisible)
		this.querySelectorAll('[data-shape-control]').forEach(input => input.disabled = !isVisible)
	}

	get errorElement() {
		return this.querySelector('[data-shape-error]')
	}

	get name() {
		return this.getAttribute('name')
	}

	get condition() {
		return this.getAttribute('condition')
	}
}

customElements.define('shape-field', ShapeFieldElement)
