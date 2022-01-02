SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'IYYY-MM-DD"T"HH24:MI:SSOF') AS "format", 'ISO 8601 date [c]'
