import axios from 'axios';
import _ from 'lodash';

window._ = _;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
