CREATE TEMPORARY VIEW [Males] AS 
SELECT 
  [ns_unittests].[Employees].[id], 
  [ns_unittests].[Employees].[name] 
FROM 
  [ns_unittests].[Employees] 
WHERE 
  [ns_unittests].[Employees].[gender] = 'M'