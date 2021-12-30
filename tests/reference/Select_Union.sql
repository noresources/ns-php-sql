SELECT 
  [ns_unittests].[Employees].[name] AS [n] 
FROM 
  [ns_unittests].[Employees] 
WHERE 
  [ns_unittests].[Employees].[gender] = 'M' 
UNION 
SELECT 
  [ns_unittests].[Employees].[name] AS [m] 
FROM 
  [ns_unittests].[Employees] 
WHERE 
  [ns_unittests].[Employees].[salary] > 1000 
ORDER BY 
  [n] ASC
