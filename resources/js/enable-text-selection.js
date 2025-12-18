/**
 * Enable Text Selection on Double Click for Draggable Elements
 *
 * This script listens for double-click events globally. If the user double-clicks
 * on an element that is (or is inside) a draggable container, it temporarily disables
 * the `draggable` attribute to allow native text selection.
 *
 * It restores the `draggable` attribute on the next left-click interaction.
 */

let temporarilyDisabledElement = null;

document.addEventListener('dblclick', function(event) {
    let target = event.target;

    // We only care if we are inside a draggable element
    const draggableParent = target.closest('[draggable="true"]');

    // Check if the target is an input/textarea/editable
    const isInput = target.tagName === 'INPUT' ||
                    target.tagName === 'TEXTAREA' ||
                    target.isContentEditable;

    if (draggableParent && !isInput) {
        // If there was a previously disabled element that wasn't restored, restore it now
        if (temporarilyDisabledElement && temporarilyDisabledElement !== draggableParent) {
            temporarilyDisabledElement.setAttribute('draggable', 'true');
        }

        // Disable draggable to allow selection to persist
        draggableParent.setAttribute('draggable', 'false');
        temporarilyDisabledElement = draggableParent;

        const selection = window.getSelection();
        const range = document.createRange();

        // Select the text content of the target
        // We select the target node itself
        range.selectNodeContents(target);

        selection.removeAllRanges();
        selection.addRange(range);
    }
});

// Restore draggable functionality on the next left-click (mousedown)
document.addEventListener('mousedown', function(event) {
    // Only restore on left click (button 0).
    // Right-click (button 2) is often used for context menu (Copy), so we must NOT restore yet.
    if (temporarilyDisabledElement && event.button === 0) {
        temporarilyDisabledElement.setAttribute('draggable', 'true');
        temporarilyDisabledElement = null;
    }
});
