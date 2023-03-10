import { logout, getInfo } from '@/api/auth';
// import axios from 'axios';
import { isLogged, setLogged, removeToken, removeIsOSS } from '@/utils/auth';
import router, { resetRouter } from '@/router';
import store from '@/store';
import axios from 'axios';

const state = {
  id: null,
  user: null,
  token: isLogged(),
  name: '',
  avatar: '',
  introduction: '',
  roles: [],
  permissions: [],
  notifications: [],
  page: 'login',
};

const mutations = {
  SET_ID: (state, id) => {
    state.id = id;
  },
  SET_TOKEN: (state, token) => {
    state.token = token;
  },
  SET_INTRODUCTION: (state, introduction) => {
    state.introduction = introduction;
  },
  SET_NAME: (state, name) => {
    state.name = name;
  },
  SET_AVATAR: (state, avatar) => {
    state.avatar = avatar;
  },
  SET_ROLES: (state, roles) => {
    state.roles = roles;
  },
  SET_PERMISSIONS: (state, permissions) => {
    state.permissions = permissions;
  },
  SET_NOTIFICATIONS: (state, notifications) => {
    state.notifications = notifications;
  },
  SET_USER: (state, user) => {
    state.user = user;
  },
  SET_PAGE_REGISTER: (state, page) => {
    state.page = page;
  },
};

const actions = {
  // user login
  login({ commit }, userInfo) {
    const { email, password } = userInfo;
    return new Promise((resolve, reject) => {
      axios.post('/api/auth/login', { email: email.trim(), password: password })
        .then(response => {
          setLogged('1');
          resolve();
        })
        .catch(error => {
          console.log(error);
          reject(error);
        });
    });
  },

  loginOss({ commit }, userInfo) {
    const { username, password } = userInfo;
    return new Promise((resolve, reject) => {
      axios.post('/api/auth/sso/login', { username: username.trim(), password: password })
        .then(response => {
          const { data } = response;
          if (!data) {
            reject('Verification failed, please Login again.');
          }
          resolve(data);
        })
        .catch(error => {
          console.log(error);
          reject(error);
        });
    });
  },

  setPage({ commit }, page) {
    commit('SET_PAGE_REGISTER', page);
  },

  // get user info
  getInfo({ commit, state, dispatch }) {
    return new Promise((resolve, reject) => {
      getInfo()
        .then(response => {
          const { data } = response;

          if (!data) {
            reject('Verification failed, please Login again.');
          }

          const { roles, name, avatar, introduction, permissions, id } = data;
          // roles must be a non-empty array
          if (!roles || roles.length <= 0) {
            reject('getInfo: roles must be a non-null array!');
          }

          commit('SET_ROLES', roles);
          commit('SET_PERMISSIONS', permissions);
          // commit('SET_NOTIFICATIONS', notifications);
          commit('SET_NAME', name);
          commit('SET_AVATAR', avatar);
          commit('SET_INTRODUCTION', introduction);
          commit('SET_ID', id);
          commit('SET_USER', data);
          resolve(data);
          dispatch('getNotifications', 5);
        })
        .catch(error => {
          // console.log(error.code);
          // if (!axios.isCancel(error)) {
          if (error.response && error.response.status === 401) {
            reject(error);
          }
        });
    });
  },

  // user logout
  logout({ commit }) {
    return new Promise((resolve, reject) => {
      logout()
        .then(() => {
          commit('SET_TOKEN', '');
          commit('SET_ROLES', []);
          removeToken();
          removeIsOSS();
          resetRouter();
          resolve();
        })
        .catch(error => {
          reject(error);
        });
    });
  },

  // remove token
  resetToken({ commit }) {
    return new Promise(resolve => {
      commit('SET_TOKEN', '');
      commit('SET_ROLES', []);
      removeToken();
      removeIsOSS();
      resolve();
    });
  },

  // Dynamically modify permissions
  changeRoles({ commit, dispatch }, role) {
    return new Promise(async resolve => {
      // const token = role + '-token';

      // commit('SET_TOKEN', token);
      // setLogged(token);

      // const { roles } = await dispatch('getInfo');

      const roles = [role.name];
      const permissions = role.permissions.map(permission => permission.name);
      commit('SET_ROLES', roles);
      commit('SET_PERMISSIONS', permissions);
      resetRouter();

      // generate accessible routes map based on roles
      const accessRoutes = await store.dispatch('permission/generateRoutes', { roles, permissions });

      // dynamically add accessible routes
      router.addRoutes(accessRoutes);

      resolve();
    });
  },
  // get notifications
  getNotifications({ commit }, limit) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/user/notifications?limit=${limit}`)
        .then(response => {
          commit('SET_NOTIFICATIONS', response.data);
          resolve();
        })
        .catch(error => {
          console.log(error);
          reject(error);
        });
    });
  },
};

export default {
  namespaced: true,
  state,
  mutations,
  actions,
};
