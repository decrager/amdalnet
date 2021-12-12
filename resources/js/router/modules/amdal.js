import Layout from '@/layout';

const amdalRoutes = {
  path: '/amdal',
  component: Layout,
  redirect: '/amdal',
  alwaysShow: true,
  hidden: true,
  meta: { title: 'AMDAL', icon: 'zip' },
  children: [
    {
      path: ':id(\\d+)/formulir',
      component: () => import('@/views/ukl-upl/FormulirUklUpl'),
      name: 'FormulirUklUpl',
      hidden: false,
      meta: { title: 'Asistensi Pelingkupan', icon: 'documentation' },
    },
    {
      path: ':id(\\d+)/matriks',
      component: () => import('@/views/ukl-upl/MatriksUklUpl'),
      name: 'MatriksUklUpl',
      hidden: false,
      meta: { title: 'Asistensi Pelingkupan', icon: 'documentation' },
    },
    {
      path: ':id(\\d+)/dokumen',
      component: () => import('@/views/ukl-upl/DokumenUklUpl'),
      name: 'DokumenUklUpl',
      hidden: false,
      meta: { title: 'Asistensi Pelingkupan', icon: 'documentation' },
    },
  ],
};

export default amdalRoutes;
