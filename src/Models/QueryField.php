<?php

namespace Nexus\Models;

enum QueryField: string
{
    case TITLE = 'title';
    case ABSTRACT = 'abstract';
    case FULL_TEXT = 'full_text';
    case AUTHOR = 'author';
    case YEAR = 'year';
    case VENUE = 'venue';
    case DOI = 'doi';
    case KEYWORD = 'keyword';
    case ANY = 'any';
}
