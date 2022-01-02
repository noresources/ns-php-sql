SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'FMHH') AS "format", '12-hour day hour (Without leading zero) [1-12] [g]'
