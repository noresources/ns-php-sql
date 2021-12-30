CREATE TABLE [metavariables].[bar] (
  [valueId] INTEGER, 
  PRIMARY KEY ([key]), 
  CONSTRAINT [fk] FOREIGN KEY ([valueId]) REFERENCES [foo] ([id])
)
