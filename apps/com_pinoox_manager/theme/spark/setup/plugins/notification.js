const notifier = require('node-notifier');

class Notification{
    apply(compiler){
        compiler.plugin('done',this.send);
    }

    send(stats)
    {
        const time = ((stats.endTime - stats.startTime) / 1000).toFixed(2);

        notifier.notify({
            title: 'Pinoox',
            message: `Webpack is done!\n${ stats.compilation.errors.length } errors in time ${time}`,
            icon: __dirname + '/icon.png',
        });
    }
}

module.exports = Notification;