<?php
class postgresqlTyperegistryTraitProgramInfo extends \NoreSources\ProgramInfo
{
	public function __construct()
	{
		parent::__construct("postgresql-typeregistry-trait");
		
		$this->abstract = 'Populate PostgreSQL type registry';
		
		// prg:switch displayHelp
		$G_1_help = new \NoreSources\SwitchOptionInfo("displayHelp", array('help'), (0));
		
		$G_1_help->abstract = 'Display program usage';
		$this->appendOption($G_1_help);
	}
}
?>
