SELECT 
  [id], 
  [e].[name], 
  [H].[manageeId] 
FROM 
  (
    SELECT 
      [ns_unittests].[Employees].[id], 
      [ns_unittests].[Employees].[name] 
    FROM 
      [ns_unittests].[Employees] 
    WHERE 
      [ns_unittests].[Employees].[gender] = $g
  ) AS [e] 
  INNER JOIN (
    SELECT 
      * 
    FROM 
      [ns_unittests].[Hierarchy]
  ) AS [H] ON [id] = [H].[managerId]
