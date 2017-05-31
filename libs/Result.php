<?php
    /*
    version: 1.1.1 (20170522)
        getData改為可回傳參考
        可以用
        $data = &$result->getData('abc');
        來取得參考。
        增加hasData, delData
    version: 1.1.0 (20170414)
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
        const VERSION = '1.1.1';
        
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
            elseif($error && count($error . "") > 0) {
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
            elseif($message && count($message . "") > 0) {
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
        
        //設定資料，支援array也支援key value
        public function setData($data, $val = null) {
            if(func_num_args() > 1 && is_string($data)) {
                $this->data[$data] = $val;
            }
            else {
                $this->data = $data;
            }
        }
        
        // 判斷是否有資料
        public function hasData($key) {
            return array_key_exists($key, $this->data);
        }
        
        //取得資料，支援取得全部資料，也取得特定key的資料
        // 傳參考會給notice...研究一下怎麼用
        public function &getData($key = null) {
            $ref = null;
            if(func_num_args() > 0 && is_string($key)) {
                if(is_array($this->data) && array_key_exists($key, $this->data)) {
                    // return $this->data[$key];
                    $ref = &$this->data[$key];
                }
                else {
                    // return null;
                }
            }
            else {
                // return $this->data;
                $ref = &$this->data;
            }
            
            return $ref;
        }
        
        // 移除資料
        public function delData($key) {
            unset($this->data[$key]);
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