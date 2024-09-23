// @ts-nocheck
import axios from 'axios';

import.meta.glob(['../images/**']);

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
