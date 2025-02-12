// vite.plugins.js
import { fileURLToPath, URL } from 'node:url';

export default function ChunkManage() {
  return {
    name: 'vite-chunk-manage',
    buildStart() {
      this.addWatchFile(fileURLToPath(new URL('node_modules', import.meta.url)));
    },
    async generateBundle(options, bundle) {
      const vendorModules = Object.keys(bundle)
          .filter((key) => bundle[key].isEntry && bundle[key].modules)
          .map((key) => bundle[key].modules)
          .flat()
          .filter((module) => module && module.id && module.id.startsWith && module.id.startsWith('node_modules/'));

      const chunkGroups = options.chunkFileNames;
      for (const vendorModule of vendorModules) {
        for (const chunkGroup of chunkGroups) {
          if (vendorModule.id && vendorModule.id.startsWith && vendorModule.id.startsWith(chunkGroup)) {
            vendorModule.fileName = 'vendor.js';
            break;
          }
        }
      }
    },
  };
}
