export class ShapeFieldElement extends HTMLElement {
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
