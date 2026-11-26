---
layout: home
title: BJCP competitions for small homebrew clubs

hero:
  name: BeerJudge
  text: Competition management for a small homebrew club.
  tagline: Members register beers, judges score them on the BJCP 50-point scale, and each competition produces a ranked result list.
  actions:
    - theme: brand
      text: How to judge
      link: /judging/howto-judge
    - theme: alt
      text: Run a competition
      link: /competition/lifecycle-howto

features:
  - title: Judging screen
    details: Offline-first scoring with Alpine.js and IndexedDB. Sheets sync to the server when online; retries are idempotent via client_uuid.
  - title: Competition lifecycle
    details: Four states — draft, open, judging, closed. Registration locks when judging starts; bottle numbers are assigned automatically.
  - title: Results
    details: Final score is the average of submitted totals. Tiebreak uses the overall-impression section. Rankings are competition-wide, not per style.
---
