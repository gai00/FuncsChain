<?php
    // auto loading
    spl_autoload_register(function($class) {
        include('../libs/' . $class . '.php');
    });
    
    // example 1
    $funcsChain = new FuncsChain([
        function($result) {
            $result->tempData = [
                'temp' => 1
            ];
        },
        function($result) {
            $result->tempData['temp'] += 1;
        },
        function($result) {
            $result->setData($result->tempData);
            unset($result->tempData);
        }
    ]);
    // get result
    $result = $funcsChain->run();
    var_dump($result->getData());
    
    // example 2
    $result = (new FuncsChain([
        function($result) {
            $result->setData([
                'msg' => "this is example 2."
            ]);
        }
    ]))->run();
    var_dump($result->getData());
    
    // example 3
    $result = new Result();
    $result->setData(['count' => 10]);
    $funcsChainLoop = new FuncsChain($result);
    $funcsChainLoop->addFuncs([
        // 0
        function($result) {
            echo("example 3\n");
            
            $count = $result->getData('count');
            echo("count: $count\n");
            
            // insert after this chain node.
            return 'loop';
        },
        
        // 1
        function($result) {
            echo("loop end.\n");
        },
        
        // loop
        'loop' => function($result) {
            $count = $result->getData('count');
            echo("current count: $count\n");
            if($count > 0) {
                // next sub
                return 'sub';
            }
            else {
                // next to 1
                return;
            }
        },
        
        // sub
        'sub' => function($result) {
            $result->data['count'] -= 1;
            
            // next loop
            return 'loop';
        }
    ]);
    $funcsChainLoop->run();
    echo("\n");
    
    // example 4
    echo("example 4\n");
    $funcsChain = new FuncsChain([
        function($result) {
            echo("without keys.\n");
        },
        function($result) {
            echo("without keys 2.\n");
        },
        
        'init' => function($result) {
            $result->setData('queue', []);
        },
        'first' => function($result) {
            echo("first\n");
            $result->data['queue'][] = 'first';
        },
        'second' => function($result) {
            echo("second\n");
            $result->data['queue'][] = 'second';
        },
        'third' => function($result) {
            echo("third\n");
            $result->data['queue'][] = 'third';
        },
        'log' => function($result) {
            $queue = $result->getData('queue');
            foreach($queue as $index => $val) {
                echo("[$index]: $val\n");
            }
        }
    ]);
    
    // no keys
    echo("[no keys]\n");
    $funcsChain->run();
    
    // with keys
    echo("[with keys]\n");
    $result = $funcsChain->run(['init', 'first', 'third', 'log']);
    // test will pass
    $result = $funcsChain->run(['init', 'test', 'second', 'log']);