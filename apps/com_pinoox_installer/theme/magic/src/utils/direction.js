export function applyDirection(direction, lang) {
    const resolved = direction === 'rtl' ? 'rtl' : 'ltr'

    document.documentElement.dir = resolved
    document.documentElement.lang = lang || document.documentElement.lang

    document.body.classList.remove('ltr', 'rtl')
    document.body.classList.add(resolved)
}
