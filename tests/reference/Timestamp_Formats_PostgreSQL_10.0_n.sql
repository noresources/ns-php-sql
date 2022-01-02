SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'FMMM') AS "format", 'Month number of the year (Without leading zero) [1-12] [n]'
