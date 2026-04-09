# Scholarly Data Exporters

This guide details the mapping of `Nexus\Models\Document` to common scholarly formats.

## RIS Export (Research Information Systems)
RIS is a tagged format widely used in reference managers (EndNote, Zotero).

### Standard Tag Mapping
- **TY**: Type (e.g., JOUR for journal, CONF for conference).
- **AU**: Author (Repeat for each author).
- **T1**: Title.
- **PY**: Year.
- **JF**: Journal/Venue.
- **VL**: Volume.
- **IS**: Issue.
- **SP**: Start Page.
- **EP**: End Page.
- **UR**: DOI or URL.
- **ER**: End of Record (Required).

### Example Output
```ris
TY  - JOUR
AU  - Smith, John
T1  - AI in Scholarly Research
PY  - 2024
JF  - Nature
UR  - 10.1038/nature12345
ER  - 
```

## BibTeX Export
BibTeX is the standard for LaTeX-based citations.

### Entry Types
- **@article**: For journal papers.
- **@inproceedings**: For conference papers.
- **@book**: For books.

### Field Mapping
- **author**: Format as `First Last and First Last`.
- **title**: Enclose in `{}` to preserve casing.
- **journal**: Venue name.
- **year**: Publication year.
- **doi**: DOI.

### Example Output
```bibtex
@article{Smith2024,
  author = {John Smith},
  title = {{AI in Scholarly Research}},
  journal = {Nature},
  year = {2024},
  doi = {10.1038/nature12345}
}
```

## CSV Export
For simple data analysis, `Nexus\Export\CsvExporter` provides a flat representation.
- Headers: `id, doi, title, authors, year, venue, abstract, citations_count`.
- List of authors is semicolon-separated.
