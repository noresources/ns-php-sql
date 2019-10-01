SELECT 
  [ns_unittests].[Employees].[id] AS [I], 
  [ns_unittests].[Employees].[name] AS [N] 
FROM 
  [ns_unittests].[Employees] AS [E] 
WHERE 
  [ns_unittests].[Employees].[gender] = 'M' 
  AND [ns_unittests].[Employees].[id] IN(
    SELECT 
      [ns_unittests].[Hierarchy].[manageeId] AS [N] 
    FROM 
      [ns_unittests].[Hierarchy] AS [E] 
    WHERE 
      [ns_unittests].[Hierarchy].[managerId] < 10
  )