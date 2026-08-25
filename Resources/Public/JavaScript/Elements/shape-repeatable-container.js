export class ShapeRepeatableContainerElement extends HTMLElement {
	connectedCallback() {
		this.querySelector('[data-shape-repeatable-add]')?.addEventListener('click', e => this.addFieldset(e))
	}

	addFieldset(e) {
		const btn = e.currentTarget
		if (!btn.disabled) {
			btn.disabled = true
			setTimeout(() => btn.disabled = false, 500)
		}

		const tmpl = this.querySelector('template')
		if (!tmpl) return

		const index = parseInt(this.getAttribute('iteration') ?? '0', 10)
		const clone = tmpl.content.cloneNode(true)
		const item = clone.querySelector('shape-repeatable-item')
		if (item) item.setAttribute('index', String(index))

		// Insert right before the button (a fixed last child), so items stay in chronological order
		// regardless of how many have already been added.
		btn.before(clone)
		this.setAttribute('iteration', String(index + 1))
	}
}

customElements.define('shape-repeatable-container', ShapeRepeatableContainerElement)
