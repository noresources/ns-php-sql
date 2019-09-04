SELECT 
  [t].[name] AS [N], 
  [ns_unittests].[Tasks].[category], 
  [ns_unittests].[Employees].[name] AS [AuthorName], 
  [e2].[name] AS [AssignedToName] 
FROM 
  [ns_unittests].[Tasks] AS [t] 
  INNER JOIN [ns_unittests].[Employees] ON [ns_unittests].[Tasks].[creator] = [ns_unittests].[Employees].[id] 
  INNER JOIN [ns_unittests].[Employees] AS [e2] ON [ns_unittests].[Tasks].[assignedTo] = [e2].[id] 
WHERE 
  [ns_unittests].[Tasks].[category] = $userDefinedCategory 
GROUP BY 
  [N], 
  [ns_unittests].[Tasks].[id] 
ORDER BY 
  substr([N], 3) ASC 
LIMIT 
  5 OFFSET 3