SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'FMHH24') AS "format", '24-hour day hour (Without leading zero) [0-23] [G]'
