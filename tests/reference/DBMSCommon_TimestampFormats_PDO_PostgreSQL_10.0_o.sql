SELECT TO_CHAR(CAST(? as timestamp without time zone), 'IYYY') AS "format", 'ISO-8601 week-numbering year (Same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead) [o]'
