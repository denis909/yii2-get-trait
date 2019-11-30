<?php

namespace denis909\yii;

use Yii;
use Exception;
use Throwable;

trait GetTrait
{

	public static function get(array $where, $create = false, $attributes = [])
	{
		$model = static::find()
			->where($where)
			->one();

        if ($model)
        {
            return $model;
        }    
	
		if (!$create)
		{
			return null;
		}

        $transaction = Yii::$app->db->beginTransaction();

        try
        {
            $attrs = $attributes;

            foreach($where as $key => $value)
            {
                $attrs[$key] = $value;
            }

            $className = get_called_class();

            $model = Yii::createObject(['class' => $className]);

            foreach($attrs as $key => $value)
            {
                $model->$key = $value;
            }

            try
            {
                if (!$model->save(false))
                {
                    $errors = $model->getFirstErrors();

                    $error = array_shift($errors);

                    throw new Exception($error); 
                }
            }
            catch(Exception $e)
            {
                if ($create)
                {
                    $return = static::get($where, false, $attributes);

                    if ($return)
                    {
                        $transaction->rollBack();

                        return $return; // created in other thread
                    }
                }

                throw $e;
            }

            $transaction->commit();
        }
        catch(Exception $e)
        {
            $transaction->rollBack();
        
            throw $e;
        }
        catch (Throwable $e)
        {
            $transaction->rollBack();
        
            throw $e;
        }

		$return = static::get($where, false, $attributes);

        if (!$return)
        {
            throw new Exception('Model not found.');
        }

        return $return;
	}
	
}