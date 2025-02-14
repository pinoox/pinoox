export const _isNumberKey = (keyCode, event) => {

    // Allow specific combinations like Cmd+R or Ctrl+R
    const isRefreshCombination = (event?.metaKey || event?.ctrlKey) && event.which === 82; // 'R' key
    if (isRefreshCombination) window.location.reload();

    // Check if the keyCode corresponds to a number key or control keys
    const isDigit = keyCode >= 48 && keyCode <= 57; // KeyCode 0-9
    const isNumberPad = keyCode >= 96 && keyCode <= 105; // KeyCode number pad

    // Allow control keys: backspace, tab, enter, escape, delete, arrows
    const isControlKey = [
        8,   // Backspace
        9,   // Tab
        13,  // Enter
        27,  // Escape
        46,  // Delete
        35,  // End
        36,  // Home
        37,  // Left Arrow
        38,  // Up Arrow
        39,  // Right Arrow
        40   // Down Arrow
    ].includes(keyCode);

    // Allow Cmd, Ctrl, or Shift combinations (like Cmd+A, Cmd+V, Cmd+R)
    const isModifierKey = event?.metaKey || event?.ctrlKey || event?.shiftKey;

    return isDigit || isNumberPad || isControlKey || isModifierKey || isRefreshCombination;
};

