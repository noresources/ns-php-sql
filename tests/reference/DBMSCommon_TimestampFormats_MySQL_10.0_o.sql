SELECT DATE_FORMAT(CAST(? as datetime), '%Y') AS `format`, 'ISO-8601 week-numbering year (Same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead) [o]'