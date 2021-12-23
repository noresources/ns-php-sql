CREATE TABLE [metavariables].[bar] (
  [valueId] INTEGER NULL, 
  PRIMARY KEY ([key]), 
  CONSTRAINT [fk] FOREIGN KEY ([valueId]) REFERENCES [foo] ([id])
)
