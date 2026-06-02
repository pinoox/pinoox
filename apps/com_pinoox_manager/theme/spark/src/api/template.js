import {http} from "@global";

const BASE_URL = '/template';

export const templateAPI = {
    get: (packageName) => http.get(`${BASE_URL}/get/${packageName}`, {alert: false}),
    install: (uid, packageName) => http.get(`${BASE_URL}/install/${uid}/${packageName}`),
    installPackage: (filename) => http.get(`${BASE_URL}/installPackage/${filename}`),
    set: (packageName, folderName) => http.get(`${BASE_URL}/set/${packageName}/${folderName}`),
    remove: (packageName, folderName) => http.get(`${BASE_URL}/remove/${packageName}/${folderName}`),
};
