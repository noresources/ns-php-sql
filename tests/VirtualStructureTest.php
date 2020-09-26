<?php
namespace NoreSources\SQL;

use NoreSources\SQL\Constants as K;
use NoreSources\SQL\DBMS\Reference\ReferenceConnection;
use NoreSources\SQL\Expression\TokenStream;
use NoreSources\SQL\Statement\StatementTokenStreamContext;
use NoreSources\SQL\Structure\DatasourceStructure;
use NoreSources\SQL\Structure\NamespaceStructure;
use NoreSources\SQL\Structure\StructureResolverException;
use NoreSources\SQL\Structure\StructureResolverInterface;
use NoreSources\SQL\Structure\TableStructure;
use NoreSources\SQL\Structure\VirtualStructureResolver;
use NoreSources\Test\DerivedFileManager;

final class VirtualStructureTest extends \PHPUnit\Framework\TestCase
{

	public function __construct($name = null, array $data = [],
		$dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->derivedFileManager = new DerivedFileManager(__DIR__);
	}

	public function testNamespace()
	{
		$vsr = new VirtualStructureResolver();
		$this->assertInstanceOf(StructureResolverInterface::class, $vsr);

		$ds = $vsr->getPivot();
		$this->assertInstanceOf(DatasourceStructure::class, $ds);

		$ns = $vsr->findNamespace('namespace');
		$ref = $vsr->findNamespace('namespace');
		$this->assertEquals($ns, $ref);

		$this->assertCount(1, $ds);

		$re2 = $vsr->findNamespace('namespace2');
		$this->assertCount(2, $ds);
	}

	public function testTable()
	{
		$vsr = new VirtualStructureResolver();
		$ns = $vsr->findNamespace('ns');

		$table = $vsr->findTable('ns.t');
		$this->assertInstanceOf(TableStructure::class, $table,
			'Table class');
		$this->assertEquals('ns.t', $table->getPath(), 'Table path');
		$this->assertEquals($table->getParentElement(), $ns,
			'Table parent');

		$ref = $vsr->findTable('ns.t');
		$this->assertEquals($table, $ref, 'Table ref');

		$shortRef = $vsr->findTable('t');
		$this->assertEquals($table, $shortRef,
			'Table ref with short name');
	}

	public function testTable2()
	{
		$vsr = new VirtualStructureResolver();
		$t = $vsr->findTable('T');
		$this->assertInstanceOf(TableStructure::class, $t);
		$ns = $vsr->findNamespace('ns');
		$this->assertInstanceOf(NamespaceStructure::class, $ns);
		$u = $vsr->findTable('U');
		$this->assertInstanceOf(TableStructure::class, $u);

		$this->assertInstanceOf(DatasourceStructure::class,
			$u->getParentElement(), 'U is owned by datasource');

		$this->assertEquals($t->getParentElement(),
			$u->getParentElement(), 'T and U have the same parent');

		$nst = $vsr->findTable('ns.T');
		$this->assertInstanceOf(TableStructure::class, $nst);
		$this->assertEquals($ns, $nst->getParentElement(),
			'nst is owned by ns');
		$this->assertNotEquals($t, $nst, 'T and ns.T are not the same');

		$vsr->setAlias('You', $u);
		$you = $vsr->findTable('You');
		$this->assertEquals($u, $you, 'U = You (alias)');

		$vsr->setAlias('nst', $nst);
		$nstAlias = $vsr->findTable('nst');
		$this->assertEquals($nst, $nstAlias, 'ns.t = nst (alias)');

		$notNsAlias = $vsr->findNamespace('nst');
		$this->assertInstanceOf(NamespaceStructure::class, $notNsAlias);
	}

	public function testColumn()
	{
		$vsr = new VirtualStructureResolver();
		$ns = $vsr->findNamespace('ns');
		$table = $vsr->findTable('t');
		$this->assertEquals('ns.t', $table->getPath(), 'Table path');

		$c = $vsr->findColumn('c');
		$this->assertEquals('ns.t.c', $c->getPath());
		$c2 = $vsr->findColumn('t.c2');
		$this->assertEquals('ns.t.c2', $c2->getPath());
	}

	public function testColumnResolutionException()
	{
		$vsr = new VirtualStructureResolver();

		$this->expectException(StructureResolverException::class,
			"Can't resolve column before resolving at least one table");

		$c = $vsr->findColumn('lonely');

		$t2 = $vsr->findTable('t2');

		$t2c = $vsr->findColumn('t2.c');
		$this->assertEquals('ns.t2.c', $t2c->getPath(),
			'Column path of the second table');

		$vsr->setPivot($t2);
		$t2c2 = $vsr->findColumn('c2');
		$this->assertEquals('ns.t2.c2', $t2c2->getPath(),
			'Second column path of the second table');
	}

	public function testStatementBuilding()
	{
		$vsr = new VirtualStructureResolver();
		// $vsr->setDefaultNamespace('main');
		$connection = new ReferenceConnection();
		$platform = $connection->getPlatform();
		$builder = $connection->getStatementBuilder();

		/**
		 *
		 * @var \NoreSources\SQL\Statement\Query\SelectQuery $select
		 */
		$select = $builder->newStatement(K::QUERY_SELECT);

		$epoch = new \DateTime('@0');
		$shift = clone $epoch;
		$shift->modify('+1 year 2 months 15 days');

		$select->from('table')
			->columns('id', [
			'timestamp' => 'at'
		])
			->where([
			'id' => 10
		])
			->where([
			'between' => [
				'timestamp',
				$epoch,
				$shift
			]
		])
			->orderBy('at');

		foreach ([
			'inline ctor' => new StatementTokenStreamContext($builder,
				$vsr),
			'array ctor' => new StatementTokenStreamContext(
				[
					'builder' => $builder,
					'resolver' => $vsr
				])
		] as $context)
		{
			$stream = new TokenStream();
			$select->tokenize($stream, $context);
			$data = $builder->finalizeStatement($stream, $context);
			$sql = \SqlFormatter::format(\strval($data), false);

			$this->derivedFileManager->assertDerivedFile($sql,
				__METHOD__, 'select', 'sql');
		}
	}

	/**
	 * /**
	 *
	 * @var DerivedFileManager
	 */
	private $derivedFileManager;
}