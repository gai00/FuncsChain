<?php
{
    /*
    change log: 
        1.1.4
            修改count() 為 strlen，PHP7.2不支援count(string)
        1.1.3
            增加moveData($fromKey, $toKey)
        1.1.2b
            修改getData有dataDelimiter的狀況之下，找到上層資料的錯誤。
        1.1.2
            setDataRef($key, $ref)
            可以設定參考值，但是不能設定整體資料像是setDataRef($ref)
            必須使用$result->data = &$ref; 來設定。
            
            setDataDelimiter($delimiter = null)
            設定分隔key用的符號，沒有設定的話就是不分隔，
            有設定'.'的話就會變成 setData('a.b.c', 123) => data['a']['b']['c'] = 123
            同理setDataRef, getData, hasData, delData
        1.1.1
            getData改為可回傳參考
            可以用
            $data = &$result->getData('abc');
            來取得參考。
            增加hasData, delData
        1.1.0
            追加函數: 
                void setCode(int number)
                int getCode()
            追加屬性: 
                int code
            修改函數: 
                addFrom 多繼承code
                toArray 多回傳code => int
    */
    class Result {
        const VERSION = '1.1.3';
        
        public $dataDelimiter = null;
        
        public $data;               //結果物件的資料
        public $hasError = false;   //是否有發生程式上的錯誤
        public $hasMessage = false; //是否有發生行為上的錯誤
        public $errors = [];        //儲存程式上錯誤的訊息
        public $messages = [];      //儲存行為上錯誤的訊息
        public $code = 0; // int 錯誤訊息的號碼
        
        public function __construct($_data = []) {
            foreach($_data as $key => $val) {
                switch($key) {
                    case 'errors':
                        $this->appendError($val);
                        break;
                    case 'messages':
                        $this->appendMessage($val);
                        break;
                    case 'dataDelimiter':
                        $this->setDataDelimiter($val);
                        break;
                    case 'data':
                        $this->setData($val);
                        break;
                    case 'code':
                        $this->setCode($val);
                        break;
                }
            }
        }
        
        // 設定code
        public function setCode($code = 0) {
            $this->code = $code;
        }
        // 取得code
        public function getCode() {
            return $this->code;
        }
        
        //是否處理完畢
        public function isDone() {
            return !$this->hasError && !$this->hasMessage;
        }
        
        //加一筆錯誤訊息
        public function appendError($error) {
            if(is_array($error)) {
                foreach($error as $err) {
                    $this->appendError($err);
                }
            }
            elseif($error && strlen($error . "") > 0) {
                $this->hasError = true;
                $this->errors[] = $error;
            }
        }
        
        //加一筆行為上的錯誤訊息
        public function appendMessage($message) {
            if(is_array($message)) {
                foreach($message as $msg) {
                    $this->appendMessage($msg);
                }
            }
            elseif(!is_string($message) && (get_class($message) == "Phalcon\Validation\Message\Group")) {
                foreach($message as $msg) {
                    $this->appendMessage($msg->getMessage());
                }
            }
            elseif($message && strlen($message . "") > 0) {
                $this->hasMessage = true;
                $this->messages[] = $message;
            }
        }
        
        //取得程式上錯誤訊息的陣列
        public function getErrors() {
            return $this->errors;
        }
        
        //取得行為上錯誤訊息的陣列
        public function getMessages() {
            return $this->messages;
        }
        
        public function setDataDelimiter($delimiter = null) {
            $this->dataDelimiter = $delimiter;
        }
        
        //設定資料，支援array也支援key value
        public function setData($data, $val = null) {
            if(func_num_args() > 1 && is_string($data)) {
                $target = &$this->getData($data, true);
                $target = $val;
            }
            else {
                $this->data = $data;
            }
        }
        
        // 設定資料ref
        public function setDataRef(string $keys, &$val = null) {
            $delimiter = $this->dataDelimiter;
            $target = &$this->data;
            
            if($delimiter) {
                $keys = explode($delimiter, $keys);
            }
            else {
                $keys = [$keys];
            }
            
            for($key = array_shift($keys); count($keys) > 0; $key = array_shift($keys)) {
                if(!is_array($target)) {
                    $target = [];
                }
                if(!array_key_exists($key, $target) || !is_array($target[$key])) {
                    $target[$key] = [];
                }
                $target = &$target[$key];
            }
            
            $target[$key] = &$val;
        }
        
        // 判斷是否有資料
        public function hasData($keys) {
            $delimiter = $this->dataDelimiter;
            $target = &$this->data;
            if($delimiter) {
                $keys = explode($delimiter, $keys);
            }
            else {
                $keys = [$keys];
            }
            foreach($keys as $key) {
                if(is_array($target) && array_key_exists($key, $target)) {
                    $target = &$target[$key];
                }
                else {
                    return false;
                }
            }
            return true;
        }
        
        //取得資料，支援取得全部資料，也取得特定key的資料
        // 傳參考會給notice...研究一下怎麼用
        public function &getData($keys = null, $force = false) {
            $null = null;
            $ref = null;
            $delimiter = $this->dataDelimiter;
            $target = &$this->data;
            if(func_num_args() > 0 && is_string($keys)) {
                if($delimiter) {
                    $keys = explode($delimiter, $keys);
                }
                else {
                    $keys = [$keys];
                }
                
                foreach($keys as $key) {
                    if($force) {
                        if(!is_array($target)) {
                            $target = [];
                        }
                        if(!array_key_exists($key, $target)) {
                            $target[$key] = null;
                        }
                    }
                    
                    if(is_array($target) && array_key_exists($key, $target)) {
                        $target = &$target[$key];
                        $ref = &$target;
                    }
                    else {
                        $ref = &$null;
                        break;
                    }
                }
                
            }
            else {
                // return $this->data;
                $ref = &$this->data;
            }
            
            return $ref;
        }
        
        // 移除資料
        public function delData($keys) {
            $delimiter = $this->dataDelimiter;
            $target = &$this->data;
            
            if($delimiter) {
                $keys = explode($delimiter, $keys);
            }
            else {
                $keys = [$keys];
            }
            
            for($key = array_shift($keys); count($keys) > 0; $key = array_shift($keys)) {
                if(is_array($target) && array_key_exists($key, $target)) {
                    $target = &$target[$key];
                }
                else {
                    break;
                }
            }
            
            if(is_array($target) && isset($target[$key])) {
                unset($target[$key]);
            }
        }
        
        // 移動資料 這邊用參考的方式
        public function moveData($fromKey, $toKey) {
            $fromData = &$this->getData($fromKey);
            $this->delData($fromKey);
            $this->setDataRef($toKey, $fromData);
        }
        
        //將此物件轉換成陣列
        public function toArray($hasError = false) {
            $result = [
                'code' => $this->getCode(),
                'isDone' => $this->isDone(),
                'data' => $this->getData(),
                'hasMessage' => $this->hasMessage,
                'messages' => $this->getMessages(),
                'hasError' => $this->hasError,
                'errors' => $this->getErrors(),
            ];
            
            return $result;
        }
        
        public function addFrom(Result $from) {
            $this->setCode($from->getCode());
            $this->setData($from->getData());
            $this->appendMessage($from->getMessages());
            $this->appendError($from->getErrors());
        }
    }
}
