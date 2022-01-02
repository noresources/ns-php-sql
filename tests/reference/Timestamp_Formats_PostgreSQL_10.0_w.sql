SELECT TO_CHAR(CAST($1::timestamp with time zone as timestamp with time zone), 'D') AS "format", 'Day number of the week (From Sunday to Saturday) [0-6] [w]'
