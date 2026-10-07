---
id: TASK-51
title: Make 'Print all' on the results index do something or remove it
status: To Do
assignee: []
created_date: '2026-10-07 15:10'
updated_date: '2026-10-07 18:09'
labels:
  - results
dependencies: []
priority: low
type: bug
ordinal: 51000
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
results/index.blade.php:59 opens ?print=all, but no controller reads that parameter, so it reopens the same page.
<!-- SECTION:DESCRIPTION:END -->

## Comments

<!-- COMMENTS:BEGIN -->
author: @claude
created: 2026-10-07 18:09
---
[audit] Open whether to build batch printing or remove the button. — Oscar decides; removing it is the cheap option.
---
<!-- COMMENTS:END -->
