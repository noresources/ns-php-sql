SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'DD Mon YYYY HH24:MI:SSOF') AS "format", 'RFC 2822 date [r]'
