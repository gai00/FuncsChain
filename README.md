# FuncsChain
A PHP class for function flow controlling.  

## FuncsChain construct usages:
### new FuncsChain() ->
new FuncsChain  

### new FuncsChain([key1 => function1, key2 => function2]) -> 
new FuncsChain with key-pair functions  

### new FuncsChain(Result, [function1, function2]) -> 
new FuncsChain include Result of functions  

### new FuncsChain([function1, function2]) -> 
new FuncsChain include new Result of functions  

### new FuncsChain(Result, [key1 => function1, key2 => function2], [key1, key2]) -> 
new FuncsChain include Result of key-pair functions  

### new FuncsChain([key1 => function1, key2 => function2], [key1, key2]) -> 
new FuncsChain include new Result of key-pair functions  

## FuncsChain->run usages:
### run() -> 
new Result of after running funcs

### run($result) -> 
Result of $result->addFrom($funcResult)

### run([key1, key2]) -> 
new Result of after running funcs from keys

### run($result, [key1, key2]) -> 
Result of $result->addFrom($funcResult) that after running funcs from keys