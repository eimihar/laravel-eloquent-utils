<?php
namespace EloquentUtils\Extension;
use Illuminate\Database\Capsule\Manager as Capsule; 

class Eloquent
{
	private $instance;

	public function __construct($host = "localhost", $user = "root", $pass = "", $database = "persona")
	{
		$this->instance = $this->setup($host, $user, $pass, $database);
	}

	public function getCapsule()
	{
		return $this->instance;
	}

	public function setup($host, $user, $pass, $database)
	{
		$capsule = new Capsule; 

		$capsule->addConnection(array(
		    'driver'    => 'mysql',
		    'host'      => $host,
		    'database'  => $database,
		    'username'  => $user,
		    'password'  => $pass,
		    'charset'   => 'utf8',
		    'collation' => 'utf8_unicode_ci',
		    'prefix'    => ''
		),"default");

		$capsule->bootEloquent();

		return $capsule;
	}
}



?>