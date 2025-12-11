import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'TestLink',
  description: 'Bidirectional Test-Method Linking for PHP (Pest & PHPUnit)',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/testlink-logo.svg' }],
  ],

  themeConfig: {
    logo: '/testlink-logo.svg',

    nav: [
      { text: 'Documentation', link: '/introduction/what-is-testlink' },
      {
        text: 'Ecosystem',
        items: [
          { text: 'test-attributes', link: 'https://github.com/testflowlabs/test-attributes' },
          { text: 'pest-plugin-bdd', link: 'https://github.com/testflowlabs/pest-plugin-bdd' },
        ]
      },
      {
        text: 'Links',
        items: [
          { text: 'Pest PHP', link: 'https://pestphp.com' },
          { text: 'PHPUnit', link: 'https://phpunit.de' },
        ]
      }
    ],

    sidebar: [
      {
        text: 'Introduction',
        collapsed: false,
        items: [
          { text: 'What is TestLink?', link: '/introduction/what-is-testlink' },
          { text: 'Installation', link: '/introduction/installation' },
          { text: 'Quick Start', link: '/introduction/quick-start' },
        ]
      },
      {
        text: 'Guide',
        collapsed: false,
        items: [
          { text: 'Test Coverage Links', link: '/guide/test-coverage-links' },
          { text: 'Linking from Tests', link: '/guide/covers-method-helper' },
          { text: 'Placeholder Pairing', link: '/guide/placeholder-pairing' },
          { text: 'CLI Commands', link: '/guide/cli-commands' },
          { text: 'Validation', link: '/guide/validation' },
        ]
      },
      {
        text: 'Auto-Sync',
        collapsed: false,
        items: [
          { text: 'Overview', link: '/sync/' },
          { text: 'Sync Command', link: '/sync/sync-command' },
          { text: 'Dry Run Mode', link: '/sync/dry-run' },
          { text: 'Pruning Orphans', link: '/sync/pruning' },
        ]
      },
      {
        text: 'Workflow',
        collapsed: false,
        items: [
          { text: 'TDD with TestLink', link: '/workflow/tdd' },
          { text: 'BDD with TestLink', link: '/workflow/bdd' },
        ]
      },
      {
        text: 'Best Practices',
        collapsed: false,
        items: [
          { text: 'Overview', link: '/best-practices/' },
          { text: 'Naming Conventions', link: '/best-practices/naming-conventions' },
          { text: 'Test Organization', link: '/best-practices/test-organization' },
          { text: 'CI Integration', link: '/best-practices/ci-integration' },
        ]
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/testflowlabs/testlink' }
    ],

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/testflowlabs/testlink/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024 TestFlowLabs'
    }
  }
})
