<?php
    /*
    usage: {
        FuncsChain() => new FuncsChain
        FuncsChain([key1 => function1, key2 => function2]) -> new FuncsChain with key-pair functions
        FuncsChain(Result, [function1, function2]) -> new FuncsChain include Result of functions
        FuncsChain([function1, function2]) -> new FuncsChain include new Result of functions
        FuncsChain(Result, [key1 => function1, key2 => function2], [key1, key2]) -> new FuncsChain include Result of key-pair functions
        FuncsChain([key1 => function1, key2 => function2], [key1, key2]) -> new FuncsChain include new Result of key-pair functions
    }
    */
    class FuncsChain {
        protected $resultClass = 'Result';
        protected $result;
        protected $funcs;
        protected $keys;
        
        // init for constructor
        public function init() {
            $args = func_get_args();
            $count = func_num_args();
            if($count > 0) {
                if(is_object($args[0]) && ($args[0] instanceof $this->resultClass)) {
                    $this->setResult(array_shift($args));
                    $count -= 1;
                }
                // 後面應該就是函數陣列了
                if($count > 0 && is_array($args[0])) {
                    $this->addFuncs(array_shift($args));
                    $count -= 1;
                }
                // 再後面才是keys
                if($count > 0 && is_array($args[0])) {
                    $this->setKeys(array_shift($args));
                    $count -= 1;
                }
            }
        }
        
        public function __construct() {
            $this->result = null;
            $this->funcs = [];
            $this->keys = [];
            
            $argv = func_get_args();
            
            call_user_func_array([$this, 'init'], $argv);
        }
        
        // setResultClass(Result object|String 'Result') -> null
        public function setResultClass($resultClass) {
            if(is_object($resultClass)) {
                $this->resultClass = get_class($resultClass);
            }
            elseif(is_string($resultClass)) {
                $this->resultClass = $resultClass;
            }
        }
        
        // setResult(Result $result) -> null
        public function setResult($result) {
            if($result instanceof $this->resultClass) {
                $this->result = $result;
            }
        }
        
        // getResult() -> Result
        public function getResult() {
            if(is_object($this->result) && ($this->result instanceof $this->resultClass)) {
                return $this->result;
            }
            else {
                $this->result = new $this->resultClass();
                return $this->result;
            }
        }
        
        // 設定指定的key所指的func
        // setFunc(string | int $key, Closure $func) -> null
        public function setFunc($key, $func) {
            if((is_integer($key) || is_string($key)) && is_callable($func)) {
                $this->funcs[$key] = $func;
            }
        }
        
        // 整個取代掉已經定義好的funcs
        // setFuncs(array [Closure] $funcs) -> null
        public function setFuncs($funcs = []) {
            $this->funcs = $funcs;
        }
        
        // 增加函數(假如丟的是陣列會丟給addFuncs去處理)
        // addFunc(array [Closure] $func | Closure $func ) -> null
        public function addFunc($func) {
            // 假如是陣列就丟到addFuncs處理
            if(is_array($func)) {
                $this->addFuncs($func);
            }
            // 判斷是否為closure
            elseif(is_callable($func)) {
                array_push($this->funcs, $func);
            }
        }
        
        // 增加函數陣列
        // addFuncs(array [Closure] $funcs) -> null
        public function addFuncs($funcs = []) {
            if(is_array($funcs)) {
                foreach($funcs as $key => $func) {
                    if(is_integer($key)) {
                        $this->addFunc($func);
                    }
                    elseif(is_integer($key) || is_string($key)) {
                        $this->setFunc($key, $func);
                    }
                }
            }
        }
        
        // 取得函數
        // getFunc(string | int $key) -> Closure
        public function getFunc($key) {
            if(array_key_exists($key, $this->funcs)) {
                return $this->funcs[$key];
            }
        }
        
        // 取得已經定義好的函數陣列
        // getFuncs() -> array [Closure] $funcs
        public function getFuncs() {
            return $this->funcs;
        }
        
        // 設定執行順序的Keys(只接受陣列)
        // setKeys(array [string] $keys) -> null
        public function setKeys($keys = []) {
            if(is_array($keys)) {
                $this->keys = $keys;
            }
        }
        
        // 增加執行順序的key(可接受陣列), 空值等同清空
        // addKey(array [string] $key | string $key) -> null
        public function addKey($key) {
            if(is_array($key)) {
                $this->addKeys($key);
            }
            elseif(is_integer($key) || is_string($key)) {
                array_push($this->keys, $key);
            }
        }
        
        // 增加執行順序的key陣列
        // addKeys(array [string] $keys) -> null
        public function addKeys($keys = []) {
            if(is_array($keys)) {
                foreach($keys as $key) {
                    if(is_integer($key) || is_string($key)) {
                        $this->addKey($key);
                    }
                }
            }
        }
        
        // 取得keys的陣列
        // getKeys() -> array [string] $keys
        public function getKeys() {
            return $this->keys;
        }
        
        // 清除已經設好的keys
        // cleanKeys() => null
        public function cleanKeys() {
            $this->setKeys();
        }
        
        /*
        執行
        usage: {
            run() -> new Result of after running funcs
            run($result) -> Result of $result->addFrom($funcResult)
            run([key1, key2]) -> new Result of after running funcs from keys
            run($result, [key1, key2]) -> Result of $result->addFrom($funcResult) that after running funcs from keys
        }
        */
        public function run() {
            $args = func_get_args();
            $count = func_num_args();
            $result = $this->getResult();
            $funcs = [];
            $keys = $this->keys;
            // 參數處理
            if($count > 0) {
                if(is_object($args[0]) && ($args[0] instanceof $this->resultClass)) {
                    $result = array_shift($args);
                    $count -= 1;
                }
                if($count > 0 && is_array($args[0])) {
                    $keys = $args[0];
                    $count -= 1;
                }
            }
            
            // 將keys裡面的指定func丟到等等要run的$funcs裡面
            if(is_array($keys) && count($keys) > 0) {
                foreach($keys as $key) {
                    if($func = $this->getFunc($key)) {
                        $funcs[] = $func;
                    }
                }
            }
            // 處理0開始的那種非key-pair的funcs
            else {
                $funcsCount = count($this->funcs);
                for($i = 0; $i < $funcsCount; $i += 1) {
                    if(array_key_exists($i, $this->funcs)) {
                        $funcs[] = $this->funcs[$i];
                    }
                    else {
                        break;
                    }
                }
            }
            // 參數處理結束
            
            for($i = 0; $i < count($funcs);) {
                $func = array_shift($funcs);
                
                $funcResult = call_user_func_array($func, [&$result]);
                
                if(is_object($funcResult) && ($funcResult instanceof $this->resultClass)) {
                    $result->addFrom($funcResult);
                }
                
                if(!$result->isDone() || $funcResult === false) {
                    break;
                }
                elseif(is_string($funcResult) || is_integer($funcResult)) {
                    if(is_callable($nextFunc = $this->getFunc($funcResult))) {
                        array_unshift($funcs, $nextFunc);
                    }
                }
                elseif(is_array($funcResult)) {
                    $nextFuncs = [];
                    foreach($funcResult as $nextKey) {
                        if(is_string($nextKey) || is_integer($nextKey)) {
                            if(is_callable($nextFunc = $this->getFunc($nextKey))) {
                                array_push($nextFuncs, $nextFunc);
                            }
                        }
                    }
                    $funcs = $nextFuncs;
                }
                
            }
            return $result;
        }
    }