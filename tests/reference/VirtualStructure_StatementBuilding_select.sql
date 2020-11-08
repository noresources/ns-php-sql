SELECT 
  [table].[id], 
  [table].[timestamp] AS [at] 
FROM 
  [table] 
WHERE 
  ([table].[id] = 10) 
  AND [table].[timestamp] BETWEEN '1970-01-01T00:00:00+0000' 
  AND '1971-03-16T00:00:00+0000' 
ORDER BY 
  [at] ASC