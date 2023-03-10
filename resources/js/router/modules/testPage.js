import Layout from '@/layout';

const testPageRoutes = {
  path: '/test-page',
  component: Layout,
  redirect: '/test-page',
  meta: {
    title: 'Test Page',
    icon: 'zip',
  },
  children: [
    {
      path: '',
      component: () => import('@/views/test-page/index'),
      name: 'testPage',
      meta: {
        title: 'Master Kbli',
        icon: 'documentation',
      },
    },
    {
      path: '/create-test',
      component: () => import('@/views/test-page/createTest'),
      name: 'createTest',
      meta: {
        title: 'Create KBLI',
        icon: 'documentation',
      },
    },
    {
      path: ':id(\\d+)',
      component: () => import('@/views/test-page/param/index'),
      name: 'ListBusinessEnvParam',
      hidden: true,
      meta: {
        title: 'masterParameterKbli',
        icon: 'documentation',
      },
    },
  ],
};

export default testPageRoutes;
