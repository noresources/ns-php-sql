SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'IW') AS "format", 'ISO 8601 Week number of the year (Starting on Monday) [0-53] [W]'
