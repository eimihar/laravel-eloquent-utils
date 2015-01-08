<?php
/*
Requirement : "illuminate/database": "*"
*/

class EloquentSchemaBuilder
{
	public $strict	= true;
	public $builder;

	public function __construct($connection,$aliases = null)
	{
		if($connection instanceof \Illuminate\Database\Connection)
		{
			$this->connection	= $connection;

			$this->aliases	= Array(
				"increment"	=>"increments",
				"varchar"	=>"string",
				"int"		=>"integer",
				"text"		=>"text"
				);
		}
		else if(is_array($connection))
		{
			$this->createConnection($connection,"default");
		}
		else
		{
			throw new Exception("Received not the connection or database config in array.");
		}

		if($aliases)
			foreach($aliases as $c=>$t)
				$this->aliases[$c]	= $t;

		## build connection.
		$this->connection->setSchemaGrammar(new \Illuminate\Database\Schema\Grammars\MySqlGrammar);
		$this->builder	= new \Illuminate\Database\Schema\Builder($this->connection);
	}

	## create default connection by database capsule manager.
	public function createConnection($config)
	{
		$capsule = new \Illuminate\Database\Capsule\Manager; 

		$capsule->addConnection(array(
		    'driver'    => 'mysql',
		    'host'      => $config['host'],
		    'database'  => $config['database'],
		    'username'  => $config['user'],
		    'password'  => $config['pass'],
		    'charset'   => 'utf8',
		    'collation' => 'utf8_unicode_ci',
		    'prefix'    => ''
					),'default');

		$capsule->bootEloquent();

		return $capsule->getConnection('default');
	}

	public function getBuilder()
	{
		return $this->builder;
	}

	public function execute($schema)
	{
		$aliases	= &$this->aliases;
		$builder	= $this->getBuilder();

		if(is_array($schema))
		{
			## Validation. increment required.
			foreach($schema as $table=>$columns)
			{
				$cols	= array_keys($columns);
				$type	= $columns[$cols[0]]; 
				
				## resolve type.
				$type	= isset($aliases[$type])?$aliases[$type]:$type;
				
				if($type != "increments" && $this->strict)
					throw new Exception("No primary was found for table ($table).");
			}

			foreach($schema as $tname=>$columns)
			{
				## engine. example tablename:engine
				$engine	= null;
				if(strpos($tname, ":") !== false)
					list($tname,$engine)	= explode(":",$tname);

				$action	= $builder->hasTable($tname)?"table":"create";

				$builder->$action($tname,function($table) use($tname, $columns, $aliases, $engine, $builder)
				{
					if($engine)
						$table->engine = $engine;

					foreach($columns as $column=>$type)
					{
						## resolve type.
						$type	= isset($aliases[$type])?$aliases[$type]:$type;

						if($builder->hasColumn($tname,$column))
							continue;

						## create;
						if(!is_numeric($column))
						{
							$table->$type($column);
						}
						else
						{
							if(!$builder->hasColumn($tname,"created_at") && !$builder->hasColumn($tname,"updated_at"))
							{
								if($type == "timestamps")
									$table->timestamps();
							}
						}
					}
				});

				## existence deletion
				if($builder->hasTable($tname))
				{
					$tableColumns	= $builder->getColumnListing($tname);

					## compare with schema.
					$nonExists	= Array();
					foreach($tableColumns as $column)
					{
						if(!isset($columns[$column]) && !in_array($column, Array("created_at","updated_at")))
							$nonExists[]	= $column;
					}

					if(count($nonExists) > 0)
					{
						echo "Unable to find below column(s) in your schema for table : $tname :\n";
						foreach($nonExists as $col)
						{
							echo "- ".$col."\n";
						}
						echo "Do you want to drop them.?\n";

						$handle = fopen ("php://stdin","r");
						$line = fgets($handle);

						if(trim($line) == "y")
						{
							$builder->table($tname,function($table) use($nonExists)
							{
								$table->dropColumn($nonExists);
							});
							echo "deleted!\n";
						}
					}
				}
			}
		}
		## use direct eloquent builder instead, given the builder.
		else if($schema instanceof Closure)
		{
			$schema($schema,$builder);
		}
	}
}

?>