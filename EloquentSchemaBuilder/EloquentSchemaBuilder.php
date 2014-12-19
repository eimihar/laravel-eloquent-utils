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
		    'host'      => $host,
		    'database'  => $database,
		    'username'  => $user,
		    'password'  => $pass,
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


				$this->getBuilder()->create($tname,function($table) use($tname, $columns, $aliases, $engine)
				{
					if($engine)
						$table->engine = $engine;

					foreach($columns as $column=>$type)
					{
						## resolve type.
						$type	= isset($aliases[$type])?$aliases[$type]:$type;

						## create;
						if(!is_numeric($column))
						{
							$table->$type($column);
						}
						else
						{
							if($type == "timestamps")
								$table->timestamps();
						}

					}
				});
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