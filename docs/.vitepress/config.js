import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'BeerJudge',
  description: 'Competition management for a small homebrew club.',
  base: '/beerjudge/',
  ignoreDeadLinks: [/README/],

  themeConfig: {
    nav: [
      { text: 'Judging', link: '/judging/reference' },
      { text: 'Competition', link: '/competition/lifecycle-reference' },
      { text: 'Members', link: '/member/registration-reference' },
      { text: 'Admin', link: '/admin/artisan-commands-reference' },
      { text: 'Architecture', link: '/architecture/two-frontend-explanation' },
      { text: 'Operations', link: '/env-reference' },
    ],

    sidebar: [
      {
        text: 'Judging',
        items: [
          { text: 'Reference', link: '/judging/reference' },
          { text: 'How to judge', link: '/judging/howto-judge' },
          { text: 'Offline sync', link: '/judging/explanation-offline-sync' },
          { text: 'DescriptorService reference', link: '/judging/descriptor-service-reference' },
          { text: 'Why descriptors are JSON', link: '/judging/descriptor-service-explanation' },
        ],
      },
      {
        text: 'Competition',
        items: [
          { text: 'Lifecycle reference', link: '/competition/lifecycle-reference' },
          { text: 'How to run a competition', link: '/competition/lifecycle-howto' },
          { text: 'Why four states', link: '/competition/lifecycle-explanation' },
          { text: 'Results reference', link: '/competition/results-reference' },
          { text: 'How to view results', link: '/competition/results-howto' },
          { text: 'Ranking service reference', link: '/competition/ranking-service-reference' },
          { text: 'Why ranking works this way', link: '/competition/ranking-service-explanation' },
          { text: 'Bottle assignment reference', link: '/competition/bottle-assignment-reference' },
          { text: 'Why bottle assignment is separate', link: '/competition/bottle-assignment-explanation' },
        ],
      },
      {
        text: 'Members',
        items: [
          { text: 'Registration reference', link: '/member/registration-reference' },
          { text: 'How to register', link: '/member/registration-howto' },
          { text: 'Auth reference', link: '/member/auth-reference' },
          { text: 'How to log in', link: '/member/auth-howto' },
          { text: 'Magic link reference', link: '/member/magic-link-reference' },
          { text: 'Active member middleware', link: '/member/active-member-middleware-reference' },
        ],
      },
      {
        text: 'Admin',
        items: [
          { text: 'Artisan commands', link: '/admin/artisan-commands-reference' },
          { text: 'How to create a member via CLI', link: '/admin/artisan-commands-howto' },
          { text: 'BJCP seeder reference', link: '/admin/bjcp-seeder-reference' },
          { text: 'How to seed BJCP styles', link: '/admin/bjcp-seeder-howto' },
          { text: 'Why Filament', link: '/admin/filament-overview-explanation' },
        ],
      },
      {
        text: 'Architecture',
        items: [
          { text: 'Two-frontend architecture', link: '/architecture/two-frontend-explanation' },
          { text: 'BJCP scoring model', link: '/architecture/bjcp-scoring-explanation' },
        ],
      },
      {
        text: 'Operations',
        items: [
          { text: 'Environment variables', link: '/env-reference' },
          { text: 'Docker reference', link: '/docker-reference' },
          { text: 'How to deploy with Docker', link: '/docker-howto' },
          { text: 'PWA reference', link: '/pwa-reference' },
        ],
      },
    ],

    search: {
      provider: 'local',
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/oscar/beerjudge' },
    ],

    editLink: {
      pattern: 'https://github.com/oekeur/beerjudge/edit/main/docs/:path',
      text: 'Edit this page',
    },
  },
})
