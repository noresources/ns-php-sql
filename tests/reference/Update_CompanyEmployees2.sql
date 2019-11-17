UPDATE 
  [ns_unittests].[Employees] 
SET 
  [salary] = [ns_unittests].[Employees].[salary] + 100 
WHERE 
  [ns_unittests].[Employees].[gender] = 'F' 
  AND [ns_unittests].[Employees].[salary] < 1000