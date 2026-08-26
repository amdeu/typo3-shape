export class ShapeRepeatableItemElement extends HTMLElement {
	connectedCallback() {
		const index = this.getAttribute('index')

		this.querySelectorAll('shape-field').forEach(field => {
			const cond = field.getAttribute('condition')
			if (cond) field.setAttribute('condition', cond.replaceAll('[__INDEX]', `[${index}]`))
		})

		this.querySelectorAll('input, textarea, select').forEach(input => {
			input.name = input.name.replaceAll('[__INDEX]', `[${index}]`)
			const newId = input.id.replaceAll('[__INDEX]', `[${index}]`)
			if (newId !== input.id) {
				this.querySelector(`label[for="${input.id}"]`)?.setAttribute('for', newId)
			}
			input.id = newId
		})

		this.querySelector('[data-shape-repeatable-remove]')?.addEventListener('click', () => this.remove())
	}
}

customElements.define('shape-repeatable-item', ShapeRepeatableItemElement)
