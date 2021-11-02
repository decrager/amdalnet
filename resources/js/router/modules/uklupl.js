import Layout from '@/layout';

const uklUplRoutes = {
  path: '/ukl-upl',
  component: Layout,
  redirect: '/ukl-upl',
  alwaysShow: true,
  hidden: true,
  meta: { title: 'UKL-UPL', icon: 'zip' },
  children: [
    {
      path: ':id(\\d+)/formulir',
      component: () => import('@/views/ukl-upl/FormulirUklUpl'),
      name: 'FormulirUklUpl',
      hidden: false,
      meta: { title: 'Formulir UKL-UPL', icon: 'documentation' },
    },
    {
      path: ':id(\\d+)/matriks',
      component: () => import('@/views/ukl-upl/MatriksUklUpl'),
      name: 'MatriksUklUpl',
      hidden: false,
      meta: { title: 'Matriks UKL-UPL', icon: 'documentation' },
    },
    {
      path: ':id(\\d+)/dokumen',
      component: () => import('@/views/ukl-upl/DokumenUklUpl'),
      name: 'DokumenUklUpl',
      hidden: false,
      meta: { title: 'Dokumen UKL-UPL', icon: 'documentation' },
    },
  ],
};

export default uklUplRoutes;
