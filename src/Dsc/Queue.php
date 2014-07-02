<?php
namespace Dsc;

class Queue extends \Dsc\Singleton
{

    public static function task( $task, $parameters, $when=null, $priority=0, $batch=null )
    {
        // if the cron job is enabled, queue $task and forget about it
        // otherwise, run it right away
        
        \Dsc\Mongo\Collections\QueueTasks::add( $task, $parameters, $when, $priority, $batch );
    }

    /**
     * Process the queue, if there are any tasks to process.
     * Run this from the command line via Cli/process_queue.php
     */
    public static function process($batch=null)
    {
        $singleton = new static;
        
        $logCategory = $batch ? 'ProcessQueue' . ucwords($batch) : 'ProcessQueue';
        $sleepSeconds = 5; // how many seconds to wait for more queue items to be added
        $pageSize = 10;
        $maxExecutionSeconds = 60 * 5; // set this to the maximum number of seconds you want to allow a task to run before trying again 
        
        gc_enable();

        $cycles = 0;
        while (true)
        {
            $model = new \Dsc\Mongo\Collections\QueueTasks;

            while (true)
            {
                // TODO If $batch is not null, add it to these conditions
                $conditions = array(
                    '$or' => array(
                        array( 'locked_by' => null ),
                        array( 'locked_at' => array( '$lte' => ( time() - $maxExecutionSeconds ) ) )
                    ),
                    'when' => array( '$lte' => time() ),
                );
                
                $__ids = \Dsc\Mongo\Collections\QueueTasks::collection()->distinct('_id', $conditions);
                if (empty($__ids))
                {
                    $model->log('No IDs', 'INFO', $logCategory);
                    
                    $cycles++;
                    if ($cycles > 30)
                    {
                        $cycles = 0;
                        gc_collect_cycles();
                    }
                    sleep($sleepSeconds);
                }
                else
                {
                    $model->log('found IDs', 'INFO', $logCategory);
                    
                    $ids = array_slice($__ids, 0, $pageSize);
                    
                    $conditions = array(
                        '_id' => array( '$in' => $ids )
                    );                    
                    
                    $mongo_id = (string) new \MongoId;
                    
                    \Dsc\Mongo\Collections\QueueTasks::collection()->update(
                        $conditions,
                        array('$set' => array( 'locked_by' => $mongo_id, 'locked_at' => time() ) ),
                        array('multiple'=>true)
                    );
                    
                    $queue = \Dsc\Mongo\Collections\QueueTasks::collection()->find(array('locked_by' => $mongo_id))->sort(array( 'priority' => 1 ));
                    
                    foreach ($queue as $queue_task) 
                    {
                        // do the task, if possible
                        
                        /**
                         * ***********************
                         * It would be better to fork the task to another process to reduce memory footprint.
                         * 
                         * TODO Create a file Cli/process_queue_task.php that accepts an input _id
                         * and trigger that here, sending $queue_task['_id']
                         * ***********************
                         */
                        
                        try 
                        {
                            $singleton->app->call( $queue_task['task'], $queue_task['parameters'] );
                            $task = (new \Dsc\Mongo\Collections\QueueTasks($queue_task))->complete();
                        }
                        
                        catch (\Exception $e) 
                        {
                            $model->log($e->getMessage(), 'ERROR', $logCategory);
                        }
                    }
                }
            }
        }
    }
}