---
layout: home

hero:
  name: cakephp-tools
  text: The CakePHP Toolbox
  tagline: Stop reinventing slugs, password flows, bitmasks, and the dozen helpers you copy into every CakePHP app.
  image:
    src: /logo.svg
    alt: cakephp-tools
  actions:
    - theme: brand
      text: 5-min Quick Start
      link: /guide/quick-start
    - theme: alt
      text: Live Sandbox
      link: https://sandbox.dereuromark.de/sandbox/tools-examples
    - theme: alt
      text: View on GitHub
      link: https://github.com/dereuromark/cakephp-tools

features:
  - icon: 🧱
    title: Behaviors you'd write yourself anyway
    details: Slugs, bitmasks, password change with confirm, JSON columns, soft toggles — ten battle-tested ORM behaviors instead of a folder full of half-finished traits.
  - icon: 🎨
    title: Your AppView, pre-loaded
    details: Format, Html, Form, Tree, Progress and a Common helper covering the rendering you keep adding to every project. Load two helpers and stop writing element wrappers.
  - icon: 🗃️
    title: A Table base that knows the things you forgot
    details: Validation rules core CakePHP doesn't ship (URL, email, phone, ranges), plus Tokens for one-time login links and native Enum integration.
  - icon: 🧩
    title: Controller boilerplate, gone
    details: Auto-trim POST data so empty validation behaves. Mobile detection. Safe redirect-to-referer with allow-listing. Three components instead of three new files in src/Controller/Component.
  - icon: 🌍
    title: i18n + URL helpers without the dance
    details: Detect and switch locale with one component call. Improved DateTime/Date handling. URL generation utilities for the cases the core helper doesn't cover.
  - icon: 🛠️
    title: Plus the rest
    details: ExceptionTrap for cleaner errors, FileLog for one-line custom logging, login-link auth flow, Datalist widget, Inflect command. The whole [plugin ecosystem](/guide/ecosystem) connects them.
---
