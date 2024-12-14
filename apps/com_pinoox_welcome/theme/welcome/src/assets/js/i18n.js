const files = import.meta.glob('../../lang/*',{ eager: true });
let messages = {}
for (let key in files) {
    let filename = key.toString().replace(/^.*[\\/]/, '').replace(/.(json|js|ts)/,'');
    messages[filename] = files[key].default;
}

export default messages