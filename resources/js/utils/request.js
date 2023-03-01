import '@/bootstrap';
import { Message } from 'element-ui';
// import { isLogged, setLogged } from '@/utils/auth';

// Create axios instance
const service = window.axios.create({
  baseURL: process.env.MIX_BASE_API,
  timeout: 300000, // Request timeout
});

// Request intercepter
service.interceptors.request.use(
  config => {
    // const token = isLogged();
    // if (token) {
    //   config.headers['Authorization'] = 'Bearer ' + isLogged(); // Set JWT token
    // }
    return config;
  },
  error => {
    // Do something with request error
    console.log(error); // for debug
    Promise.reject(error);
  }
);

// response pre-processing
service.interceptors.response.use(
  response => {
    // if (response.headers.authorization) {
    //   setLogged(response.headers.authorization);
    //   response.data.token = response.headers.authorization;
    // }

    return response.data;
  },
  error => {
    // let message = error.message;
    // if (error.response.data && error.response.data.errors) {
    //   message = error.response.data.errors;
    // } else if (error.response.data && error.response.data.error) {
    //   message = error.response.data.error;
    // }
    if (error.response.data.message === 'Unauthenticated.') {
      Message({
        // message: message,
        message: 'Sesi anda telah habis, Silahkan login ulang.',
        // message: error.response.data.message,
        type: 'error',
        duration: 10 * 1000,
      });
    } else if (error.response.data.message === 'CSRF token mismatch.') {
      Message({
        // message: message,
        message: 'Sesi anda telah habis, Silahkan refresh halaman.',
        // message: error.response.data.message,
        type: 'error',
        duration: 10 * 1000,
      });
    } else {
      Message({
        // message: message,
        message: 'Terjadi Kesalahan Dalam System, Silahkan Hubungi Administrator',
        type: 'error',
        duration: 5 * 1000,
      });
    }
    return Promise.reject(error);
  }
);

export default service;
