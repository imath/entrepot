# Change Log

## 1.5.5

_Requires WordPress 5.0_
_Tested up to WordPress 5.6_

### Bug fixes

- Avoids messing with the Block Editor `query_plugins` requests about block plugins.
- When the WordPress version installed on the site is >= 5.5, disable the feature to manage plugin versions in favor of the WordPress 5.5 manual plugin down/upgrade one.
- Set default values for WP/PHP version theme requirements to make sure it's still possible to install a Theme registered into the Entrepôt using the Customizer.
- Adds PHP/WP requirements management for Plugin updates.
- Adds PHP/WP requirements management for Block updates.

### Newly Registered Plugin

- [Réception](https://github.com/imath/reception/)
- [Communauté protégée](https://github.com/imath/communaute-protegee/)
- [Type de membres](https://github.com/imath/types-de-membre/)

---

## 1.5.4

_Requires WordPress 5.0_
_Tested up to WordPress 5.4_

### Bug fixes

- Fix the appearance of the action buttons used into the Plugin/Theme's modal.
- Fix the "Activités de publication" dependency descriptions into the repository's JSON file.

### Newly Registered Plugin

- [DocuThèques](https://github.com/imath/docutheques/)

---

## 1.5.3

_Requires WordPress 4.8_
_Tested up to WordPress 5.1_

### Bug fixes

- Fix 2 warnings into `Entrepot_REST_Blocks_Controller->prepare_item_for_response()`

### Newly Registered Block

- [Section](https://github.com/imath/section/)

---

## 1.5.2

_Requires WordPress 4.8_
_Tested up to WordPress 5.1_

### Bug fixes

- Check if the hook is a regular function or a class when dealing with admin notices.

### Props

@gregoirenoyelle

---

## 1.5.1

_Requires WordPress 4.8_
_Tested up to WordPress 5.1_

### Bug fixes

- Make sure `entrepot_blocks_dir()` is available even if WordPress version < 5.0
- Do not include `.babelrc` in built package.

### Props

@gregoirenoyelle

---

## 1.5.0

_Requires WordPress 4.8_
_Tested up to WordPress 5.1_

### Features

- Manage standalone Block Types from a specific Administration screen.
  - handle Block Type installations
  - handle Block Type updates
  - handle Block Type activations
  - handle Block Type deactivations
  - handle Block Type deletions
- Introduce a new Entrepôt Block Types API in order to use activated Block Types within the Block editor.
- Introduce a distant way of getting registered repositories using the GitHub API.

### Bug fixes

- Fix PHPUnit7 failings in Travis

### Newly Registered Block

- [Formulaire de Recherche](https://github.com/imath/formulaire-de-recherche/)
- [Entrepôt Test Block](https://github.com/imath/entrepot-test-block/) (Only loaded when `WP_DEBUG` is `true`)

---

## 1.4.2

_Requires WordPress 4.8_

### Bug fixes

- Remove the Gutenberg dependency for GutenBlocks and replace it with WordPress 5.0.

## 1.4.1

_Requires WordPress 4.8_

### Newly Registered Plugins

- Activités de Publication

---

## 1.4.0

_Requires WordPress 4.8_

### Features

- Theme installs/updates inside the customizer and from the regular Administration screens.

### Bug fixes

- Leave WordPress display regular w.org plugin details in the Thickbox.
- Make sure the zip file type check in Plugin overwrites is taking in account all zip mime types (eg: "application/x-zip-compressed").
- Use actions instead of filters when overring update transients.

### Newly Registered Themes

- Vingt DixSept

---

## 1.3.0

_Requires WordPress 4.8_

### Features

- Improve the Admin notifices center by adding a link to fully open notices.
- Update the URL to flag plugins.

### Bug fixes

- Fix a notice error when notice hooks are hooked from class methods.

---

## 1.2.1

_Requires WordPress 4.8_

### Newly Registered Plugins

- Gutenblocks
- WP Tuning

---

## 1.2.0

_Requires WordPress 4.8_

### Bug fixes

- Removes extra parenthesis when using require or require_once.

### Features

- More links to inform about Entrepôt repositories into the "More details" modal
- Use the Entrepôt repository icon into the WordPress Bulk update List Table.
- Manual WordPress plugin upgrade and downgrade.
- Restrictions to the WordPress Plugins Code Editor.

### Newly Registered Plugins

- Alternative Control for Public Group
- Profil de Groupes

---

## 1.1.0

_Requires WordPress 4.8_

### Bug fixes

- Fix a WordPress JavaScript error about Updates count into the Entrepôt Tab of the Install Plugins Administration screen.
- Improve version checking when a plugin is not using a standard version number.
- Multisite improvements to make available the plugin depedencies available for each blog of the network.

### Features

- Plugin dependencies management
- Admin Notices Center
- Upgrades API

### Newly Registered Plugins

- AD ACF Builder
- BP Idea Stream
- BP Reshare

---

## 1.0.0

_Requires WordPress 4.8_

### Features

Thanks to Entrepôt you can enjoy an alternative source of public & free Plugins that are hosted on GitHub.com. Once activated, you will be able to **browse, install, activate and upgrade** the registered plugins directly from your WordPress Administration.

### Registered Plugins

- Entrepôt
- MédiaThèque
- WP Idea Stream
- WP Statuses
