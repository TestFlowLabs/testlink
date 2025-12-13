import { defineConfig } from 'vitepress'
import { tabsMarkdownPlugin } from 'vitepress-plugin-tabs'

export default defineConfig({
  title: 'TestLink',
  description: 'Bidirectional Test-Method Linking for PHP (Pest & PHPUnit)',

  markdown: {
    config(md) {
      md.use(tabsMarkdownPlugin)
    }
  },

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/testlink-logo.svg' }],
  ],

  themeConfig: {
    logo: '/testlink-logo.svg',

    nav: [
      { text: 'Docs', link: '/tutorials/getting-started' },
      {
        text: 'Ecosystem',
        items: [
          { text: 'test-attributes', link: 'https://github.com/testflowlabs/test-attributes' },
          { text: 'pest-plugin-bdd', link: 'https://github.com/testflowlabs/pest-plugin-bdd' },
        ]
      },
    ],

    sidebar: [
      {
        text: 'Tutorials',
        collapsed: false,
        items: [
          { text: 'Getting Started', link: '/tutorials/getting-started' },
          { text: 'Your First Bidirectional Link', link: '/tutorials/first-bidirectional-link' },
          { text: 'Understanding Reports', link: '/tutorials/understanding-reports' },
        ]
      },
      {
        text: 'How-to Guides',
        collapsed: false,
        items: [
          {
            text: 'Basic Tasks',
            collapsed: false,
            items: [
              { text: 'Add Links to Existing Tests', link: '/how-to/add-links-to-existing-tests' },
              { text: 'Add #[TestedBy] to Production', link: '/how-to/add-testedby-to-production' },
              { text: 'Run Validation in CI', link: '/how-to/run-validation-in-ci' },
              { text: 'Fix Validation Errors', link: '/how-to/fix-validation-errors' },
            ]
          },
          {
            text: 'Sync & Placeholder',
            collapsed: false,
            items: [
              { text: 'Sync Links Automatically', link: '/how-to/sync-links-automatically' },
              { text: 'Use Dry-Run Mode', link: '/how-to/use-dry-run-mode' },
              { text: 'Resolve Placeholders', link: '/how-to/resolve-placeholders' },
              { text: 'Prune Orphan Links', link: '/how-to/prune-orphan-links' },
              { text: 'Handle N:M Relationships', link: '/how-to/handle-nm-relationships' },
            ]
          },
          {
            text: 'Workflows',
            collapsed: false,
            items: [
              {
                text: 'TDD',
                collapsed: true,
                items: [
                  { text: 'Introduction', link: '/workflows/tdd/' },
                  { text: 'Red-Green-Refactor', link: '/workflows/tdd/red-green-refactor' },
                  { text: 'Placeholders', link: '/workflows/tdd/placeholders' },
                  { text: 'Complete Example', link: '/workflows/tdd/complete-example' },
                  { text: 'Why TDD with Links?', link: '/workflows/tdd/why-tdd-with-links' },
                  { text: 'When to Add Links', link: '/workflows/tdd/when-to-add-links' },
                ]
              },
              {
                text: 'BDD',
                collapsed: true,
                items: [
                  { text: 'Introduction', link: '/workflows/bdd/' },
                  { text: 'Double-Loop', link: '/workflows/bdd/double-loop' },
                  { text: 'Acceptance to Unit', link: '/workflows/bdd/acceptance-to-unit' },
                  { text: 'Placeholders', link: '/workflows/bdd/placeholders' },
                  { text: 'Complete Example', link: '/workflows/bdd/complete-example' },
                  { text: 'Acceptance vs Unit Links', link: '/workflows/bdd/acceptance-vs-unit' },
                  { text: 'Placeholder Concepts', link: '/workflows/bdd/placeholder-concepts' },
                ]
              },
            ]
          },
          {
            text: 'Advanced',
            collapsed: false,
            items: [
              { text: 'Migrate Existing Project', link: '/how-to/migrate-existing-project' },
              { text: 'Setup IDE Navigation', link: '/how-to/setup-ide-navigation' },
              { text: 'Organize Tests with Describe', link: '/how-to/organize-tests-with-describe' },
              { text: 'Use @see Tags', link: '/how-to/use-see-tags' },
              { text: 'Debug Parsing Issues', link: '/how-to/debug-parsing-issues' },
            ]
          },
        ]
      },
      {
        text: 'Explanation',
        collapsed: false,
        items: [
          { text: 'Bidirectional Linking', link: '/explanation/bidirectional-linking' },
          { text: 'Two-Package Architecture', link: '/explanation/two-package-architecture' },
          { text: 'Links vs LinksAndCovers', link: '/explanation/links-vs-linksandcovers' },
          { text: 'Placeholder Strategy', link: '/explanation/placeholder-strategy' },
          { text: 'Test Traceability', link: '/explanation/test-traceability' },
        ]
      },
      {
        text: 'Reference',
        collapsed: false,
        items: [
          {
            text: 'CLI Commands',
            collapsed: true,
            items: [
              { text: 'Overview', link: '/reference/cli/' },
              { text: 'report', link: '/reference/cli/report' },
              { text: 'validate', link: '/reference/cli/validate' },
              { text: 'sync', link: '/reference/cli/sync' },
              { text: 'pair', link: '/reference/cli/pair' },
            ]
          },
          {
            text: 'Attributes',
            collapsed: true,
            items: [
              { text: 'Overview', link: '/reference/attributes/' },
              { text: '#[TestedBy]', link: '/reference/attributes/testedby' },
              { text: '#[LinksAndCovers]', link: '/reference/attributes/linksandcovers' },
              { text: '#[Links]', link: '/reference/attributes/links' },
            ]
          },
          { text: 'Pest Methods', link: '/reference/pest-methods' },
          { text: 'Configuration', link: '/reference/configuration' },
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
