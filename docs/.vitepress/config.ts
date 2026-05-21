import { defineConfig } from 'vitepress'

function unifiedSidebar() {
  return [
    {
      text: 'Getting Started',
      items: [
        { text: 'Overview', link: '/guide/' },
        { text: '5-min Quick Start', link: '/guide/quick-start' },
        { text: 'Installation', link: '/guide/install' },
        { text: 'Upgrade Guide', link: '/guide/upgrade' },
        { text: 'Shims', link: '/guide/shims' },
        { text: 'Tools Backend', link: '/guide/backend' },
        { text: 'Plugin Ecosystem', link: '/guide/ecosystem' },
      ],
    },
    {
      text: 'Behaviors',
      collapsed: true,
      items: [
        { text: 'Overview', link: '/behavior/' },
        { text: 'AfterSave', link: '/behavior/after-save' },
        { text: 'Bitmasked', link: '/behavior/bitmasked' },
        { text: 'Encryption', link: '/behavior/encryption' },
        { text: 'Jsonable', link: '/behavior/jsonable' },
        { text: 'Passwordable', link: '/behavior/passwordable' },
        { text: 'Reset', link: '/behavior/reset' },
        { text: 'Slugged', link: '/behavior/slugged' },
        { text: 'String', link: '/behavior/string' },
        { text: 'Toggle', link: '/behavior/toggle' },
        { text: 'Typographic', link: '/behavior/typographic' },
      ],
    },
    {
      text: 'Helpers',
      collapsed: true,
      items: [
        { text: 'Overview', link: '/helper/' },
        { text: 'Common', link: '/helper/common' },
        { text: 'Format', link: '/helper/format' },
        { text: 'Form', link: '/helper/form' },
        { text: 'Html', link: '/helper/html' },
        { text: 'Icon', link: '/helper/icon' },
        { text: 'Meter', link: '/helper/meter' },
        { text: 'Progress', link: '/helper/progress' },
        { text: 'Tree', link: '/helper/tree' },
        { text: 'Typography', link: '/helper/typography' },
      ],
    },
    {
      text: 'Components',
      collapsed: true,
      items: [
        { text: 'Overview', link: '/component/' },
        { text: 'Common', link: '/component/common' },
        { text: 'Mobile', link: '/component/mobile' },
        { text: 'RefererRedirect', link: '/component/referer-redirect' },
      ],
    },
    {
      text: 'Model & Entity',
      collapsed: true,
      items: [
        { text: 'Overview', link: '/model/' },
        { text: 'Table', link: '/model/table' },
        { text: 'Tokens', link: '/model/tokens' },
        { text: 'Enum', link: '/model/enum' },
        { text: 'StaticEnum', link: '/model/static-enum' },
      ],
    },
    {
      text: 'Reference',
      collapsed: true,
      items: [
        { text: 'Overview', link: '/reference/' },
        { text: 'Login Links', link: '/reference/login-link' },
        { text: 'Email', link: '/reference/email' },
        { text: 'Controller', link: '/reference/controller' },
        { text: 'Inflect Command', link: '/reference/inflect' },
        { text: 'I18n', link: '/reference/i18n' },
        { text: 'DateTime', link: '/reference/date-time' },
        { text: 'URL', link: '/reference/url' },
        { text: 'ExceptionTrap', link: '/reference/exception-trap' },
        { text: 'FileLog', link: '/reference/file-log' },
        { text: 'Datalist Widget', link: '/reference/datalist' },
      ],
    },
  ]
}

export default defineConfig({
  title: 'cakephp-tools',
  description: 'The CakePHP Toolbox: behaviors, helpers, components, model and entity utilities for CakePHP applications.',
  base: '/cakephp-tools/',
  cleanUrls: true,
  lastUpdated: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-tools/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-tools/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
      { text: 'Reference', link: '/reference/', activeMatch: '/(reference|behavior|helper|component|model)/' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-tools' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-tools' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-tools/issues' },
        ],
      },
    ],
    sidebar: {
      '/': unifiedSidebar(),
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-tools' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-tools/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})
