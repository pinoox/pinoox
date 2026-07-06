export function controlPanelAppPath(packageName, section = 'details') {
    const pkg = encodeURIComponent(packageName);
    const suffix = section && section !== 'details' ? `/${section}` : '/details';

    return `/control/apps/${pkg}${suffix}`;
}
