export function useInstaller() {
    function redirect(path, seconds) {
        setTimeout(() => {
            window.location = path
        }, seconds * 1000)
    }

    return {
        redirect,
    }
}
