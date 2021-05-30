INSERT INTO [ns_unittests].[Employees] ([name], [salary]) 
SELECT 
  [ns_unittests].[Employees].[name] AS [N], 
  [ns_unittests].[Employees].[salary] AS [S] 
FROM 
  [ns_unittests].[Employees] 
WHERE 
  [ns_unittests].[Employees].[gender] = 'F'