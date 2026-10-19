#!/usr/bin/env python3
"""Extract Google Forms judging responses into JSON + name/beer mapping CSVs.

One-off preprocessing script, not part of the Laravel app. Run once per
source xlsx file, then hand-fill the generated CSVs before running the
`backfill:import` Artisan command. Needs `pip install openpyxl` (not a
project dependency). Reads and writes in backfill/data/, which is gitignored:
the exports hold member names and judges' notes, so they stay out of git.
"""

import csv
import json
import re
import sys
from pathlib import Path

import openpyxl

SOURCES = [
    {
        "xlsx": "FOEBAR Rarely found beers competition 2025 (Responses).xlsx",
        "slug": "rarely-found-beers-2025",
        "competition_name": "Rarely Found Beers 2025",
        "columns": {
            "timestamp": 0,
            "judge_name": 1,
            "beer_text": 2,
            "aroma": 4,
            "aroma_notes": 5,
            "appearance": 6,
            "appearance_notes": 7,
            "flavour": 8,
            "flavour_notes": 9,
            "mouthfeel": 10,
            "mouthfeel_notes": 11,
            "overall": 12,
            "overall_notes": 13,
            "sheet_total": 14,
        },
    },
    {
        "xlsx": "FOEBAR Lager competition 2025 (Responses).xlsx",
        "slug": "lager-2025",
        "competition_name": "Lager Competition 2025",
        "columns": {
            "timestamp": 0,
            "judge_name": 1,
            "beer_text": 2,
            "aroma": 3,
            "aroma_notes": 4,
            "appearance": 5,
            "appearance_notes": 6,
            "flavour": 7,
            "flavour_notes": 8,
            "mouthfeel": 9,
            "mouthfeel_notes": 10,
            "overall": 11,
            "overall_notes": 12,
            "sheet_total": 14,
        },
    },
]

BACKFILL_DIR = Path(__file__).parent / "data"

# "1. Pete's Cream Ale" -> ("Pete", "Cream Ale")
# "Jorge & Rodrigo Marzen" -> ("Jorge & Rodrigo", "Marzen")
BEER_TEXT_RE = re.compile(
    r"^(?:\d+\.\s*)?(?P<brewer>.+?)(?:'s)\s+(?P<style>.+)$"
)


def parse_beer_text(text):
    text = text.strip()
    match = BEER_TEXT_RE.match(text)
    if match:
        return match.group("brewer").strip(), match.group("style").strip()
    # Fallback for text with no possessive, e.g. "Jorge & Rodrigo Marzen"
    parts = text.rsplit(" ", 1)
    if len(parts) == 2:
        return parts[0].strip(), parts[1].strip()
    return text, ""


def extract(source):
    xlsx_path = BACKFILL_DIR / source["xlsx"]
    wb = openpyxl.load_workbook(xlsx_path, data_only=True)
    ws = wb["Form responses 1"]
    rows = list(ws.iter_rows(values_only=True))
    cols = source["columns"]

    records = []
    for row in rows[1:]:
        if not row[cols["judge_name"]] or not row[cols["beer_text"]]:
            continue
        beer_text = str(row[cols["beer_text"]]).strip()
        brewer_name, style_text = parse_beer_text(beer_text)
        timestamp = row[cols["timestamp"]]
        records.append(
            {
                "judge_name": str(row[cols["judge_name"]]).strip(),
                "beer_text": beer_text,
                "brewer_name": brewer_name,
                "style_text": style_text,
                "aroma": row[cols["aroma"]],
                "aroma_notes": row[cols["aroma_notes"]],
                "appearance": row[cols["appearance"]],
                "appearance_notes": row[cols["appearance_notes"]],
                "flavour": row[cols["flavour"]],
                "flavour_notes": row[cols["flavour_notes"]],
                "mouthfeel": row[cols["mouthfeel"]],
                "mouthfeel_notes": row[cols["mouthfeel_notes"]],
                "overall": row[cols["overall"]],
                "overall_notes": row[cols["overall_notes"]],
                "sheet_total": row[cols["sheet_total"]],
                "timestamp": timestamp.isoformat() if timestamp else None,
            }
        )

    out = {
        "competition_name": source["competition_name"],
        "slug": source["slug"],
        "records": records,
    }
    out_path = BACKFILL_DIR / f"{source['slug']}.json"
    out_path.write_text(json.dumps(out, indent=2))
    print(f"wrote {out_path} ({len(records)} rows)")
    return out


def build_names_csv(competitions):
    names = {}
    for comp in competitions:
        for record in comp["records"]:
            entry = names.setdefault(
                record["judge_name"],
                {"seen_as_judge": 0, "seen_as_brewer": 0, "occurrences": 0},
            )
            entry["seen_as_judge"] += 1
            entry["occurrences"] += 1
            brewer_entry = names.setdefault(
                record["brewer_name"],
                {"seen_as_judge": 0, "seen_as_brewer": 0, "occurrences": 0},
            )
            brewer_entry["seen_as_brewer"] += 1
            brewer_entry["occurrences"] += 1

    out_path = BACKFILL_DIR / "names_to_map.csv"
    with out_path.open("w", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["name", "seen_as_judge", "seen_as_brewer", "occurrences", "member_id"])
        for name in sorted(names):
            stats = names[name]
            writer.writerow([name, stats["seen_as_judge"], stats["seen_as_brewer"], stats["occurrences"], ""])
    print(f"wrote {out_path} ({len(names)} distinct names)")


def build_beers_csv(competitions):
    out_path = BACKFILL_DIR / "beers_to_map.csv"
    with out_path.open("w", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["competition", "raw_beer_text", "brewer_name", "style_text_guess", "bjcp_code", "brewer_member_id"])
        for comp in competitions:
            seen = set()
            for record in comp["records"]:
                key = record["beer_text"]
                if key in seen:
                    continue
                seen.add(key)
                writer.writerow(
                    [
                        comp["competition_name"],
                        record["beer_text"],
                        record["brewer_name"],
                        record["style_text"],
                        "",
                        "",
                    ]
                )
    print(f"wrote {out_path}")


def main():
    competitions = [extract(source) for source in SOURCES]
    build_names_csv(competitions)
    build_beers_csv(competitions)


if __name__ == "__main__":
    sys.exit(main())
