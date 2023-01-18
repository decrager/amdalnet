import Layout from '@/layout';

const plChangesRoutes = {
  path: '/perubahan_pl',
  component: Layout,
  redirect: 'https://amdalnet-dev.menlhk.go.id/perubahan_pl',
  meta: {
    title: 'Perubahan PL',
    icon: 'excel',
    permissions: ['view menu digital workspace'],
  },
  children: [
    {
      path: '/perubahan_pl',
      beforeEnter() {
        location.href = 'https://amdalnet-dev.menlhk.go.id/perubahan_pl';
      },
      name: 'Perubahan PL',
      meta: { title: 'Perubahan PL', icon: 'excel' },
    },
  ],
};

export default plChangesRoutes;
