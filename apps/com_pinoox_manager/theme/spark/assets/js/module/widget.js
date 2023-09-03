import moment from 'moment';

export default {
    state:{
        clock:{
            data:'',
            time:0,
            percent:0,
        },

    },
    mutations:{
        startClock:(state)=>{
            return setInterval(() => {
                state.clock.data.time++;
                let timestamp = state.clock.data.time;
                let time = moment.unix(timestamp).format('h:mm');
                let s = moment.unix(timestamp).format('ss');
                state.clock.percent = (parseInt(s) / 60) * 100;

                if (time !== state.clock.time) {
                    state.clock.time = time;
                }
            }, 1000);
        }
    }
}