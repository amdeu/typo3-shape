/*
 * This file is part of the "shape" TYPO3 extension.
 *
 * Drag & drop sorting for the form-page / module button rows rendered by
 * \Amdeu\Shape\Backend\Preview\FormPluginPreviewRenderer in the page module.
 *
 * The new order is persisted through the DataHandler using the same "move after
 * record" command the list module's up/down links use.
 */
import Sortable from 'sortablejs';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler.js';
import DocumentService from '@typo3/core/document-service.js';

class PreviewFormSortable {
  constructor() {
    this.containerSelector = '[data-shape-preview-sortable]';
    this.itemSelector = '.shape-preview-sortable-item';
    this.handleSelector = '.shape-preview-drag-handle';
    this.addSelector = '.shape-preview-add';
    DocumentService.ready().then(() => this.initialize());
  }

  initialize() {
    document.querySelectorAll(this.containerSelector).forEach((container) => {
      if (container.dataset.shapeSortableInitialized) {
        return;
      }
      container.dataset.shapeSortableInitialized = '1';
      new Sortable(container, {
        handle: this.handleSelector,
        draggable: this.itemSelector,
        animation: 150,
        // keep the trailing "add" button pinned to the end
        onMove: (event) => !(event.related.matches(this.addSelector) && event.willInsertAfter),
        onEnd: (event) => this.persist(container, event),
      });
    });
  }

  persist(container, event) {
    const item = event.item;
    const table = container.dataset.table;
    const uid = parseInt(item.dataset.uid, 10);
    if (!table || !uid || event.oldIndex === event.newIndex) {
      return;
    }

    let previous = item.previousElementSibling;
    while (previous !== null && !previous.matches(this.itemSelector)) {
      previous = previous.previousElementSibling;
    }
    // negative target uid = "move behind that record", storage pid = "move to top"
    const target = previous !== null
      ? -parseInt(previous.dataset.uid, 10)
      : parseInt(container.dataset.pid, 10);

    const context = { component: 'shape-preview', action: 'move', table: table, uid: uid };
    AjaxDataHandler
      .process(`cmd[${table}][${uid}][move]=${target}`, context)
      .catch(() => document.location.reload());
  }
}

export default new PreviewFormSortable();
