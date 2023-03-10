import router from './router';
import store from './store';
import { Message } from 'element-ui';
import NProgress from 'nprogress'; // progress bar
import 'nprogress/nprogress.css'; // progress bar style
import { isLogged } from '@/utils/auth';
import getPageTitle from '@/utils/get-page-title';

NProgress.configure({ showSpinner: false }); // NProgress Configuration

const whiteList = [
  '/pre-login',
  '/login',
  '/auth-redirect',
  '/home',
  '/',
  '/about',
  '/webgis',
  '/announce',
  '/doc-uklupl',
  '/activate/:id',
]; // no redirect whitelist

router.beforeEach(async(to, from, next) => {
  // start progress bar
  NProgress.start();
  // set page title
  document.title = getPageTitle(to.meta.title);

  // determine whether the user has logged in
  const isUserLogged = isLogged();
  const query = to.query.redirect;
  if (isUserLogged) {
    if (to.path === '/login') {
      if (to.fullPath === '/login') {
        next({ path: '/dashboard' });
      } else if (to.query.redirect.includes('/project/docspace')) {
        let document_type = 'ukl-upl';
        if (to.query.redirect.includes('ka-andal')) {
          document_type = 'ka-andal';
        } else if (to.query.redirect.includes('rkl-rpl')) {
          document_type = 'rkl-rpl';
        } else if (to.query.redirect.includes('ka')) {
          document_type = 'ka';
        }
        console.log({ doc_type: document_type });
        next({ path: to.query.redirect, query: {
          idProject: query.substring(query.indexOf('e/') + 2, query.lastIndexOf('/')),
          document_type: document_type,
        }});
      } else {
        next({ path: '/dashboard' });
      }
      NProgress.done();
    } else {
      // determine whether the user has obtained his permission roles through getInfo
      const hasRoles = store.getters.roles && store.getters.roles.length > 0;
      if (hasRoles) {
        next();
      } else {
        try {
          // get user info
          // note: roles must be a object array! such as: ['admin'] or ,['manager','editor']
          const { roles, permissions } = await store.dispatch('user/getInfo');
          // generate accessible routes map based on roles
          const accessRoutes = await store.dispatch('permission/generateRoutes', { roles, permissions });
          router.addRoutes(accessRoutes);
          next({ ...to, replace: true });
        } catch (error) {
          // remove token and go to login page to re-login
          await store.dispatch('user/resetToken');
          Message.error(error.message || 'Has Error');
          next(`/login?redirect=${to.path}`);
          NProgress.done();
        }
      }
    }
  } else {
    /* has no token*/

    if (whiteList.indexOf(to.matched[0] ? to.matched[0].path : '') !== -1 || to.path.includes('/oss')) {
      // in the free login whitelist, go directly
      next();
    } else {
      // other pages that do not have permission to access are redirected to the login page.
      next(`/login?redirect=${to.path}`);
      NProgress.done();
    }
  }
});

router.afterEach(() => {
  // finish progress bar
  NProgress.done();
});
