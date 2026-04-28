import _ from 'lodash';
window._ = _;

import $ from 'jquery';
import 'popper.js';
import 'bootstrap';

window.$ = $;
window.jQuery = $;

import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
