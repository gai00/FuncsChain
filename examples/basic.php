<?php
    // auto loading
    spl_autoload_register(function($class) {
        include(__DIR__ . '/../libs/' . $class . '.php');
    });
    
    // example 1
    echo("example 1\n");
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
    echo($result->getData('temp') . "\n");
    
    // example 2
    echo("\nexample 2\n");
    $result = (new FuncsChain([
        function($result) {
            $result->setData([
                'msg' => "this is example 2."
            ]);
        }
    ]))->run();
    echo($result->getData(msg) . "\n");
    
    // example 3
    echo("\nexample 3\n");
    
    $result = new Result();
    $result->setData(['count' => 10]);
    $funcsChainLoop = new FuncsChain($result);
    $funcsChainLoop->addFuncs([
        // 0
        function($result) {
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
    
    // example 5
    echo("\nexample 5\n");
    $funcsChain = new FuncsChain([
        // 0
        function($result) {
            if($result->getData('number') % 2) {
                return 'even';
            }
            else {
                return 'odd';
            }
        },
        // 1
        function($result) {
            echo($result->getData('output'));
        },
        
        'odd' => function($result) {
            $result->setData('output', "it's odd.\n");
        },
        'even' => function($result) {
            $result->setData('output', "it's even.\n");
        }
    ]);
    $result1 = new Result(['data' => ['number' => 10]]);
    $result2 = new Result(['data' => ['number' => 11]]);
    $funcsChain->run($result1);
    $funcsChain->run($result2);