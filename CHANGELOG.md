# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## Unreleased

- Nothing so far

## [1.5.1](https://github.com/creecros/Mailmagik/releases/tag/1.5.1) - 2024-09-03

### Fixed

- [#47](https://github.com/creecros/Mailmagik/issues/47) Task mail parsing collides with CommentOnTask, makes it non-functional.

## [1.5.0](https://github.com/creecros/Mailmagik/releases/tag/1.5.0) - 2024-08-19

### Added

- [#46](https://github.com/creecros/Mailmagik/issues/46) Allow custom textarea fields with multi-line content.
- When parsing dates from email body, a textual representation can be used instead of a date.

### Changed

- Exclude MetaMagik fields from parsing if the Plugin is not installed.

## [1.4.0](https://github.com/creecros/Mailmagik/releases/tag/1.4.0) - 2024-07-30

### Added

- [#45](https://github.com/creecros/Mailmagik/issues/45) Cronjob Link just for Mailmagik, allowing to invoke mailmagik:fetchmail from webcron systems.

- [#40](https://github.com/creecros/Mailmagik/issues/40) Send notification for incoming task emails. The mailbox is scanned by the regular mailmagik:fetchmail cron job.

- [#41](https://github.com/creecros/Mailmagik/issues/41) Config option to select the server encoding, for compatibility with Exchange IMAP.

- [#42](https://github.com/creecros/Mailmagik/issues/42) Subject parsing: Allow multi-word attribute values for columns, categories and tags, by quoting them with any of "", '', ‘’, “”, «» and „“.

### Changed

- Replaced the specific task creation mail by a generic notification. The mailto links are now integrated into the usual notification email for all created tasks. The former implementation did only notify the creator of the new task.

- Reordered the CHANGELOG, the newest items are always on top.

### Fixed

- [#44](https://github.com/creecros/Mailmagik/issues/44) Task attributes are not removed from task email subject.

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
