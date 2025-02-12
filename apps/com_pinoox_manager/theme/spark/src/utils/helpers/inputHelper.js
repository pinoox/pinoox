export function preview(file, callback) {
    if (!(file instanceof Blob)) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        const previewURL = event.target.result;
        callback(previewURL);
    };
    reader.readAsDataURL(file);
}