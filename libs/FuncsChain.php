<?php
    /*
    change log:
        1.1.1
            getFuncsChain增加autoLoaded參數
            假如找不到會自動去load看看有沒有。
        1.1.0b
            修改getFuncsChain的bug
        1.1.0
            取得funcs chain的靜態函數
            有load跟get兩種, set則是給key跟funcsChain物件。
            
            setResultClass改為static
        1.0.3
            追加push|unshift Next Key|Func
            pushNextFunc($func) 跟addNextKey類似
            unshiftNextFunc($func)則是將func加到queue最前面。
            
            追加 spliceNextKeys|Funcs
            用來控制流程queue的函數
        1.0.2
            追加queue作為run期間的執行駐列
            
            追加hasFunc(key)來判斷是否有該函數
            
            追加addNextKey給funcsChain，作為執行期間(run)動態追加執行函數
            addNextKey(nextKey)
            addNextKey(nextKeys = [nextKey1, nextKey2]) 轉去給addNextKeys處理
            addNextKeys(nextKeys = [nextKey1, nextKey2])
            
        1.0.1
            追加回傳funcsChain作為額外執行的方法。
        
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
        const VERSION = '1.1.1';
        
        static protected $resultClass = 'Result';
        
        static protected $loadDir = __DIR__ . '/FuncsChain';
        // key => fc 的方式key-pair
        static protected $funcsChains = [];
        
        // 綁定的result, 也可以在run的時候臨時定義, 不影響此變數
        protected $result;
        // 此funcschain設定好的funcs
        protected $funcs;
        // 此funcschain預定執行的keys
        protected $keys;
        // 此funcschain執行期間用的funcsQueue
        protected $queue = [];
        // nextKeys, 在執行期間預計要執行的keys
        protected $nextKeys = [];
        
        // setResultClass(Result object|String 'Result') -> null
        public static function setResultClass($resultClass) {
            if(is_object($resultClass)) {
                self::$resultClass = get_class($resultClass);
            }
            elseif(is_string($resultClass)) {
                self::$resultClass = $resultClass;
            }
        }
        
        public static function setLoadDir($path) {
            if(substr($path, 0, 1) != '/') {
                $path = __DIR__ . '/' . $path;
            }
            self::$loadDir = $path;
        }
        
        public static function loadFuncsChain($key) {
            $path = self::$loadDir . '/' . $key . '.php';
            
            if(file_exists($path)) {
                $fc = include($path);
                self::setFuncsChain($key, $fc);
                return $fc;
            }
        }
        
        public static function getFuncsChain($key, $autoLoaded = false) {
            if(isset(self::$funcsChains[$key])) {
                return self::$funcsChains[$key];
            }
            elseif($autoLoaded) {
                return self::loadFuncsChain($key);
            }
        }
        
        public static function setFuncsChain($key, $fc) {
            self::$funcsChains[$key] = $fc;
        }
        
        // init for constructor
        public function init() {
            $args = func_get_args();
            $count = func_num_args();
            if($count > 0) {
                if(is_object($args[0]) && ($args[0] instanceof self::$resultClass)) {
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
        
        
        // setResult(Result $result) -> null
        public function setResult($result) {
            if($result instanceof self::$resultClass) {
                $this->result = $result;
            }
        }
        
        // getResult() -> Result
        public function getResult() {
            if(is_object($this->result) && ($this->result instanceof self::$resultClass)) {
                return $this->result;
            }
            else {
                $this->result = new self::$resultClass();
                return $this->result;
            }
        }
        
        // 設定指定的key所指的func
        // setFunc(string | int $key, Closure $func) -> null
        public function setFunc($key, $func) {
            if((is_integer($key) || is_string($key)) && (is_callable($func)  || $func instanceof self)) {
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
            elseif(is_callable($func) || $func instanceof self) {
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
        
        // 判斷是否有該函數
        public function hasFunc($key) {
            return array_key_exists($key, $this->funcs);
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
        
        public function addNextKey($nextKey) {
            if(is_array($nextKey)) {
                $this->addNextKeys($nextKeys);
            }
            elseif(is_integer($nextKey) || is_string($nextKey))  {
                if($this->hasFunc($nextKey)) {
                    $this->nextKeys[] = $nextKey;
                }
            }
        }
        
        public function addNextKeys($nextKeys = []) {
            if(is_array($nextKeys)) {
                foreach($nextKeys as $nextKey) {
                    $this->addNextKey($nextKey);
                }
            }
        }
        
        public function hasNextKey() {
            return !!count($this->nextKeys);
        }
        
        public function getNextKey() {
            return array_shift($this->nextKeys);
        }
        
        // 判斷是否有nextKeys，有的話查詢取得func後丟到傳參考參數
        // 或是直接追加$func直接追加
        public function pushNextKey($key = null) {
            if(is_array($key)) {
                $this->pushNextKeys($key);
            }
            elseif($func = $this->getFunc($key)) {
                array_push($this->queue, $func);
            }
        }
        public function pushNextKeys($keys = []) {
            $this->spliceNextKeys(-1, 0, $keys);
        }
        public function pushNextFunc($func = null) {
            if(is_callable($func)) {
                array_push($this->queue, $func);
            }
            elseif(is_array($func)) {
                $this->pushNextFuncs($func);
            }
            else {
                while($this->hasNextKey()) {
                    $key = $this->getNextKey();
                    $this->pushNextKey($key);
                }
            }
        }
        public function pushNextFuncs($funcs = []) {
            $this->spliceNextFuncs(-1, 0, $funcs);
        }
        
        // unshift版本
        public function unshiftNextKey($key = null) {
            if(is_array($key)) {
                $this->unshiftNextKeys($key);
            }
            elseif($func = $this->getFunc($key)) {
                array_unshift($this->queue, $func);
            }
        }
        public function unshiftNextKeys($keys = []) {
            $this->spliceNextKeys(0, 0, $keys);
        }
        public function unshiftNextFunc($func = null) {
            if(is_callable($func)) {
                array_unshift($this->queue, $func);
            }
            elseif(is_array($func)) {
                $this->unshiftNextFuncs($func);
            }
            else {
                while($this->hasNextKey()) {
                    $key = $this->getNextKey();
                    $this->unshiftNextKey($key);
                }
            }
        }
        public function unshiftNextFuncs($funcs = []) {
            $this->spliceNextFuncs($funcs);
        }
        
        // splice版本
        public function spliceNextKeys($offset, $length, $keys = []) {
            $funcs = [];
            foreach($keys as $key) {
                if($func = $this->getFunc($key)) {
                    array_push($funcs, $func);
                }
            }
            
            $this->spliceNextFuncs($offset, $length, $funcs);
        }
        public function spliceNextFuncs($offset, $length, $funcs = []) {
            if(count($funcs) > 0) {
                array_splice($this->queue, $offset, $length, $funcs);
            }
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
            $funcs = &$this->queue;
            $keys = $this->keys;
            // 參數處理
            if($count > 0) {
                if(is_object($args[0]) && ($args[0] instanceof self::$resultClass)) {
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
                        array_push($funcs, $func);
                    }
                }
            }
            // 處理0開始的那種非key-pair的funcs
            else {
                $funcsCount = count($this->funcs);
                for($i = 0; $i < $funcsCount; $i += 1) {
                    if(array_key_exists($i, $this->funcs)) {
                        array_push($funcs, $this->funcs[$i]);
                    }
                    else {
                        break;
                    }
                }
            }
            // 參數處理結束
            
            // 處理next佇列
            $this->pushNextFunc();
            
            // 開始迴圈執行
            for($i = 0; $i < count($funcs);) {
                $func = array_shift($funcs);
                
                // 假如取得的是funcsChain
                if($func instanceof self) {
                    $funcResult = call_user_func_array([$func, 'run'], [&$result]);
                }
                else {
                    $funcResult = call_user_func_array($func, [&$result]);
                }
                
                if(is_object($funcResult) && ($funcResult instanceof self::$resultClass)) {
                    $result->addFrom($funcResult);
                }
                
                // isDone是false 或者 回傳值是false 就跳出
                if(!$result->isDone() || $funcResult === false) {
                    break;
                }
                // 假如是funcschain的話就直接加到funcs前面
                elseif($funcResult instanceof self) {
                    array_unshift($funcs, $funcResult);
                }
                // 假如是字串或者數字就增加funcs
                elseif(is_string($funcResult) || is_integer($funcResult)) {
                    if(is_callable($nextFunc = $this->getFunc($funcResult))) {
                        array_unshift($funcs, $nextFunc);
                    }
                }
                // 如果是陣列就整個取代
                elseif(is_array($funcResult)) {
                    $nextFuncs = [];
                    foreach($funcResult as $nextKey) {
                        if(is_string($nextKey) || is_integer($nextKey)) {
                            if(is_callable($nextFunc = $this->getFunc($nextKey))) {
                                array_push($nextFuncs, $nextFunc);
                            }
                        }
                    }
                    
                    // 參考到$this->queue, 用新的陣列覆蓋
                    $funcs = $nextFuncs;
                }
                
                // 追加next資料
                $this->pushNextFunc();
            }
            return $result;
        }
    }