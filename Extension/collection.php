<?php
namespace EloquentUtils\Extension;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
	public function toList($key, $column = null)
	{
		$records = array();
		if($this->count())
		{
			foreach($this as $model)
			{
				if($column)
					$records[$model[$key]] = $model[$column];
				else
					$records[] = $model[$key];
			}
		}

		return $records;
	}

	public function delete()
	{
		if($this->count() == 0)
			return;

		foreach($this as $model)
			$model->delete();

		return $this;
	}
}