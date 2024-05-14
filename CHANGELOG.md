# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## Unreleased

### Added

- [#42](https://github.com/creecros/Mailmagik/issues/42) Subject parsing: Allow multi-word attribute values for columns, categories and tags, by quoting them with any of "", '', ‘’, “”, «» and „“.

### Changed

- Reordered the CHANGELOG, the newest items are always on top.

## [1.3.0](https://github.com/creecros/Mailmagik/releases/tag/1.3.0) - 2024-07-03

### Added

- Ability to automatically parse and apply task attribute- and meta-data from message body, for convert to task mails and task mails. This feature is optional.

- Option to send notification email after creation. Please note that this is a temporary feature. It will be replaced by a more generic solution in the near future.

## [1.2.3](https://github.com/creecros/Mailmagik/releases/tag/1.2.3) - 2023-07-18

### Fixed

- [#34](https://github.com/creecros/Mailmagik/issues/34) Mailmagik broken due to Symfony updates

### Added

- [README] Hint that the sender of the mail must be a member of the targeted project.

## [1.2.2](https://github.com/creecros/Mailmagik/releases/tag/1.2.2) - 2023-03-08

### Added

- [#24](https://github.com/creecros/Mailmagik/discussions/24) Added advanced option for auth/security flags
- [#25](https://github.com/creecros/Mailmagik/discussions/25) Added Subject parsing options for actions
- [#25](https://github.com/creecros/Mailmagik/discussions/25) Added ability to set column name during parse

### Fixed

- [#26](https://github.com/creecros/Mailmagik/issues/26) Fix fatal error when subject is null

## [1.2.1](https://github.com/creecros/Mailmagik/releases/tag/1.2.1) - 2023-03-01

### Added
-  [#21](https://github.com/creecros/Mailmagik/issues/21) Allow the use of IMAP-subfolders
-  [#22](https://github.com/creecros/Mailmagik/issues/22) Added options for including or not including attachments when convert to tasks or comments, both in task emails or automatic conversion

### Fixes
- (https://github.com/creecros/Mailmagik/commit/7e467019d03905700a2494a516ab1a0f934c3664) Change HTML to markdown conversion and remove erroneuos code

## [1.2.0](https://github.com/creecros/Mailmagik/releases/tag/1.2.0) - 2023-02-24

### Added

- [#20](https://github.com/creecros/Mailmagik/issues/20) Create an own cronjob, for mail fetching only.
- https://github.com/creecros/Mailmagik/commit/221a6fb929e5298b9e4acb0748dcb2f9898c34fd Rework Config Setting to Check box
- https://github.com/creecros/Mailmagik/commit/eb2623e59664cfecfb301e107f8af61fcd44313d Add MailHelper

## [1.1.1](https://github.com/creecros/Mailmagik/releases/tag/1.1.1) - 2023-02-14

### Fixed

- [#16](https://github.com/creecros/Mailmagik/issues/16) To README.md: There must be at least 1 open task in a project for the automatic actions to work.
- [#17](https://github.com/creecros/Mailmagik/issues/17)  PHP 8 Deprecated Warnings.

## [1.1.0](https://github.com/creecros/Mailmagik/releases/tag/1.1.0) - 2023-02-09

Initial release
