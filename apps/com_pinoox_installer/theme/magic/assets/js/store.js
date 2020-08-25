/** global: PINOOX */

import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        LANG:PINOOX.LANG,
        OPTIONS:PINOOX.OPTIONS,
        db:{
            host: 'localhost',
            database: 'pinoox',
            username: 'root',
            password: '',
            prefix: '',
        },
        user:{
            fname:'',
            lname:'',
            username:'',
            email:'',
            password:'',
        }
    },
});