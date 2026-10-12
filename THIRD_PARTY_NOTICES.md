# Third-party notices

BeerJudge's own code is MIT-licensed (see `LICENSE`). The style data it seeds comes from the sources below, under their own terms.

## BJCP 2021 beer styles

`bjcp_styleguide-2021.json` is taken from [beerjson/bjcp-json](https://github.com/beerjson/bjcp-json) (`styles/bjcp_styleguide-2021.json`), which publishes the BJCP 2021 Beer Style Guidelines in BeerJSON format.

Modified. The changes, compared with upstream:

- Text fixes in about 330 text fields. Most split words the upstream text ran together ("Lightspicy" -> "Light spicy"); the rest fix typos and stray fragments ("Heroldl" -> "Herold", "Heles" -> "Helles", a trailing "Flavor" heading).
- The Spanish and Portuguese field names of the local styles X1, X2 and X4 (`aspecto`, `sabor`, `impresion_general`, ...) are renamed to the English keys the other styles use, and a stray `accordingly` key on 24C is removed.
- The X2 original gravity is normalised from `1055`/`1065` to `1.055`/`1.065`.

Style codes, style count (110) and all other values are unchanged.

The guideline text itself is written by the Beer Judge Certification Program ([bjcp.org](https://www.bjcp.org)).

The BeerJSON packaging is distributed under this licence:

```
MIT License

Copyright (c) 2022 BeerJSON

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Brewers Association 2026 beer styles

`ba_styleguide-2026.json` is built by `php artisan styles:scrape-ba` from the [Brewers Association Beer Style Guidelines](https://www.brewersassociation.org/edu/brewers-association-beer-style-guidelines/). The text is reorganised into BeerJudge's style sections.

The Brewers Association allows free use with an attribution line ("Proper Use of Guidelines" on [brewersassociation.org](https://www.brewersassociation.org/educational-publications/beer-styles/)). BeerJudge shows this line wherever the BA text appears (`Style::SOURCE_ATTRIBUTIONS`):

> Based on Brewers Association 2026 Beer Style Guidelines with changes. Used with permission of Brewers Association.

That permission does not cover use for which a fee is charged.
