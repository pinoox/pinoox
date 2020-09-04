/** global: PINOOX */

import 'bootstrap';
import 'simplebar';
import $ from 'jquery';
import Vue from 'vue';
import "./mixins/global";
import axios from 'axios';
import axiosMethodOverride from 'axios-method-override';
import VueAxios from 'vue-axios';
import store from './store';
import Main from '../vue/main.vue';
import router from './router';
import ToggleButton from 'vue-js-toggle-button'

axios.defaults.headers['Content-Type'] = 'application/x-www-form-urlencoded';
axiosMethodOverride(axios);
const instance = axios.create();
axiosMethodOverride(instance);
Vue.use(ToggleButton);
Vue.use(VueAxios, axios);
__webpack_public_path__ = PINOOX.URL.THEME + 'dist/';

new Vue({
    el: '#app',
    render: h => h(Main),
    router: router,
    store: store,

});

$(document).on('click', '#pin-dock .handler', function () {

    if ($('#pin-dock .apps-holder').height() == '400') {
        //close pin dock
        $('#pin-dock .apps-holder').css('height', '100px');
        $('#pin-dock .apps-holder').css('overflow-y', 'hidden');
        //change handler icon
        $(this).html('</i>').html('<i class="fas fa-expand-arrows-alt"></i>');
    } else {
        //open pin dock
        $('#pin-dock .apps-holder').css('height', '400px');
        $('#pin-dock .apps-holder').css('overflow-y', 'scroll');
        //change handler icon
        $(this).html('</i>').html('<i class="fas fa-times"></i>');
    }

});
$('#pin-dock .app-item').on('click', function () {
    $('.app-details').removeClass('animated zoomOutDown').addClass('animated zoomInDown');
});
$('.close-details').on('click', function () {
    $('.app-details').removeClass('animated zoomInDown').addClass('animated zoomOutDown fast');
});