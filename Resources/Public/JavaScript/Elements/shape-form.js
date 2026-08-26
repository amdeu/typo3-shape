import './shape-field.js'
import './shape-repeatable-container.js'
import './shape-repeatable-item.js'

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

export class ShapeFormElement extends HTMLElement {
	connectedCallback() {
		const form = this.querySelector('form')
		if (!form) return

		if (this.getAttribute('focus-pass') === '1') attachFocusPass(form)
	}
}

customElements.define('shape-form', ShapeFormElement)
