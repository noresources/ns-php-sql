UPDATE 
  [ns_unittests].[Employees] 
SET 
  [salary] = [ns_unittests].[Employees].[salary] * 2 
WHERE 
  [ns_unittests].[Employees].[id] IN (
    SELECT 
      [ns_unittests].[Employees].[id] 
    FROM 
      [ns_unittests].[Employees] AS [e] 
    WHERE 
      [ns_unittests].[Employees].[id] > 2
  ) 
  AND [ns_unittests].[Employees].[id] NOT IN (4, 5)
