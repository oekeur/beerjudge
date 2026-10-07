---
id: TASK-53
title: 'Only prompt for member:* arguments that were not passed'
status: Backlog
assignee: []
created_date: '2026-10-07 15:10'
labels:
  - docs
dependencies: []
priority: low
type: chore
ordinal: 53000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
member:create, member:delete and member:password always call ask('Name'/'Email') and ignore the answer when the option or argument is given. Docs now describe this; the code is the odd part.
<!-- SECTION:DESCRIPTION:END -->
