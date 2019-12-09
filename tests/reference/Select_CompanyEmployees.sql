SELECT 
  [ns_unittests].[Employees].[id] AS [identifier], 
  [ns_unittests].[Employees].[name] 
FROM 
  [ns_unittests].[Employees] AS [t] 
WHERE 
  [ns_unittests].[Employees].[id] IN (2, 4, 6, 8) 
  AND [ns_unittests].[Employees].[name] LIKE 'Jean%'