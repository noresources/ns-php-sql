SELECT 
  "Namespace"."classId" AS "c" 
FROM 
  "Namespace" AS "n" 
WHERE 
  ("Namespace"."name" = ?) 
  AND "Namespace"."classId" IN (
    SELECT 
      "Classes"."id" AS "classId" 
    FROM 
      "Classes" AS "c" 
    WHERE 
      "Classes"."criteria" = ?
  )