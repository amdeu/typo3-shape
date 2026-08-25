import jstin from '../lib/subscript-9.0.0/justin.min.js'
import './shape-field.js'
import './shape-repeatable-container.js'
import './shape-repeatable-item.js'

function evaluateConditions(form, namespace) {
	const fields = form.querySelectorAll('shape-field[condition]')
	if (!fields.length) return

	const data = Object.fromEntries(new FormData(form))

	fields.forEach(field => {
		const cond = field.getAttribute('condition')
		if (!cond) return

		const isVisible = jstin(cond)({
			value: fId => data[`tx_shape_form[${namespace}][${fId}]`] ?? null,
			formData: str => data[`tx_shape_form[${namespace}]${str}`] ?? null
		})
		field.toggleVisibility(isVisible)
	})
}

function attachConditionalFields(form, namespace) {
	form.addEventListener('change', e => {
		if (e.target.matches('[data-shape-control]')) evaluateConditions(form, namespace)
	})
	// Fired by shape-repeatable-item when a fieldset is added, so newly-added conditions get evaluated too.
	form.addEventListener('shape:fieldset-added', () => evaluateConditions(form, namespace))
	requestAnimationFrame(() => evaluateConditions(form, namespace))
}

function attachFocusPass(form) {
	const template = form.querySelector('[data-shape-focus-pass-template]')
	if (!template) return

	setTimeout(() => {
		form.addEventListener('focusin', () => {
			const frag = template.content.cloneNode(true)
			const field = frag.querySelector('[data-focus-pass]')
			field.value = field.dataset.focusPass
			template.parentElement.appendChild(frag)
		}, { once: true })
	}, 200)
}

// Sets a custom message *text* for native HTML5 validation (the browser's own tooltip/bubble still
// shows it, just with our wording instead of the browser default).
function attachValidationMessages(form) {
	form.addEventListener('invalid', e => {
		if (!e.target.matches('[data-shape-validation-message]')) return
		e.target.setCustomValidity(e.target.dataset.shapeValidationMessage)
	}, true)

	form.addEventListener('change', e => {
		if (!e.target.matches('[data-shape-validation-message]')) return
		e.target.setCustomValidity('')
	})
}

// Replaces the native (unstylable) validation bubble with a styled error element next to the field.
function attachStylableValidation(form) {
	form.addEventListener('invalid', e => {
		if (!e.target.matches('[data-shape-control]')) return
		const control = e.target
		const error = control.closest('shape-field')?.errorElement
		if (!error) return

		e.preventDefault()
		form.querySelector('[data-shape-control]:invalid')?.focus()
		error.classList.remove('--hidden')
		error.innerHTML = `<div>${control.dataset.shapeValidationMessage || control.validationMessage}</div>`
	}, true)

	form.addEventListener('change', e => {
		if (!e.target.matches('[data-shape-control]')) return
		if (e.target.validity.valid) {
			e.target.closest('shape-field')?.errorElement?.classList.add('--hidden')
		}
	})
}

export class ShapeFormElement extends HTMLElement {
	connectedCallback() {
		const form = this.querySelector('form')
		if (!form) return

		const namespace = this.getAttribute('namespace')

		if (this.getAttribute('conditional-fields') === '1') attachConditionalFields(form, namespace)
		if (this.getAttribute('focus-pass') === '1') attachFocusPass(form)
		if (this.getAttribute('validation-messages') === '1') attachValidationMessages(form)
		if (this.getAttribute('stylable-validation') === '1') attachStylableValidation(form)
	}
}

customElements.define('shape-form', ShapeFormElement)
