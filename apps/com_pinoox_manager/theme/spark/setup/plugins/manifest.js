const fs = require('fs');
const path = require('path');
let manifestName = '';

class Manifest
{
    constructor(filename)
    {
        manifestName = (filename === undefined)? 'manifest.json' : filename;
    }

    apply(compiler){
        compiler.plugin('emit',this.write_compilation);
        //compiler.plugin('done',this.write_fs);
    }

    write_fs(stats)
    {
        fs.writeFileSync(
            path.resolve('dist/'+manifestName),
            JSON.stringify(stats.toJson().assetsByChunkName)
        );
    }

    write_compilation (compilation,callback)
    {
        let manifest = JSON.stringify(compilation.getStats().toJson().assetsByChunkName);

        compilation.assets[manifestName] = {
            source: () => manifest,
            size: () => manifest.length,
        };

        callback();
    }
}

module.exports = Manifest;