export function applyDirection(direction, lang) {
    if (direction) {
        document.documentElement.dir = direction
        document.body.className = direction
    }

    if (lang) {
        document.documentElement.lang = lang
    }
}
