<?php
namespace EloquentUtils\Extension;

class Base extends \Illuminate\Database\Eloquent\Model
{
	protected $guarded = array();
	
	public static function all($selects = null, $format = null)
	{
		$records = parent::all($selects);
		if(is_array($format))
		{
			$newRecords = array();
			if($records->count())
			{
				foreach($records as $row)
					$newRecords[$row[$format[0]]] = isset($format[1]) ? $row[$format[1]] : $row;
			}

			return $newRecords;
		}

		return $records;
	}

	// use our collection.
	public function newCollection(array $models = array())
	{
		return new Collection($models);
	}
}



?>